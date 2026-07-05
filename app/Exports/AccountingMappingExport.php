<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AccountingMappingExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Event Mapping'       => new Sheets\EventMappingSheet(),
            'Chart of Accounts'   => new Sheets\ChartOfAccountsSheet(),
            'SYSCOHADA Codes'     => new Sheets\SyscohadaCodesSheet(),
            'Blocked Events'      => new Sheets\BlockedEventsSheet(),
        ];
    }
}
