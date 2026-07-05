<?php

namespace App\Services\Export;

use App\Models\FinancialEvent;
use App\Services\Odoo\OdooCustomerMapper;
use Carbon\Carbon;

/**
 * CSV-05: Exports approved refund financial events as Odoo account.payment
 * (outbound / refund payment) import rows.
 *
 * Only exports events with:
 *   - event_type      = 'refund'
 *   - accounting_status = 'export_ready'
 *   - approval_status   = 'approved'
 *
 * Import order: refunds MUST be imported after credit notes so that
 * the customer credit balance reference exists in Odoo.
 *
 * Columns follow the Odoo account.payment import template for outbound payments.
 */
class RefundCsvExporter
{
    // Payment channel → Odoo journal name mapping (outbound direction)
    private const JOURNAL_MAP = [
        'cash'          => 'Cash',
        'CASH'          => 'Cash',
        'mobile money'  => 'Mobile Money',
        'MOBILE MONEY'  => 'Mobile Money',
        'mobile_money'  => 'Mobile Money',
        'bank transfer' => 'Bank',
        'BANK TRANSFER' => 'Bank',
        'credit card'   => 'Bank',
        'CREDIT CARD'   => 'Bank',
        'bank'          => 'Bank',
    ];

    private const DEFAULT_JOURNAL        = 'Cash';
    private const PAYMENT_METHOD_OUTBOUND = 'Manual';

    public function __construct(private readonly OdooCustomerMapper $mapper) {}

    /**
     * @return array{rows: array, skipped: array}
     */
    public function export(
        ?Carbon $from      = null,
        ?Carbon $to        = null,
        ?array  $eventIds  = null
    ): array {
        $query = FinancialEvent::where('event_type', 'refund')
            ->where('accounting_status', FinancialEvent::STATUS_EXPORT_READY)
            ->where('approval_status',   FinancialEvent::APPROVAL_APPROVED);

        if ($from) {
            $query->where('event_date', '>=', $from->toDateString());
        }
        if ($to) {
            $query->where('event_date', '<=', $to->toDateString());
        }
        if ($eventIds !== null) {
            $query->whereIn('id', $eventIds);
        }

        $events  = $query->get();
        $rows    = [];
        $skipped = [];

        foreach ($events as $event) {
            $row = $this->buildRow($event);

            if ($row === null) {
                $skipped[] = [
                    'financial_event_id' => $event->financial_event_id,
                    'reason'             => 'missing customer_id',
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
            'payment_type',
            'date',
            'amount',
            'journal_id/name',
            'payment_method_line_id/name',
            'ref',
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

    private function buildRow(FinancialEvent $event): ?array
    {
        if (! $event->customer_id) {
            return null;
        }

        $journal = self::JOURNAL_MAP[$event->service_type ?? '']
            ?? self::DEFAULT_JOURNAL;

        $memo = $event->notes
            ?? "Refund to customer — approved credit balance ({$event->financial_event_id})";

        return [
            'id'                          => 'coolagristock.refund.' . $event->id,
            'partner_id/id'               => $this->mapper->externalId($event->customer_id),
            'payment_type'                => 'outbound',
            'date'                        => $event->event_date->toDateString(),
            'amount'                      => round($event->amount, 2),
            'journal_id/name'             => $journal,
            'payment_method_line_id/name' => self::PAYMENT_METHOD_OUTBOUND,
            'ref'                         => $event->financial_event_id,
            'memo'                        => $memo,
        ];
    }
}
