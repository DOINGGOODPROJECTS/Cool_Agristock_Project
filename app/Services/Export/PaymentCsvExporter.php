<?php

namespace App\Services\Export;

use App\Models\Payment;
use App\Services\Odoo\OdooCustomerMapper;
use Carbon\Carbon;

/**
 * CSV-03: Exports COOL AGRISTOCK payments as Odoo account.payment import rows.
 *
 * Handles:
 *  - payment_received: linked to an existing invoice (bill_id present)
 *  - advance_payment:  no invoice yet (bill_id is null/zero)
 *
 * Import order: payments MUST be imported after invoices so that
 * invoice references resolve in Odoo.
 *
 * Columns follow the Odoo account.payment import template:
 *   id, partner_id/id, date, amount, journal_id/name,
 *   payment_method_line_id/name, ref, reconciled_invoice_ids/id
 */
class PaymentCsvExporter
{
    // Map COOL AGRISTOCK payment method strings to Odoo journal names.
    // Actual journal names must be confirmed after finance approval (Odoo Journals sheet).
    // Matches the actual SET enum in the payments table
    private const JOURNAL_MAP = [
        'cash'          => 'Cash',
        'CASH'          => 'Cash',
        'mobile money'  => 'Mobile Money',
        'MOBILE MONEY'  => 'Mobile Money',
        'mobile_money'  => 'Mobile Money',
        'credit card'   => 'Bank',
        'CREDIT CARD'   => 'Bank',
        'bank transfer' => 'Bank',
        'BANK TRANSFER' => 'Bank',
        'bank'          => 'Bank',
        'wallet'        => 'Customer Advances',
    ];

    private const DEFAULT_JOURNAL         = 'Cash';
    private const PAYMENT_METHOD_INBOUND  = 'Manual';

    public function __construct(private readonly OdooCustomerMapper $mapper) {}

    /**
     * Build payment CSV rows.
     *
     * @return array{rows: array, skipped: array}
     */
    public function export(
        ?Carbon $from       = null,
        ?Carbon $to         = null,
        ?array  $paymentIds = null
    ): array {
        $query = Payment::with(['customer', 'billing'])
            ->whereNull('deleted_at')
            ->where('amount', '>', 0);

        if ($from) {
            $query->where('payments.created_at', '>=', $from->startOfDay());
        }

        if ($to) {
            $query->where('payments.created_at', '<=', $to->endOfDay());
        }

        if ($paymentIds !== null) {
            $query->whereIn('payments.id', $paymentIds);
        }

        $payments = $query->get();
        $rows     = [];
        $skipped  = [];

        foreach ($payments as $payment) {
            $row = $this->buildRow($payment);

            if ($row === null) {
                $skipped[] = [
                    'payment_id' => $payment->id,
                    'reason'     => 'missing customer reference',
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
            'date',
            'amount',
            'journal_id/name',
            'payment_method_line_id/name',
            'ref',
            'reconciled_invoice_ids/id',
            'memo',
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

    private function buildRow(Payment $payment): ?array
    {
        $customer = $payment->customer;

        if (! $customer) {
            return null;
        }

        $isAdvance     = ! $payment->billing_id || $payment->billing_id === 0;
        $invoiceExtId  = $isAdvance ? '' : 'coolagristock.invoice.' . $payment->billing_id;
        $journal       = $this->resolveJournal($payment->method ?? 'CASH');
        $memo          = $isAdvance
            ? 'Advance payment — no invoice at time of receipt'
            : 'Payment for invoice CAG-INV-' . $payment->billing_id;

        return [
            'id'                          => 'coolagristock.payment.' . $payment->id,
            'partner_id/id'               => $this->mapper->externalId($customer->id),
            'date'                        => $payment->created_at->toDateString(),
            'amount'                      => round($payment->amount, 2),
            'journal_id/name'             => $journal,
            'payment_method_line_id/name' => self::PAYMENT_METHOD_INBOUND,
            'ref'                         => 'CAG-PAY-' . $payment->id,
            'reconciled_invoice_ids/id'   => $invoiceExtId,
            'memo'                        => $memo,
        ];
    }

    private function resolveJournal(string $method): string
    {
        return self::JOURNAL_MAP[strtolower($method)] ?? self::DEFAULT_JOURNAL;
    }
}
