<?php

namespace App\Services\Accounting;

use App\Models\Billing;
use App\Models\Invoice;

class InvoiceGeneratorService
{
    public function generateFromBilling(Billing $billing, int $generatedBy): Invoice
    {
        $existing = Invoice::where('billing_id', $billing->id)
            ->whereNotIn('status', ['cancelled'])
            ->first();

        if ($existing) {
            return $existing;
        }

        $subtotal = (float) $billing->amount;

        return Invoice::create([
            'invoice_number' => Invoice::nextInvoiceNumber(),
            'billing_id'     => $billing->id,
            'customer_id'    => $billing->customer_id,
            'invoice_date'   => now()->toDateString(),
            'due_date'       => now()->addDays(30)->toDateString(),
            'subtotal'       => $subtotal,
            'tax_amount'     => 0,
            'total_amount'   => $subtotal,
            'currency'       => 'XOF',
            'status'         => 'issued',
            'generated_by'   => $generatedBy,
        ]);
    }

    public function cancel(Invoice $invoice): void
    {
        if ($invoice->status === 'paid') {
            throw new \RuntimeException('Cannot cancel a paid invoice.');
        }

        $invoice->update(['status' => 'cancelled']);
    }

    public function markPaid(Invoice $invoice): void
    {
        $invoice->update(['status' => 'paid']);
    }
}
