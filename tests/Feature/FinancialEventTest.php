<?php

namespace Tests\Feature;

use App\Models\FinancialEvent;
use App\Models\User;
use App\Services\Accounting\AccountingEventRules;
use App\Services\Accounting\FinancialEventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class FinancialEventTest extends TestCase
{
    use RefreshDatabase;

    private FinancialEventService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FinancialEventService(new AccountingEventRules());
    }

    // -------------------------------------------------------------------------
    // EVT-02: Controlled event_type values
    // -------------------------------------------------------------------------

    public function test_record_rejects_unknown_event_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->record([
            'event_type'  => 'invalid_type',
            'customer_id' => 1,
            'amount'      => 100,
            'event_date'  => '2026-06-24',
        ]);
    }

    // -------------------------------------------------------------------------
    // EVT-01/03: Record creates a financial event with correct payload
    // -------------------------------------------------------------------------

    public function test_record_creates_financial_event_with_correct_fields(): void
    {
        $event = $this->service->record([
            'event_type'       => 'storage_fee',
            'customer_id'      => 42,
            'stock_id'         => 10,
            'product_id'       => 3,
            'storage_id'       => 1,
            'quantity'         => 500.0,
            'unit'             => 'kg',
            'amount'           => 2500.00,
            'currency'         => 'XOF',
            'event_date'       => '2026-06-01',
            'due_date'         => '2026-07-01',
            'service_type'     => 'storage_fee',
            'source_reference' => 'billings.55',
            'source_model'     => 'billings',
            'created_by'       => 1,
        ]);

        $this->assertDatabaseHas('financial_events', [
            'event_type'         => 'storage_fee',
            'customer_id'        => 42,
            'amount'             => 2500.00,
            'currency'           => 'XOF',
            'accounting_status'  => FinancialEvent::STATUS_DRAFT,
            'odoo_model'         => 'account.move',
        ]);

        $this->assertStringStartsWith('CAG-FE-STORAGE-FEE-', $event->financial_event_id);
        $this->assertNotEmpty($event->idempotency_key);
    }

    // -------------------------------------------------------------------------
    // EVT-06: Idempotency — duplicate submissions return the same record
    // -------------------------------------------------------------------------

    public function test_duplicate_submission_returns_existing_event(): void
    {
        $payload = [
            'event_type'       => 'payment_received',
            'customer_id'      => 7,
            'amount'           => 1000.00,
            'event_date'       => '2026-06-10',
            'source_reference' => 'payments.12',
            'idempotency_key'  => 'unique-key-for-pay-12',
        ];

        $first  = $this->service->record($payload);
        $second = $this->service->record($payload);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('financial_events', 1);
    }

    // -------------------------------------------------------------------------
    // EVT-04: accounting_status state machine — valid transitions
    // -------------------------------------------------------------------------

    public function test_state_machine_allows_valid_transitions(): void
    {
        $event = $this->makeStorageFeeEvent();

        $this->assertSame(FinancialEvent::STATUS_DRAFT, $event->accounting_status);

        $event->markExportReady();
        $this->assertSame(FinancialEvent::STATUS_EXPORT_READY, $event->fresh()->accounting_status);

        $event->markExportedCsv('BATCH-001');
        $this->assertSame(FinancialEvent::STATUS_EXPORTED_CSV, $event->fresh()->accounting_status);
        $this->assertSame('BATCH-001', $event->fresh()->export_batch_id);

        $event->markReconciled();
        $this->assertSame(FinancialEvent::STATUS_RECONCILED, $event->fresh()->accounting_status);
    }

    public function test_state_machine_allows_api_sync_path(): void
    {
        $event = $this->makeStorageFeeEvent();
        $event->markExportReady();
        $event->markSyncedApi('account.move', '999', 'INV/2026/001');

        $fresh = $event->fresh();
        $this->assertSame(FinancialEvent::STATUS_SYNCED_API, $fresh->accounting_status);
        $this->assertSame('account.move', $fresh->odoo_model);
        $this->assertSame('999', $fresh->odoo_record_id);
        $this->assertSame('INV/2026/001', $fresh->odoo_reference);
        $this->assertNotNull($fresh->last_sync_at);
    }

    public function test_state_machine_allows_failure_and_retry(): void
    {
        $event = $this->makeStorageFeeEvent();
        $event->markExportReady();
        $event->markFailed('ODOO_TIMEOUT', 'Request timed out after 30s');

        $fresh = $event->fresh();
        $this->assertSame(FinancialEvent::STATUS_FAILED, $fresh->accounting_status);
        $this->assertSame(1, $fresh->retry_count);
        $this->assertSame('ODOO_TIMEOUT', $fresh->sync_error_code);

        // Retry: failed → export_ready
        $fresh->transitionTo(FinancialEvent::STATUS_EXPORT_READY);
        $this->assertSame(FinancialEvent::STATUS_EXPORT_READY, $fresh->fresh()->accounting_status);
    }

    public function test_state_machine_blocks_invalid_transitions(): void
    {
        $this->expectException(LogicException::class);

        $event = $this->makeStorageFeeEvent();
        // draft → reconciled is not allowed
        $event->transitionTo(FinancialEvent::STATUS_RECONCILED);
    }

    public function test_reversed_status_is_terminal(): void
    {
        $this->expectException(LogicException::class);

        $event = $this->makeStorageFeeEvent();
        $event->markExportReady();
        $event->markReversed();
        // reversed → anything is not allowed
        $event->transitionTo(FinancialEvent::STATUS_DRAFT);
    }

    // -------------------------------------------------------------------------
    // EVT-04: isPosted / isEditable helpers
    // -------------------------------------------------------------------------

    public function test_is_posted_returns_false_for_draft(): void
    {
        $event = $this->makeStorageFeeEvent();
        $this->assertFalse($event->isPosted());
        $this->assertTrue($event->isEditable());
    }

    public function test_is_posted_returns_true_after_export(): void
    {
        $event = $this->makeStorageFeeEvent();
        $event->markExportReady();
        $event->markExportedCsv('BATCH-X');

        $this->assertTrue($event->isPosted());
        $this->assertFalse($event->isEditable());
    }

    // -------------------------------------------------------------------------
    // EVT-04: Approval gate for events that require finance sign-off
    // -------------------------------------------------------------------------

    public function test_mark_ready_for_export_blocks_unapproved_claim(): void
    {
        $this->expectException(LogicException::class);

        $event = $this->service->record([
            'event_type'  => 'credit_note',
            'customer_id' => 5,
            'amount'      => 400.00,
            'event_date'  => '2026-06-24',
        ]);

        $this->service->markReadyForExport($event);
    }

    public function test_approve_then_mark_ready_for_export_succeeds(): void
    {
        $event = $this->service->record([
            'event_type'  => 'credit_note',
            'customer_id' => 5,
            'amount'      => 400.00,
            'event_date'  => '2026-06-24',
        ]);

        $this->service->approve($event, approvedBy: 1);
        $this->service->markReadyForExport($event);

        $this->assertSame(FinancialEvent::STATUS_EXPORT_READY, $event->fresh()->accounting_status);
        $this->assertSame(FinancialEvent::APPROVAL_APPROVED, $event->fresh()->approval_status);
    }

    public function test_approve_throws_for_event_that_does_not_need_approval(): void
    {
        $this->expectException(LogicException::class);

        $event = $this->service->record([
            'event_type'  => 'storage_fee',
            'customer_id' => 5,
            'amount'      => 200.00,
            'event_date'  => '2026-06-24',
        ]);

        $this->service->approve($event, approvedBy: 1);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeStorageFeeEvent(): FinancialEvent
    {
        return $this->service->record([
            'event_type'  => 'storage_fee',
            'customer_id' => 1,
            'amount'      => 1500.00,
            'event_date'  => '2026-06-01',
        ]);
    }
}
