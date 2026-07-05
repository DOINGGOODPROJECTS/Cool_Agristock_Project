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

class SyscohadaCodesSheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    public function title(): string
    {
        return 'SYSCOHADA Codes';
    }

    public function headings(): array
    {
        return ['Class', 'Code', 'Account Name (EN)', 'Account Name (FR)', 'Type', 'Cool Agristock Use'];
    }

    public function array(): array
    {
        return [
            // Class 4 — Third Parties
            ['4 — Third Parties', '411',    'Trade Receivables / Customers',    'Clients',                              'Asset',     'Amounts owed by farmers & buyers'],
            ['4 — Third Parties', '419',    'Customer Credit / Advances Received','Clients créditeurs — avances',        'Liability', 'Prepayments received before invoice'],
            ['4 — Third Parties', '441',    'Output VAT',                        'État — TVA collectée',                 'Liability', 'VAT on storage, drying, handling fees'],
            ['4 — Third Parties', '445',    'Input VAT (recoverable)',            'État — TVA déductible',                'Asset',     'VAT on business purchases'],
            ['4 — Third Parties', '471',    'Suspense / Transit Account',         'Compte d\'attente',                   'Neutral',   'Default fallback — fix before month end'],
            ['4 — Third Parties', '491',    'Provision for Bad Debts',            'Provision pour créances douteuses',   'Asset',     'Doubtful customer balances'],
            // Class 5 — Cash & Bank
            ['5 — Cash & Bank',   '521',    'Bank Account',                       'Banque',                              'Asset',     'Main bank account'],
            ['5 — Cash & Bank',   '551',    'Mobile Money',                       'Mobile Money',                        'Asset',     'MTN, Orange, Wave payments'],
            ['5 — Cash & Bank',   '571',    'Cash on Hand',                       'Caisse',                              'Asset',     'Petty cash / cash desk'],
            ['5 — Cash & Bank',   '581',    'Inter-account Transfers',            'Virements internes',                  'Neutral',   'Transfers between cash and bank'],
            // Class 6 — Expenses
            ['6 — Expenses',      '621',    'Staff Costs',                        'Personnel',                           'Expense',   'Salaries & wages'],
            ['6 — Expenses',      '624',    'Transport & Logistics',              'Transport',                           'Expense',   'Delivery & logistics costs'],
            ['6 — Expenses',      '632',    'Rent / Lease Expense',               'Loyers',                              'Expense',   'Warehouse lease if rented'],
            ['6 — Expenses',      '641',    'Spoilage & Stock Losses',            'Pertes sur stocks',                   'Expense',   'Damaged or destroyed grain write-off'],
            ['6 — Expenses',      '651',    'Insurance',                          'Assurances',                          'Expense',   'Warehouse insurance'],
            ['6 — Expenses',      '681',    'Depreciation',                       'Dotations aux amortissements',        'Expense',   'Warehouse & equipment depreciation'],
            // Class 7 — Revenue
            ['7 — Revenue',       '706',    'Service Revenue',                    'Prestations de services',             'Revenue',   'All service fees'],
            ['7 — Revenue',       '706100', 'Storage Fee Revenue',                'Revenus — stockage',                  'Revenue',   'Monthly storage charges ← PRIMARY'],
            ['7 — Revenue',       '706200', 'Drying Service Revenue',             'Revenus — séchage',                   'Revenue',   'Drying service charges'],
            ['7 — Revenue',       '706300', 'Handling Revenue',                   'Revenus — manutention',               'Revenue',   'Loading, unloading, sorting'],
            ['7 — Revenue',       '709',    'Discounts & Rebates Given',          'Rabais, remises et ristournes',        'Revenue',   'Credit notes issued to customers'],
            ['7 — Revenue',       '771',    'Interest Income',                    'Revenus financiers',                  'Revenue',   'Interest on deposits'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $groupColors = [
            '4 — Third Parties' => 'E3F2FD',
            '5 — Cash & Bank'   => 'E8F5E9',
            '6 — Expenses'      => 'FFF3E0',
            '7 — Revenue'       => 'F3E5F5',
        ];

        $styles = [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4A148C']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];

        $data = $this->array();
        foreach ($data as $i => $row) {
            $rowNum = $i + 2;
            $class = $row[0];
            $color = $groupColors[$class] ?? 'FFFFFF';
            $styles[$rowNum] = [
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
            ];
        }

        return $styles;
    }

    public function columnWidths(): array
    {
        return ['A' => 20, 'B' => 10, 'C' => 35, 'D' => 38, 'E' => 12, 'F' => 45];
    }
}
