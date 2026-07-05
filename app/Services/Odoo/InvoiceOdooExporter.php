<?php

namespace App\Services\Odoo;

use App\Models\Invoice;
use RuntimeException;
use Illuminate\Support\Facades\Log;

/**
 * Pushes an approved invoice to Odoo as an out_invoice account.move.
 *
 * Flow:
 *   1. Resolve customer → Odoo partner_id (search by name/email, create if missing)
 *   2. Build invoice line_ids from lines where send_to_odoo = 'Yes'
 *   3. Create account.move (out_invoice) and post it
 *   4. Update local invoice: odoo_invoice_id, odoo_invoice_name, odoo_status
 */
class InvoiceOdooExporter
{
    private array $partnerCache = [];
    private array $accountCache = [];

    public function __construct(private readonly OdooApiClient $odoo) {}

    public function push(Invoice $invoice): void
    {
        $invoice->load('lines', 'customer');

        $lines = $invoice->lines->filter(fn ($l) => $l->send_to_odoo === 'Yes');

        if ($lines->isEmpty()) {
            throw new RuntimeException('No lines marked "Yes" for Odoo on this invoice.');
        }

        $partnerId  = $this->resolvePartner($invoice);
        $accountId  = $this->resolveRevenueAccount();
        $lineIds    = $lines->map(fn ($line) => $this->buildOdooLine($line, $accountId))->values()->toArray();

        try {
            $moveId = $this->odoo->create('account.move', [
                'move_type'          => 'out_invoice',
                'partner_id'         => $partnerId,
                'invoice_date'       => $invoice->invoice_date->toDateString(),
                'invoice_date_due'   => $invoice->due_date?->toDateString(),
                'ref'                => $invoice->invoice_number,
                'narration'          => $invoice->notes,
                'invoice_line_ids'   => $lineIds,
            ]);

            $this->odoo->callAction('account.move', [$moveId], 'action_post');

            $moveInfo = $this->odoo->searchRead('account.move', [['id', '=', $moveId]], ['name'], 1);
            $moveName = $moveInfo[0]['name'] ?? null;

            $invoice->update([
                'odoo_invoice_id'   => $moveId,
                'odoo_invoice_name' => $moveName,
                'odoo_status'       => 'exported',
                'odoo_push_error'   => null,
            ]);

            Log::info("[Odoo] Invoice {$invoice->invoice_number} pushed as {$moveName} (move id={$moveId})");

        } catch (\Throwable $e) {
            $invoice->update(['odoo_push_error' => $e->getMessage()]);
            Log::error("[Odoo] Invoice push failed for {$invoice->invoice_number}: " . $e->getMessage());
            throw $e;
        }
    }

    // -------------------------------------------------------------------------

    private function resolvePartner(Invoice $invoice): int
    {
        // Prefer the linked user record; fall back to typed customer_name
        $name  = $invoice->customer?->name ?? $invoice->customer_name;
        $email = $invoice->customer?->email;
        $phone = $invoice->customer?->phone;

        if (! $name) {
            throw new RuntimeException('Invoice has no customer name — cannot resolve Odoo partner.');
        }

        $cacheKey = strtolower($name);
        if (isset($this->partnerCache[$cacheKey])) {
            return $this->partnerCache[$cacheKey];
        }

        // 1. Exact name match
        $results = $this->odoo->searchRead('res.partner', [['name', '=', $name]], ['id', 'name'], 1);

        // 2. Email match fallback
        if (empty($results) && $email) {
            $results = $this->odoo->searchRead('res.partner', [['email', '=', $email]], ['id', 'name'], 1);
        }

        // 3. Create partner if not found
        if (empty($results)) {
            $partnerId = $this->odoo->create('res.partner', array_filter([
                'name'          => $name,
                'email'         => $email,
                'phone'         => $phone,
                'customer_rank' => 1,
            ]));
            Log::info("[Odoo] Created new partner '{$name}' → id={$partnerId}");
        } else {
            $partnerId = $results[0]['id'];
            Log::info("[Odoo] Resolved partner '{$name}' → id={$partnerId}");
        }

        $this->partnerCache[$cacheKey] = $partnerId;
        return $partnerId;
    }

    private function resolveRevenueAccount(): int
    {
        $code = config('odoo.accounts.invoice_revenue');

        if (! $code) {
            throw new RuntimeException('odoo.accounts.invoice_revenue is not configured.');
        }

        if (isset($this->accountCache[$code])) {
            return $this->accountCache[$code];
        }

        $results = $this->odoo->searchRead('account.account', [['code', '=', $code]], ['id', 'code', 'name'], 1);

        if (empty($results)) {
            throw new RuntimeException("Invoice revenue account '{$code}' not found in Odoo chart of accounts.");
        }

        $this->accountCache[$code] = $results[0]['id'];
        Log::info("[Odoo] Invoice revenue account '{$code}' → id={$results[0]['id']}");

        return $results[0]['id'];
    }

    private function buildOdooLine(\App\Models\InvoiceLine $line, int $accountId): array
    {
        $qty       = (float) $line->quantity;
        $unitPrice = (float) $line->unit_price;
        $gross     = $qty * $unitPrice;

        // Convert fixed discount → percentage (Odoo uses %)
        $discountPct = ($gross > 0) ? min(round(((float) $line->discount_fixed_amount / $gross) * 100, 4), 100) : 0;

        $name = implode(' — ', array_filter([
            $line->service,
            $line->product,
            $line->description,
        ]));

        return [0, 0, [
            'name'       => $name ?: 'Service',
            'quantity'   => $qty,
            'price_unit' => $unitPrice,
            'discount'   => $discountPct,
            'account_id' => $accountId,
        ]];
    }
}
