<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class EventMappingSheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    public function title(): string
    {
        return 'Event Mapping';
    }

    public function headings(): array
    {
        return [
            'Event Type',
            'Label',
            'Creates Entry?',
            'Debit Account(s)',
            'Credit Account(s)',
            'SYSCOHADA Debit Code',
            'SYSCOHADA Credit Code',
            'Odoo Model',
            'Odoo Object',
            'Finance Approval?',
            'Blocked from Export?',
        ];
    }

    public function array(): array
    {
        $events = config('accounting_events.event_types', []);
        $blocked = config('accounting_events.export_blocked_event_types', []);
        $financeRequired = config('accounting_events.policies.finance_approval.required_for', []);

        $syscohadaMap = [
            'accounts_receivable'       => '411',
            'cash'                      => '571',
            'mobile_money'              => '551',
            'bank'                      => '521',
            'storage_revenue'           => '706100',
            'drying_revenue'            => '706200',
            'handling_revenue'          => '706300',
            'customer_advances'         => '419',
            'customer_credit_liability' => '419',
            'spoilage_loss'             => '641',
            'revenue_adjustment'        => '709',
            'vat_payable'               => '441',
            'claims_liability'          => 'BLOCKED',
            'compensation_expense'      => 'BLOCKED',
        ];

        $rows = [];

        foreach ($events as $key => $event) {
            $debitAccounts  = is_array($event['debit'])  ? implode(' / ', $event['debit'])  : ($event['debit']  ?? '—');
            $creditAccounts = is_array($event['credit']) ? implode(' / ', $event['credit']) : ($event['credit'] ?? '—');

            $debitCodes  = $this->resolveCodes($event['debit'],  $syscohadaMap);
            $creditCodes = $this->resolveCodes($event['credit'], $syscohadaMap);

            $rows[] = [
                $key,
                $event['label'],
                $event['creates_accounting_entry'] ? 'Yes' : 'No',
                $debitAccounts,
                $creditAccounts,
                $debitCodes,
                $creditCodes,
                $event['odoo_model'] ?? '—',
                $event['odoo_object'] ?? '—',
                in_array($key, $financeRequired) ? 'Yes' : 'No',
                in_array($key, $blocked) ? '⛔ BLOCKED' : '',
            ];
        }

        return $rows;
    }

    private function resolveCodes(mixed $accounts, array $map): string
    {
        if (is_null($accounts)) return '—';
        $list = is_array($accounts) ? $accounts : [$accounts];
        $codes = array_map(fn($a) => $map[$a] ?? '?', $list);
        return implode(' / ', $codes);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E7D32']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30, 'B' => 30, 'C' => 15, 'D' => 40,
            'E' => 40, 'F' => 25, 'G' => 25, 'H' => 20,
            'I' => 30, 'J' => 18, 'K' => 18,
        ];
    }
}
