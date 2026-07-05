<?php

namespace App\Services\Export;

use App\Models\Billing;
use App\Services\Odoo\OdooCustomerMapper;
use Carbon\Carbon;

/**
 * CSV-02: Exports COOL AGRISTOCK billings as Odoo account.move (customer invoice) import rows.
 *
 * Each billing record maps to one invoice with one service line.
 * Service type is resolved from the billing's stock context.
 *
 * Import order: invoices MUST be imported after contacts.
 *
 * Columns follow the Odoo account.move import template:
 *   id, partner_id/id, invoice_date, invoice_date_due, journal_id/name,
 *   invoice_line_ids/product_id/name, invoice_line_ids/quantity,
 *   invoice_line_ids/price_unit, invoice_line_ids/tax_ids/id,
 *   invoice_line_ids/name, ref, narration
 */
class InvoiceCsvExporter
{
    // Placeholder service product names — replaced with real Odoo product names after finance confirms
    private const PRODUCT_STORAGE  = '[CAG] Storage Fee';
    private const PRODUCT_DRYING   = '[CAG] Solar Drying Service';
    private const PRODUCT_HANDLING = '[CAG] Handling Service';
    private const DEFAULT_JOURNAL  = 'Customer Invoices';
    private const PAYMENT_TERM     = '30 days';

    public function __construct(private readonly OdooCustomerMapper $mapper) {}

    /**
     * Build invoice CSV rows from billings in the given date range.
     *
     * @return array{rows: array, skipped: array}
     */
    public function export(
        ?Carbon $from = null,
        ?Carbon $to   = null,
        ?array  $billingIds = null
    ): array {
        $query = Billing::with(['stock.details.product', 'stock.customer'])
            ->whereNull('deleted_at')
            ->where('amount', '>', 0);

        if ($from) {
            $query->where('billings.created_at', '>=', $from->startOfDay());
        }

        if ($to) {
            $query->where('billings.created_at', '<=', $to->endOfDay());
        }

        if ($billingIds !== null) {
            $query->whereIn('billings.id', $billingIds);
        }

        $billings = $query->get();
        $rows     = [];
        $skipped  = [];

        foreach ($billings as $billing) {
            $row = $this->buildRow($billing);

            if ($row === null) {
                $skipped[] = [
                    'billing_id' => $billing->id,
                    'reason'     => 'missing customer or stock reference',
                ];
                continue;
            }

            $rows[] = $row;
        }

        return compact('rows', 'skipped');
    }

    public function headers(): array
    {
        return [
            'id',
            'partner_id/id',
            'invoice_date',
            'invoice_date_due',
            'journal_id/name',
            'payment_reference',
            'invoice_line_ids/product_id/name',
            'invoice_line_ids/quantity',
            'invoice_line_ids/price_unit',
            'invoice_line_ids/discount',
            'invoice_line_ids/tax_ids/id',
            'invoice_line_ids/name',
            'ref',
            'narration',
        ];
    }

    public function toCsvString(array $rows): string
    {
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $this->headers());

        foreach ($rows as $row) {
            fputcsv($output, array_values($row));
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    // -------------------------------------------------------------------------

    private function buildRow(Billing $billing): ?array
    {
        $stock    = $billing->stock;
        $customer = $stock?->customer ?? \App\Models\User::find($billing->customer_id);

        if (! $customer || ! $stock) {
            return null;
        }

        $customerId  = $customer->id;
        $invoiceDate = $billing->created_at->toDateString();
        $dueDate     = $billing->delayed_at
            ? $billing->delayed_at->toDateString()
            : $billing->created_at->addDays(30)->toDateString();

        $productName = $this->resolveProductName($billing);
        $description = $this->resolveDescription($billing, $stock);

        return [
            'id'                               => 'coolagristock.invoice.' . $billing->id,
            'partner_id/id'                    => $this->mapper->externalId($customerId),
            'invoice_date'                     => $invoiceDate,
            'invoice_date_due'                 => $dueDate,
            'journal_id/name'                  => self::DEFAULT_JOURNAL,
            'payment_reference'                => 'CAG-INV-' . $billing->id,
            'invoice_line_ids/product_id/name' => $productName,
            'invoice_line_ids/quantity'        => 1,
            'invoice_line_ids/price_unit'      => round($billing->amount, 2),
            'invoice_line_ids/discount'        => round($billing->discount ?? 0, 2),
            'invoice_line_ids/tax_ids/id'      => '',   // filled after VAT approval
            'invoice_line_ids/name'            => $description,
            'ref'                              => 'CAG-BILLING-' . $billing->id,
            'narration'                        => 'Stock #' . $stock->id . ' — generated by COOL AGRISTOCK',
        ];
    }

    private function resolveProductName(Billing $billing): string
    {
        // service_type on billing — fall back to storage fee as default
        return match ($billing->service_type ?? 'storage_fee') {
            'drying_fee'   => self::PRODUCT_DRYING,
            'handling_fee' => self::PRODUCT_HANDLING,
            default        => self::PRODUCT_STORAGE,
        };
    }

    private function resolveDescription(Billing $billing, $stock): string
    {
        $qty     = $stock->qty ?? 0;
        $product = $stock->details->first()?->product?->name ?? 'produce';

        return "Storage: {$product}, {$qty} kg (Stock #{$stock->id})";
    }
}
