<?php

namespace Tests\Feature;

use App\Models\Claim;
use App\Models\ExportBatch;
use App\Models\FinancialEvent;
use App\Models\Rotten;
use App\Models\Stock;
use App\Models\Storage;
use App\Models\User;
use App\Services\Accounting\AccountingEventRules;
use App\Services\Accounting\ClaimWorkflowService;
use App\Services\Accounting\FinancialEventService;
use App\Services\Export\CreditNoteCsvExporter;
use App\Services\Export\OdooExportService;
use App\Services\Export\RefundCsvExporter;
use App\Services\Odoo\OdooCustomerMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class ClaimWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private ClaimWorkflowService  $workflow;
    private FinancialEventService $events;

    protected function setUp(): void
    {
        parent::setUp();
        $rules          = new AccountingEventRules();
        $this->events   = new FinancialEventService($rules);
        $this->workflow = new ClaimWorkflowService($this->events);
    }

    // =========================================================================
    // WSC-03: Record claim event
    // =========================================================================

    public function test_record_claim_event_creates_draft_financial_event(): void
    {
        $claim = $this->makeClaim();

        $event = $this->workflow->recordClaimEvent($claim, 500.00, createdBy: 1);

        $this->assertSame('claim',                   $event->event_type);
        $this->assertSame(FinancialEvent::STATUS_DRAFT, $event->accounting_status);
        $this->assertNull($event->approval_status);
        $this->assertSame(500.00, $event->amount);
        $this->assertSame((string) $claim->id, $event->source_reference);
    }

    public function test_record_claim_event_rejects_zero_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->workflow->recordClaimEvent($this->makeClaim(), 0, createdBy: 1);
    }

    public function test_record_claim_event_is_idempotent(): void
    {
        $claim  = $this->makeClaim();
        $first  = $this->workflow->recordClaimEvent($claim, 500.00, createdBy: 1);
        $second = $this->workflow->recordClaimEvent($claim, 500.00, createdBy: 1);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('financial_events', 1);
    }

    // =========================================================================
    // WSC-04: Issue credit note from approved claim
    // =========================================================================

    public function test_issue_credit_note_creates_approved_export_ready_event(): void
    {
        $claim      = $this->makeClaim();
        $claimEvent = $this->workflow->recordClaimEvent($claim, 500.00, createdBy: 1);
        $this->events->approve($claimEvent, approvedBy: 1);

        $creditNote = $this->workflow->issueCreditNote($claimEvent, 400.00, approvedBy: 1);

        // credit_note is NOT in the export block list — it reaches export_ready
        $this->assertSame('credit_note',                        $creditNote->event_type);
        $this->assertSame(FinancialEvent::STATUS_EXPORT_READY,  $creditNote->accounting_status);
        $this->assertSame(FinancialEvent::APPROVAL_APPROVED,    $creditNote->approval_status);
        $this->assertSame(400.00,                               $creditNote->amount);
    }

    public function test_spoilage_compensation_stays_approved_but_not_export_ready(): void
    {
        // spoilage_cool_agristock_fault references claims_liability + compensation_expense
        // — both rejected by Finance. The event is approved internally but must NOT
        // reach export_ready until replacement accounts are confirmed.
        $user     = $this->makeUser();
        $storage  = Storage::create(['name' => 'S1', 'location' => 'Loc', 'capacity' => 500]);
        $stock    = Stock::create(['ref' => 'STK-ROT', 'customer_id' => $user->id, 'storage_id' => $storage->id, 'qty' => 200, 'expired_at' => 365, 'created_by' => $user->id]);
        $category = \App\Models\Category::create(['name' => 'Cereals']);
        $capacity = \App\Models\Capacity::create(['name' => 'Sack']);
        $product  = \App\Models\Product::create(['name' => 'Maize', 'category_id' => $category->id, 'min_expired_at' => 180, 'max_expired_at' => 365]);
        $detail   = \App\Models\Detail::create(['qty' => 200, 'stock_id' => $stock->id, 'product_id' => $product->id, 'container_id' => $capacity->id]);
        $rotten   = Rotten::create(['before_qty' => 200, 'qty' => 50, 'after_qty' => 150, 'detail_id' => $detail->id, 'stock_id' => $stock->id]);

        $event = $this->workflow->recordSpoilageCompensation(
            $rotten, 2000.00, approvedBy: 1, investigationNote: 'Cold chain failure confirmed.'
        );

        $this->assertSame('spoilage_cool_agristock_fault',   $event->event_type);
        $this->assertSame(FinancialEvent::APPROVAL_APPROVED,  $event->approval_status);
        // Must NOT be export_ready — blocked by rejected accounts
        $this->assertSame(FinancialEvent::STATUS_DRAFT, $event->accounting_status);
    }

    public function test_mark_ready_for_export_throws_for_blocked_event_types(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/cannot be marked export-ready/');

        $event = $this->events->record([
            'event_type'  => 'claim',
            'customer_id' => 1,
            'amount'      => 300.00,
            'event_date'  => '2026-06-26',
        ]);
        $this->events->approve($event, approvedBy: 1);
        $this->events->markReadyForExport($event); // should throw
    }

    public function test_issue_credit_note_fails_on_unapproved_claim(): void
    {
        $this->expectException(LogicException::class);

        $claim      = $this->makeClaim();
        $claimEvent = $this->workflow->recordClaimEvent($claim, 500.00, createdBy: 1);

        // Not approved yet
        $this->workflow->issueCreditNote($claimEvent, 400.00, approvedBy: 1);
    }

    public function test_issue_credit_note_fails_on_wrong_event_type(): void
    {
        $this->expectException(LogicException::class);

        $storageEvent = $this->events->record([
            'event_type'  => 'storage_fee',
            'customer_id' => 1,
            'amount'      => 200.00,
            'event_date'  => '2026-06-25',
        ]);

        $this->workflow->issueCreditNote($storageEvent, 100.00, approvedBy: 1);
    }

    // =========================================================================
    // WSC-05: Request refund from approved credit note
    // =========================================================================

    public function test_request_refund_creates_approved_export_ready_event(): void
    {
        [$claim, $claimEvent, $creditNote] = $this->makeCreditNote(500.00, 400.00);

        $refund = $this->workflow->requestRefund($creditNote, 400.00, 'CASH', approvedBy: 1);

        $this->assertSame('refund',                            $refund->event_type);
        $this->assertSame(FinancialEvent::STATUS_EXPORT_READY, $refund->accounting_status);
        $this->assertSame(FinancialEvent::APPROVAL_APPROVED,   $refund->approval_status);
        $this->assertSame(400.00,                              $refund->amount);
    }

    public function test_request_refund_rejects_amount_exceeding_credit_note(): void
    {
        $this->expectException(InvalidArgumentException::class);

        [$claim, $claimEvent, $creditNote] = $this->makeCreditNote(500.00, 300.00);

        $this->workflow->requestRefund($creditNote, 400.00, 'CASH', approvedBy: 1); // 400 > 300
    }

    public function test_request_refund_fails_on_unapproved_credit_note(): void
    {
        $this->expectException(LogicException::class);

        $claim      = $this->makeClaim();
        $claimEvent = $this->workflow->recordClaimEvent($claim, 500.00, createdBy: 1);
        // claimEvent is not approved — create a fake credit_note event directly
        $creditNote = $this->events->record([
            'event_type'  => 'credit_note',
            'customer_id' => $claim->customer_id,
            'amount'      => 300.00,
            'event_date'  => '2026-06-25',
        ]);

        $this->workflow->requestRefund($creditNote, 300.00, 'CASH', approvedBy: 1);
    }

    // WSC-02 spoilage test moved — see test_spoilage_compensation_stays_approved_but_not_export_ready

    // =========================================================================
    // CSV-04: CreditNoteCsvExporter
    // =========================================================================

    public function test_credit_note_exporter_returns_correct_headers(): void
    {
        $exporter = new CreditNoteCsvExporter(new OdooCustomerMapper());
        $headers  = $exporter->headers();

        foreach (['id', 'partner_id/id', 'move_type', 'invoice_date', 'journal_id/name', 'ref'] as $col) {
            $this->assertContains($col, $headers);
        }
    }

    public function test_credit_note_exporter_maps_approved_event_to_row(): void
    {
        [$claim, $claimEvent, $creditNote] = $this->makeCreditNote(500.00, 400.00);

        $exporter = new CreditNoteCsvExporter(new OdooCustomerMapper());
        $result   = $exporter->export(eventIds: [$creditNote->id]);

        $this->assertCount(1, $result['rows']);
        $row = $result['rows'][0];

        $this->assertSame('coolagristock.creditnote.' . $creditNote->id, $row['id']);
        $this->assertSame('coolagristock.customer.' . $creditNote->customer_id, $row['partner_id/id']);
        $this->assertSame('out_refund', $row['move_type']);
        $this->assertSame(400.00, $row['invoice_line_ids/price_unit']);
    }

    public function test_credit_note_exporter_skips_draft_events(): void
    {
        $claim      = $this->makeClaim();
        $claimEvent = $this->workflow->recordClaimEvent($claim, 300.00, createdBy: 1);
        // draft — not approved, not export_ready

        $exporter = new CreditNoteCsvExporter(new OdooCustomerMapper());
        $result   = $exporter->export(eventIds: [$claimEvent->id]);

        $this->assertCount(0, $result['rows']);
    }

    // =========================================================================
    // CSV-05: RefundCsvExporter
    // =========================================================================

    public function test_refund_exporter_returns_correct_headers(): void
    {
        $exporter = new RefundCsvExporter(new OdooCustomerMapper());
        $headers  = $exporter->headers();

        foreach (['id', 'partner_id/id', 'payment_type', 'date', 'amount', 'journal_id/name'] as $col) {
            $this->assertContains($col, $headers);
        }
    }

    public function test_refund_exporter_maps_approved_refund_to_row(): void
    {
        [$claim, $claimEvent, $creditNote] = $this->makeCreditNote(500.00, 400.00);
        $refund = $this->workflow->requestRefund($creditNote, 400.00, 'CASH', approvedBy: 1);

        $exporter = new RefundCsvExporter(new OdooCustomerMapper());
        $result   = $exporter->export(eventIds: [$refund->id]);

        $this->assertCount(1, $result['rows']);
        $row = $result['rows'][0];

        $this->assertSame('coolagristock.refund.' . $refund->id, $row['id']);
        $this->assertSame('outbound', $row['payment_type']);
        $this->assertSame(400.00, $row['amount']);
    }

    // =========================================================================
    // Full ZIP package now includes credit notes and refunds
    // =========================================================================

    public function test_full_zip_package_includes_credit_note_and_refund_files(): void
    {
        $service = app(OdooExportService::class);
        $batch   = $service->generateFullPackage(generatedBy: 1);

        $this->assertSame(ExportBatch::STATUS_READY, $batch->status);

        $zipPath = storage_path('app/' . $batch->file_path);
        $zip     = new \ZipArchive();
        $zip->open($zipPath);

        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        $zip->close();

        $this->assertContains('4_credit_notes.csv', $names);
        $this->assertContains('5_refunds.csv',      $names);
        $this->assertContains('manifest.json',       $names);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeUser(): User
    {
        return User::create([
            'name'     => 'Farmer ' . uniqid(),
            'email'    => 'farmer' . uniqid() . '@farm.gh',
            'phone'    => '024' . rand(1000000, 9999999),
            'password' => bcrypt('secret'),
            'group_id' => 4,
        ]);
    }

    private function makeClaim(): Claim
    {
        $user    = $this->makeUser();
        $storage = Storage::create(['name' => 'Store ' . uniqid(), 'location' => 'Loc', 'capacity' => 500]);

        return Claim::create([
            'name'        => 'AUTRES',
            'message'     => 'Spoilage claim for testing',
            'status'      => 'EN COURS',
            'customer_id' => $user->id,
            'storage_id'  => $storage->id,
        ]);
    }

    private function makeCreditNote(float $claimed, float $credited): array
    {
        $claim      = $this->makeClaim();
        $claimEvent = $this->workflow->recordClaimEvent($claim, $claimed, createdBy: 1);
        $this->events->approve($claimEvent, approvedBy: 1);
        $creditNote = $this->workflow->issueCreditNote($claimEvent, $credited, approvedBy: 1);

        return [$claim, $claimEvent, $creditNote];
    }
}
