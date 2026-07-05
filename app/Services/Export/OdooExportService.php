<?php

namespace App\Services\Export;

use App\Models\ExportBatch;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * CSV-06 / CSV-08: Orchestrates export batch creation, file generation, and ZIP packaging.
 *
 * Usage:
 *   $service = app(OdooExportService::class);
 *
 *   // Generate individual files
 *   $contactsBatch     = $service->generateContacts($from, $to, $userId);
 *   $invoicesBatch     = $service->generateInvoices($from, $to, $userId);
 *   $paymentsBatch     = $service->generatePayments($from, $to, $userId);
 *   $creditNotesBatch  = $service->generateCreditNotes($from, $to, $userId);
 *   $refundsBatch      = $service->generateRefunds($from, $to, $userId);
 *
 *   // Or generate the full ZIP package in one call (all 5 files)
 *   $zipBatch = $service->generateFullPackage($from, $to, $userId);
 */
class OdooExportService
{
    private const DISK         = 'local';
    private const EXPORT_DIR   = 'odoo_exports';

    public function __construct(
        private readonly ContactsCsvExporter  $contacts,
        private readonly InvoiceCsvExporter   $invoices,
        private readonly PaymentCsvExporter   $payments,
        private readonly CreditNoteCsvExporter $creditNotes,
        private readonly RefundCsvExporter    $refunds,
        private readonly ExportValidator      $validator,
    ) {}

    // -------------------------------------------------------------------------
    // CSV-01: Contacts
    // -------------------------------------------------------------------------

    public function generateContacts(
        ?Carbon $from       = null,
        ?Carbon $to         = null,
        ?int    $generatedBy = null
    ): ExportBatch {
        $batch = $this->openBatch(ExportBatch::TYPE_CONTACTS, $from, $to, $generatedBy);

        try {
            $validation = $this->validator->validateContacts();

            if ($validation['blocking_errors'] > 0) {
                return $this->failBatch($batch, 'Validation failed: ' . $validation['blocking_errors'] . ' blocking error(s).', $validation);
            }

            $result   = $this->contacts->export();
            $csvStr   = $this->contacts->toCsvString($result['rows']);
            $fileName = $this->fileName('contacts', $from, $to);

            return $this->closeBatch($batch, $csvStr, $fileName, count($result['rows']), $validation);
        } catch (\Throwable $e) {
            return $this->failBatch($batch, $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // CSV-02: Invoices
    // -------------------------------------------------------------------------

    public function generateInvoices(
        ?Carbon $from        = null,
        ?Carbon $to          = null,
        ?int    $generatedBy = null
    ): ExportBatch {
        $batch = $this->openBatch(ExportBatch::TYPE_INVOICES, $from, $to, $generatedBy);

        try {
            $validation = $this->validator->validateInvoices($from, $to);

            if ($validation['blocking_errors'] > 0) {
                return $this->failBatch($batch, 'Validation failed: ' . $validation['blocking_errors'] . ' blocking error(s).', $validation);
            }

            $result   = $this->invoices->export($from, $to);
            $csvStr   = $this->invoices->toCsvString($result['rows']);
            $fileName = $this->fileName('invoices', $from, $to);

            return $this->closeBatch($batch, $csvStr, $fileName, count($result['rows']), $validation);
        } catch (\Throwable $e) {
            return $this->failBatch($batch, $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // CSV-03: Payments
    // -------------------------------------------------------------------------

    public function generatePayments(
        ?Carbon $from        = null,
        ?Carbon $to          = null,
        ?int    $generatedBy = null
    ): ExportBatch {
        $batch = $this->openBatch(ExportBatch::TYPE_PAYMENTS, $from, $to, $generatedBy);

        try {
            $validation = $this->validator->validatePayments($from, $to);

            if ($validation['blocking_errors'] > 0) {
                return $this->failBatch($batch, 'Validation failed: ' . $validation['blocking_errors'] . ' blocking error(s).', $validation);
            }

            $result   = $this->payments->export($from, $to);
            $csvStr   = $this->payments->toCsvString($result['rows']);
            $fileName = $this->fileName('payments', $from, $to);

            return $this->closeBatch($batch, $csvStr, $fileName, count($result['rows']), $validation);
        } catch (\Throwable $e) {
            return $this->failBatch($batch, $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // CSV-04: Credit Notes
    // -------------------------------------------------------------------------

    public function generateCreditNotes(
        ?Carbon $from        = null,
        ?Carbon $to          = null,
        ?int    $generatedBy = null
    ): ExportBatch {
        $batch = $this->openBatch(ExportBatch::TYPE_CREDIT_NOTES, $from, $to, $generatedBy);

        try {
            $result   = $this->creditNotes->export($from, $to);
            $csvStr   = $this->creditNotes->toCsvString($result['rows']);
            $fileName = $this->fileName('credit_notes', $from, $to);

            return $this->closeBatch($batch, $csvStr, $fileName, count($result['rows']), ['blocking_errors' => 0, 'warnings' => 0, 'issues' => []]);
        } catch (\Throwable $e) {
            return $this->failBatch($batch, $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // CSV-05: Refunds
    // -------------------------------------------------------------------------

    public function generateRefunds(
        ?Carbon $from        = null,
        ?Carbon $to          = null,
        ?int    $generatedBy = null
    ): ExportBatch {
        $batch = $this->openBatch(ExportBatch::TYPE_REFUNDS, $from, $to, $generatedBy);

        try {
            $result   = $this->refunds->export($from, $to);
            $csvStr   = $this->refunds->toCsvString($result['rows']);
            $fileName = $this->fileName('refunds', $from, $to);

            return $this->closeBatch($batch, $csvStr, $fileName, count($result['rows']), ['blocking_errors' => 0, 'warnings' => 0, 'issues' => []]);
        } catch (\Throwable $e) {
            return $this->failBatch($batch, $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // CSV-08: Full ZIP package (contacts + invoices + payments + credit notes + refunds + manifest)
    // -------------------------------------------------------------------------

    public function generateFullPackage(
        ?Carbon $from        = null,
        ?Carbon $to          = null,
        ?int    $generatedBy = null
    ): ExportBatch {
        $batch = $this->openBatch(ExportBatch::TYPE_ZIP, $from, $to, $generatedBy);

        try {
            // Validate all types before generating anything
            $contactsValidation = $this->validator->validateContacts();
            $invoicesValidation = $this->validator->validateInvoices($from, $to);
            $paymentsValidation = $this->validator->validatePayments($from, $to);

            $totalBlocking = $contactsValidation['blocking_errors']
                + $invoicesValidation['blocking_errors']
                + $paymentsValidation['blocking_errors'];

            if ($totalBlocking > 0) {
                return $this->failBatch($batch, "{$totalBlocking} blocking validation error(s) — fix before export.", [
                    'contacts' => $contactsValidation,
                    'invoices' => $invoicesValidation,
                    'payments' => $paymentsValidation,
                ]);
            }

            // Generate each CSV
            $contactsResult    = $this->contacts->export();
            $invoicesResult    = $this->invoices->export($from, $to);
            $paymentsResult    = $this->payments->export($from, $to);
            $creditNotesResult = $this->creditNotes->export($from, $to);
            $refundsResult     = $this->refunds->export($from, $to);

            $contactsCsv    = $this->contacts->toCsvString($contactsResult['rows']);
            $invoicesCsv    = $this->invoices->toCsvString($invoicesResult['rows']);
            $paymentsCsv    = $this->payments->toCsvString($paymentsResult['rows']);
            $creditNotesCsv = $this->creditNotes->toCsvString($creditNotesResult['rows']);
            $refundsCsv     = $this->refunds->toCsvString($refundsResult['rows']);

            $manifest = $this->buildManifest($batch, [
                'contacts'     => ['rows' => count($contactsResult['rows']),    'skipped' => count($contactsResult['skipped'])],
                'invoices'     => ['rows' => count($invoicesResult['rows']),    'skipped' => count($invoicesResult['skipped'])],
                'payments'     => ['rows' => count($paymentsResult['rows']),    'skipped' => count($paymentsResult['skipped'])],
                'credit_notes' => ['rows' => count($creditNotesResult['rows']), 'skipped' => count($creditNotesResult['skipped'])],
                'refunds'      => ['rows' => count($refundsResult['rows']),     'skipped' => count($refundsResult['skipped'])],
            ]);

            // Build ZIP
            $zipFileName = $this->fileName('odoo_import_package', $from, $to, 'zip');
            $zipPath     = self::EXPORT_DIR . '/' . $zipFileName;
            $tempZip     = storage_path('app/' . $zipPath);

            Storage::disk(self::DISK)->makeDirectory(self::EXPORT_DIR);

            $zip = new ZipArchive();
            if ($zip->open($tempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException("Cannot create ZIP archive at [{$tempZip}].");
            }

            // Import order (CSV-08): contacts → invoices → payments → credit notes → refunds
            $zip->addFromString('1_contacts.csv',    $contactsCsv);
            $zip->addFromString('2_invoices.csv',    $invoicesCsv);
            $zip->addFromString('3_payments.csv',    $paymentsCsv);
            $zip->addFromString('4_credit_notes.csv', $creditNotesCsv);
            $zip->addFromString('5_refunds.csv',     $refundsCsv);
            $zip->addFromString('manifest.json',     json_encode($manifest, JSON_PRETTY_PRINT));
            $zip->close();

            $totalRows = count($contactsResult['rows'])
                + count($invoicesResult['rows'])
                + count($paymentsResult['rows'])
                + count($creditNotesResult['rows'])
                + count($refundsResult['rows']);

            $checksum = hash_file('sha256', $tempZip);
            $fileSize = filesize($tempZip);

            $batch->update([
                'status'            => ExportBatch::STATUS_READY,
                'file_path'         => $zipPath,
                'file_name'         => $zipFileName,
                'file_checksum'     => $checksum,
                'file_size_bytes'   => $fileSize,
                'row_count'         => $totalRows,
                'validation_errors' => 0,
                'generated_at'      => now(),
            ]);

            return $batch->fresh();
        } catch (\Throwable $e) {
            return $this->failBatch($batch, $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function openBatch(
        string  $type,
        ?Carbon $from,
        ?Carbon $to,
        ?int    $generatedBy
    ): ExportBatch {
        return ExportBatch::create([
            'batch_id'     => $this->makeBatchId(),
            'export_type'  => $type,
            'status'       => ExportBatch::STATUS_GENERATING,
            'period_from'  => $from?->toDateString(),
            'period_to'    => $to?->toDateString(),
            'generated_by' => $generatedBy,
        ]);
    }

    private function closeBatch(
        ExportBatch $batch,
        string      $csvString,
        string      $fileName,
        int         $rowCount,
        array       $validation
    ): ExportBatch {
        $filePath = self::EXPORT_DIR . '/' . $fileName;

        Storage::disk(self::DISK)->makeDirectory(self::EXPORT_DIR);
        Storage::disk(self::DISK)->put($filePath, $csvString);

        $fullPath = storage_path('app/' . $filePath);
        $checksum = hash('sha256', $csvString);
        $fileSize = strlen($csvString);

        $batch->update([
            'status'            => ExportBatch::STATUS_READY,
            'file_path'         => $filePath,
            'file_name'         => $fileName,
            'file_checksum'     => $checksum,
            'file_size_bytes'   => $fileSize,
            'row_count'         => $rowCount,
            'validation_errors' => $validation['warnings'] ?? 0,
            'validation_report' => json_encode($validation['issues'] ?? []),
            'generated_at'      => now(),
        ]);

        return $batch->fresh();
    }

    private function failBatch(ExportBatch $batch, string $message, ?array $validation = null): ExportBatch
    {
        $batch->update([
            'status'            => ExportBatch::STATUS_FAILED,
            'error_message'     => $message,
            'validation_errors' => $validation ? ($validation['blocking_errors'] ?? 0) : 0,
            'validation_report' => $validation ? json_encode($validation['issues'] ?? $validation) : null,
            'generated_at'      => now(),
        ]);

        return $batch->fresh();
    }

    private function makeBatchId(): string
    {
        return 'CAG-EXPORT-' . now()->format('Ymd') . '-' . strtoupper(Str::random(8));
    }

    private function fileName(string $type, ?Carbon $from, ?Carbon $to, string $ext = 'csv'): string
    {
        $period = ($from && $to)
            ? $from->format('Ymd') . '_' . $to->format('Ymd')
            : now()->format('Ymd');

        return "coolagristock_{$type}_{$period}." . $ext;
    }

    private function buildManifest(ExportBatch $batch, array $summary): array
    {
        return [
            'batch_id'     => $batch->batch_id,
            'generated_at' => now()->toIso8601String(),
            'generated_by' => $batch->generated_by,
            'period_from'  => $batch->period_from?->toDateString(),
            'period_to'    => $batch->period_to?->toDateString(),
            'import_order' => ['1_contacts.csv', '2_invoices.csv', '3_payments.csv', '4_credit_notes.csv', '5_refunds.csv'],
            'summary'      => $summary,
            'note'         => 'Import files in numbered order. Contacts must exist in Odoo before invoices or payments are imported.',
        ];
    }
}
