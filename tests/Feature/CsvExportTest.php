<?php

namespace Tests\Feature;

use App\Models\Billing;
use App\Models\Detail;
use App\Models\ExportBatch;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Storage;
use App\Models\User;
use App\Services\Export\ContactsCsvExporter;
use App\Services\Export\ExportValidator;
use App\Services\Export\InvoiceCsvExporter;
use App\Services\Export\OdooExportService;
use App\Services\Export\PaymentCsvExporter;
use App\Services\Odoo\OdooCustomerMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsvExportTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // CSV-01: ContactsCsvExporter
    // =========================================================================

    public function test_contacts_exporter_returns_correct_headers(): void
    {
        $exporter = new ContactsCsvExporter(new OdooCustomerMapper());
        $headers  = $exporter->headers();

        foreach (['id', 'name', 'phone', 'email', 'customer_rank', 'company_type', 'ref'] as $col) {
            $this->assertContains($col, $headers);
        }
    }

    public function test_contacts_exporter_maps_user_to_partner_row(): void
    {
        $user = $this->makeUser(['name' => 'Kofi Boateng', 'phone' => '0244111111', 'email' => 'kofi@farm.gh']);

        $exporter = new ContactsCsvExporter(new OdooCustomerMapper());
        $result   = $exporter->export([$user->id]);

        $this->assertCount(1, $result['rows']);
        $this->assertSame('coolagristock.customer.' . $user->id, $result['rows'][0]['id']);
        $this->assertSame('Kofi Boateng', $result['rows'][0]['name']);
    }

    public function test_contacts_exporter_skips_user_with_no_name(): void
    {
        $user = $this->makeUser(['name' => '']);

        $exporter = new ContactsCsvExporter(new OdooCustomerMapper());
        $result   = $exporter->export([$user->id]);

        $this->assertCount(0, $result['rows']);
        $this->assertCount(1, $result['skipped']);
    }

    public function test_contacts_exporter_produces_valid_csv_string(): void
    {
        $user     = $this->makeUser(['name' => 'Ama Mensah', 'phone' => '0201234567']);
        $exporter = new ContactsCsvExporter(new OdooCustomerMapper());
        $result   = $exporter->export([$user->id]);
        $csv      = $exporter->toCsvString($result['rows']);

        $this->assertStringContainsString('Ama Mensah', $csv);
        $this->assertStringContainsString('coolagristock.customer.' . $user->id, $csv);
        $this->assertStringContainsString('id,name,phone', $csv);
    }

    // =========================================================================
    // CSV-02: InvoiceCsvExporter
    // =========================================================================

    public function test_invoice_exporter_returns_correct_headers(): void
    {
        $exporter = new InvoiceCsvExporter(new OdooCustomerMapper());
        $headers  = $exporter->headers();

        foreach (['id', 'partner_id/id', 'invoice_date', 'invoice_date_due', 'journal_id/name'] as $col) {
            $this->assertContains($col, $headers);
        }
    }

    public function test_invoice_exporter_maps_billing_to_invoice_row(): void
    {
        [$user, $stock, $billing] = $this->makeBilling(2500.00);

        $exporter = new InvoiceCsvExporter(new OdooCustomerMapper());
        $result   = $exporter->export(billingIds: [$billing->id]);

        $this->assertCount(1, $result['rows']);
        $row = $result['rows'][0];

        $this->assertSame('coolagristock.invoice.' . $billing->id, $row['id']);
        $this->assertSame('coolagristock.customer.' . $user->id,   $row['partner_id/id']);
        $this->assertSame(2500.00, $row['invoice_line_ids/price_unit']);
        $this->assertStringContainsString('Customer Invoices', $row['journal_id/name']);
    }

    public function test_invoice_exporter_skips_billing_when_customer_is_soft_deleted(): void
    {
        // Create customer, billing, then soft-delete the customer.
        // The FK is satisfied (soft-delete keeps the row), but User::find()
        // returns null — so the exporter cannot resolve the partner and skips the row.
        $user    = $this->makeUser(['name' => 'Deleted Farmer']);
        $storage = Storage::create(['name' => 'S1', 'location' => 'Loc', 'capacity' => 500]);
        $stock   = Stock::create(['ref' => 'STK-X', 'customer_id' => $user->id, 'storage_id' => $storage->id, 'qty' => 100, 'expired_at' => 365, 'created_by' => $user->id]);
        $billing = Billing::create(['ref' => 'BIL-X', 'stock_id' => $stock->id, 'customer_id' => $user->id, 'amount' => 500, 'discount' => 0]);

        $user->delete(); // soft-delete — User::find() will now return null

        $exporter = new InvoiceCsvExporter(new OdooCustomerMapper());
        $result   = $exporter->export(billingIds: [$billing->id]);

        $this->assertCount(0, $result['rows']);
        $this->assertCount(1, $result['skipped']);
    }

    // =========================================================================
    // CSV-03: PaymentCsvExporter
    // =========================================================================

    public function test_payment_exporter_returns_correct_headers(): void
    {
        $exporter = new PaymentCsvExporter(new OdooCustomerMapper());
        $headers  = $exporter->headers();

        foreach (['id', 'partner_id/id', 'date', 'amount', 'journal_id/name'] as $col) {
            $this->assertContains($col, $headers);
        }
    }

    public function test_payment_exporter_maps_payment_to_row(): void
    {
        $user    = $this->makeUser(['name' => 'Abena Oti', 'phone' => '0209999999']);
        $storage = Storage::create(['name' => 'SA', 'location' => 'LocA', 'capacity' => 100]);
        $stock   = Stock::create(['ref' => 'STK-A', 'customer_id' => $user->id, 'storage_id' => $storage->id, 'qty' => 50, 'expired_at' => 365, 'created_by' => $user->id]);
        // billing_id is required with FK — advance payments still reference a billing in this system
        $billing = Billing::create(['ref' => 'BIL-ADV', 'stock_id' => $stock->id, 'customer_id' => $user->id, 'amount' => 1500, 'discount' => 0]);
        $payment = Payment::create([
            'customer_id' => $user->id,
            'amount'      => 1500.00,
            'method'      => 'MOBILE MONEY',
            'billing_id'  => $billing->id,
            'stock_id'    => $stock->id,
            'location'    => 'HQ',
            'created_by'  => $user->id,
        ]);

        $exporter = new PaymentCsvExporter(new OdooCustomerMapper());
        $result   = $exporter->export(paymentIds: [$payment->id]);

        $this->assertCount(1, $result['rows']);
        $row = $result['rows'][0];

        $this->assertSame('coolagristock.payment.' . $payment->id, $row['id']);
        $this->assertSame('coolagristock.customer.' . $user->id,   $row['partner_id/id']);
        $this->assertSame(1500.00, $row['amount']);
        $this->assertSame('Mobile Money', $row['journal_id/name']);
        // billing_id is present — invoice reference is included
        $this->assertSame('coolagristock.invoice.' . $billing->id, $row['reconciled_invoice_ids/id']);
    }

    public function test_payment_exporter_links_invoice_when_billing_id_present(): void
    {
        $user    = $this->makeUser(['name' => 'Kweku Asare', 'phone' => '0267777777']);
        $storage = Storage::create(['name' => 'SB', 'location' => 'LocB', 'capacity' => 200]);
        $stock   = Stock::create(['ref' => 'STK-B', 'customer_id' => $user->id, 'storage_id' => $storage->id, 'qty' => 50, 'expired_at' => 365, 'created_by' => $user->id]);
        $billing = Billing::create(['ref' => 'BIL-B', 'stock_id' => $stock->id, 'customer_id' => $user->id, 'amount' => 800, 'discount' => 0]);
        $payment = Payment::create([
            'customer_id' => $user->id,
            'amount'      => 800.00,
            'method'      => 'CASH',
            'billing_id'  => $billing->id,
            'stock_id'    => $stock->id,
            'location'    => 'HQ',
            'created_by'  => $user->id,
        ]);

        $exporter = new PaymentCsvExporter(new OdooCustomerMapper());
        $result   = $exporter->export(paymentIds: [$payment->id]);

        $this->assertCount(1, $result['rows']);
        $this->assertSame('coolagristock.invoice.' . $billing->id, $result['rows'][0]['reconciled_invoice_ids/id']);
    }

    // =========================================================================
    // CSV-07: ExportValidator
    // =========================================================================

    public function test_validator_flags_blocking_error_for_billing_with_zero_amount(): void
    {
        $user    = $this->makeUser(['name' => 'Zero Fee']);
        $storage = Storage::create(['name' => 'S3', 'location' => 'L3', 'capacity' => 100]);
        $stock   = Stock::create(['ref' => 'STK-Z', 'customer_id' => $user->id, 'storage_id' => $storage->id, 'qty' => 10, 'expired_at' => 365, 'created_by' => $user->id]);
        $billing = Billing::create(['ref' => 'BIL-Z', 'stock_id' => $stock->id, 'customer_id' => $user->id, 'amount' => 0, 'discount' => 0]);

        $validator = new ExportValidator();
        $report    = $validator->validateInvoices(billingIds: [$billing->id]);

        $this->assertGreaterThan(0, $report['blocking_errors']);
    }

    public function test_validator_flags_warning_for_missing_tax(): void
    {
        [$user, $stock, $billing] = $this->makeBilling(500.00);

        $validator = new ExportValidator();
        $report    = $validator->validateInvoices(billingIds: [$billing->id]);

        $warnings = array_filter($report['issues'], fn ($i) => $i['severity'] === 'warning');
        $this->assertNotEmpty($warnings);
    }

    public function test_validator_passes_clean_contacts(): void
    {
        $user = $this->makeUser(['name' => 'Clean Farmer']);

        $validator = new ExportValidator();
        $report    = $validator->validateContacts([$user->id]);

        $this->assertSame(0, $report['blocking_errors']);
    }

    // =========================================================================
    // CSV-06/08: OdooExportService batch tracking
    // =========================================================================

    public function test_export_service_creates_export_batch_record(): void
    {
        $service = app(OdooExportService::class);
        $batch   = $service->generateContacts(generatedBy: 1);

        $this->assertInstanceOf(ExportBatch::class, $batch);
        $this->assertDatabaseHas('export_batches', [
            'export_type' => ExportBatch::TYPE_CONTACTS,
        ]);
        $this->assertStringStartsWith('CAG-EXPORT-', $batch->batch_id);
    }

    public function test_export_service_generates_ready_contacts_batch(): void
    {
        $this->makeUser(['name' => 'Export Farmer', 'phone' => '0201111111']);

        $service = app(OdooExportService::class);
        $batch   = $service->generateContacts(generatedBy: 1);

        $this->assertSame(ExportBatch::STATUS_READY, $batch->status);
        $this->assertNotNull($batch->file_checksum);
        $this->assertGreaterThan(0, $batch->row_count);
    }

    public function test_export_service_generates_full_zip_package(): void
    {
        $this->makeUser(['name' => 'Package Farmer', 'phone' => '0202222222']);

        $service = app(OdooExportService::class);
        $batch   = $service->generateFullPackage(generatedBy: 1);

        $this->assertSame(ExportBatch::TYPE_ZIP, $batch->export_type);
        $this->assertSame(ExportBatch::STATUS_READY, $batch->status);
        $this->assertNotNull($batch->file_checksum);
        $this->assertStringEndsWith('.zip', $batch->file_name);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'name'     => 'Test Farmer ' . uniqid(),
            'email'    => 'farmer' . uniqid() . '@farm.gh',
            'phone'    => '024' . rand(1000000, 9999999),
            'password' => bcrypt('secret'),
            'group_id' => 4,
        ], $attributes));
    }

    private function makeBilling(float $amount): array
    {
        $user    = $this->makeUser(['name' => 'Billing Farmer ' . uniqid()]);
        $storage = Storage::create(['name' => 'Store ' . uniqid(), 'location' => 'Loc', 'capacity' => 1000]);
        $stock   = Stock::create([
            'ref'         => 'STK-' . uniqid(),
            'customer_id' => $user->id,
            'storage_id'  => $storage->id,
            'qty'         => 200,
            'expired_at'  => 365,
            'created_by'  => $user->id,
        ]);
        $billing = Billing::create([
            'ref'         => 'BIL-' . uniqid(),
            'stock_id'    => $stock->id,
            'customer_id' => $user->id,
            'amount'      => $amount,
            'discount'    => 0,
        ]);

        return [$user, $stock, $billing];
    }
}
