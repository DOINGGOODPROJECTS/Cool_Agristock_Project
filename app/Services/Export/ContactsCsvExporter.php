<?php

namespace App\Services\Export;

use App\Models\User;
use App\Services\Odoo\OdooCustomerMapper;
use Illuminate\Support\Collection;

/**
 * CSV-01: Exports COOL AGRISTOCK customers as Odoo res.partner import rows.
 *
 * Import order: contacts MUST be imported before invoices or payments
 * so that partner_id references resolve correctly in Odoo.
 */
class ContactsCsvExporter
{
    public function __construct(private readonly OdooCustomerMapper $mapper) {}

    /**
     * Build CSV rows for all active customers (farmers, cooperatives, companies).
     * Skips records with no name and flags phone duplicates in the report.
     *
     * @return array{rows: array, skipped: array, warnings: array}
     */
    public function export(?array $customerIds = null): array
    {
        $query = User::with('group')
            ->whereNull('deleted_at');

        if ($customerIds !== null) {
            $query->whereIn('id', $customerIds);
        }

        $users    = $query->get();
        $rows     = [];
        $skipped  = [];
        $warnings = [];

        foreach ($users as $user) {
            $partner = $this->mapper->toPartner($user);

            if ($partner === null) {
                $skipped[] = [
                    'id'     => $user->id,
                    'reason' => 'missing name',
                ];
                continue;
            }

            $signals = $this->mapper->detectDuplicates($user);

            if (! empty($signals)) {
                $warnings[] = [
                    'id'      => $user->id,
                    'name'    => $user->name,
                    'signals' => $signals,
                ];
            }

            $rows[] = $this->mapper->toCsvRow($partner);
        }

        return compact('rows', 'skipped', 'warnings');
    }

    public function headers(): array
    {
        return $this->mapper->csvHeaders();
    }

    /**
     * Render rows to a CSV string ready to write to a file.
     */
    public function toCsvString(array $rows): string
    {
        if (empty($rows)) {
            return implode(',', $this->headers()) . "\n";
        }

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
}
