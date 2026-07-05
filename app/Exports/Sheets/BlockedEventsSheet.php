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

class BlockedEventsSheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    public function title(): string
    {
        return 'Blocked Events';
    }

    public function headings(): array
    {
        return ['Account Key', 'Account Name', 'Reason Blocked', 'Affected Event Types', 'Action Required'];
    }

    public function array(): array
    {
        return [
            [
                'claims_liability',
                'Claims Liability',
                'Rejected by Finance — replacement account not yet confirmed.',
                'claim, spoilage_cool_agristock_fault',
                'Finance must confirm the correct SYSCOHADA account code and update config/accounting_events.php',
            ],
            [
                'compensation_expense',
                'Compensation Expense',
                'Rejected by Finance — replacement account not yet confirmed.',
                'claim, spoilage_cool_agristock_fault',
                'Finance must confirm the correct SYSCOHADA account code and update config/accounting_events.php',
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B71C1C']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFEBEE']]],
            3 => ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFEBEE']]],
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 25, 'B' => 28, 'C' => 55, 'D' => 40, 'E' => 60];
    }
}
