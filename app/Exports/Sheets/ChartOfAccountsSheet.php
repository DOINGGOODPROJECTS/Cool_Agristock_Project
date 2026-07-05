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

class ChartOfAccountsSheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    public function title(): string
    {
        return 'Chart of Accounts';
    }

    public function headings(): array
    {
        return ['Account Key', 'Account Name', 'SYSCOHADA Code', 'SYSCOHADA Name', 'Type', 'Used In Event Types', 'Notes'];
    }

    public function array(): array
    {
        return [
            ['accounts_receivable',       'Accounts Receivable',        '411',    'Clients',                           'Asset',     'storage_fee, drying_fee, handling_fee, payment_received, credit_note', ''],
            ['cash',                       'Cash',                        '571',    'Caisse',                            'Asset',     'advance_payment, payment_received, refund', ''],
            ['mobile_money',               'Mobile Money',                '551',    'Mobile Money',                      'Asset',     'advance_payment, payment_received, refund', 'MTN, Orange, Wave'],
            ['bank',                       'Bank',                        '521',    'Banque',                            'Asset',     'advance_payment, payment_received, refund', ''],
            ['storage_revenue',            'Storage Revenue',             '706100', 'Prestations de services — stockage','Revenue',   'storage_fee', ''],
            ['drying_revenue',             'Drying Service Revenue',      '706200', 'Prestations de services — séchage', 'Revenue',   'drying_fee', ''],
            ['handling_revenue',           'Handling Revenue',            '706300', 'Prestations de services — manutention','Revenue','handling_fee', ''],
            ['customer_advances',          'Customer Advances',           '419',    'Clients créditeurs — avances',      'Liability', 'advance_payment', ''],
            ['customer_credit_liability',  'Customer Credit Liability',   '419',    'Clients créditeurs',                'Liability', 'credit_note, refund, claim, spoilage_cool_agristock_fault', ''],
            ['spoilage_loss',              'Spoilage Loss',               '641',    'Pertes sur stocks',                 'Expense',   'spoilage_cool_agristock_fault', ''],
            ['revenue_adjustment',         'Revenue Adjustment',          '709',    'Rabais, remises et ristournes',     'Revenue',   'credit_note', ''],
            ['vat_payable',                'VAT Payable',                 '441',    'État — TVA collectée',              'Liability', 'storage_fee, drying_fee, handling_fee', ''],
            ['claims_liability',           'Claims Liability',            'BLOCKED','—',                                 'Liability', 'claim, spoilage_cool_agristock_fault', 'Rejected by Finance — no replacement confirmed'],
            ['compensation_expense',       'Compensation Expense',        'BLOCKED','—',                                 'Expense',   'claim, spoilage_cool_agristock_fault', 'Rejected by Finance — no replacement confirmed'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $styles = [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1565C0']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];

        // Highlight BLOCKED rows in red
        foreach ([13, 14] as $row) {
            $styles[$row] = [
                'font' => ['color' => ['rgb' => 'B71C1C']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFEBEE']],
            ];
        }

        return $styles;
    }

    public function columnWidths(): array
    {
        return ['A' => 28, 'B' => 30, 'C' => 16, 'D' => 38, 'E' => 12, 'F' => 55, 'G' => 45];
    }
}
