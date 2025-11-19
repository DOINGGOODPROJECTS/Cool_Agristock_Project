<?php

namespace App\Http\Controllers;

use Codedge\Fpdf\Fpdf\Fpdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class ExportController extends Controller
{
    /**
     * Tables we do not want to surface in the export UI.
     */
    private array $ignoredTables = [
        'migrations',
        'failed_jobs',
        'password_resets',
        'password_reset_tokens',
        'personal_access_tokens',
        'cache',
        'cache_locks',
        'sessions',
        'jobs',
        'job_batches',
    ];

    /**
     * Prettified column mappings (with joins) per table.
     */
    private array $tableConfigs = [
        'users' => [
            'label' => 'customers',
            'select' => [
                ['users.id', 'id'],
                ['groups.name', 'category'],
                ['users.name', 'company'],
                ['users.username', 'username'],
                ['users.phone', 'phone'],
                ['users.email', 'email'],
                ['users.language', 'language'],
                ['users.created_at', 'created_at'],
            ],
            'joins' => [
                ['left', 'groups', 'groups.id', '=', 'users.group_id'],
            ],
            'where' => [
                ['users.deleted_at', '=', null],
            ],
            'headers' => [
                'id' => '#',
                'category' => 'Category',
                'company' => 'Company',
                'username' => 'Username',
                'phone' => 'Phone',
                'email' => 'Email',
                'language' => 'Language',
                'created_at' => 'Created At',
            ],
        ],
        'categories' => [
            'select' => [
                ['id', 'id'],
                ['name', 'name'],
            ],
            'headers' => [
                'id' => '#',
                'name' => 'Name',
            ],
        ],
        'storages' => [
            'select' => [
                ['id', 'id'],
                ['name', 'name'],
                ['capacity', 'capacity'],
                ['location', 'location'],
            ],
            'headers' => [
                'id' => '#',
                'name' => 'Name',
                'capacity' => 'Capacity',
                'location' => 'Location',
            ],
        ],
        'products' => [
            'select' => [
                ['products.id', 'id'],
                ['products.name', 'product'],
                ['categories.name', 'category'],
                ['products.min_expired_at', 'min_days'],
                ['products.max_expired_at', 'max_days'],
            ],
            'joins' => [
                ['left', 'categories', 'categories.id', '=', 'products.category_id'],
            ],
            'headers' => [
                'id' => '#',
                'product' => 'Product',
                'category' => 'Category',
                'min_days' => 'Min Expiration (days)',
                'max_days' => 'Max Expiration (days)',
            ],
        ],
        'cities' => [
            'select' => [
                ['id', 'id'],
                ['name', 'name'],
            ],
            'headers' => [
                'id' => '#',
                'name' => 'Name',
            ],
        ],
        'groups' => [
            'select' => [
                ['id', 'id'],
                ['name', 'name'],
            ],
            'headers' => [
                'id' => '#',
                'name' => 'Name',
            ],
        ],
        'stocks' => [
            'select' => [
                ['stocks.id', 'id'],
                ['stocks.ref', 'reference'],
                ['users.name', 'customer'],
                ['storages.name', 'storage'],
                ['stocks.type_storage', 'storage_type'],
                ['stocks.qty', 'quantity'],
                ['stocks.expired_at', 'expires_in_days'],
                ['stocks.created_at', 'created_at'],
            ],
            'joins' => [
                ['left', 'users', 'users.id', '=', 'stocks.customer_id'],
                ['left', 'storages', 'storages.id', '=', 'stocks.storage_id'],
            ],
            'headers' => [
                'id' => '#',
                'reference' => 'Reference',
                'customer' => 'Customer',
                'storage' => 'Storage',
                'storage_type' => 'Storage Type',
                'quantity' => 'Quantity (kg)',
                'expires_in_days' => 'Expires In (days)',
                'created_at' => 'Created At',
            ],
        ],
        'billings' => [
            'select' => [
                ['billings.id', 'id'],
                ['billings.ref', 'reference'],
                ['users.name', 'customer'],
                ['billings.amount', 'amount'],
                ['billings.discount', 'discount'],
                ['billings.created_at', 'created_at'],
            ],
            'joins' => [
                ['left', 'users', 'users.id', '=', 'billings.customer_id'],
            ],
            'headers' => [
                'id' => '#',
                'reference' => 'Reference',
                'customer' => 'Customer',
                'amount' => 'Amount',
                'discount' => 'Discount',
                'created_at' => 'Created At',
            ],
        ],
        'payments' => [
            'select' => [
                ['payments.id', 'id'],
                ['users.name', 'customer'],
                ['payments.amount', 'amount'],
                ['payments.method', 'method'],
                ['payments.location', 'location'],
                ['payments.created_at', 'created_at'],
            ],
            'joins' => [
                ['left', 'users', 'users.id', '=', 'payments.customer_id'],
            ],
            'headers' => [
                'id' => '#',
                'customer' => 'Customer',
                'amount' => 'Amount',
                'method' => 'Method',
                'location' => 'Location',
                'created_at' => 'Created At',
            ],
        ],
        'activities' => [
            'select' => [
                ['activities.id', 'id'],
                ['users.name', 'user'],
                ['activities.description', 'description'],
                ['activities.login_at', 'login_at'],
                ['activities.logout_at', 'logout_at'],
            ],
            'joins' => [
                ['left', 'users', 'users.id', '=', 'activities.user_id'],
            ],
            'headers' => [
                'id' => '#',
                'user' => 'User',
                'description' => 'Description',
                'login_at' => 'Login At',
                'logout_at' => 'Logout At',
            ],
        ],
        'releases' => [
            'select' => [
                ['releases.id', 'id'],
                ['stocks.ref', 'reference'],
                ['users.name', 'customer'],
                ['releases.before_qty', 'before_qty'],
                ['releases.qty', 'quantity'],
                ['releases.after_qty', 'after_qty'],
                ['releases.delivery', 'delivery'],
                ['releases.created_at', 'created_at'],
            ],
            'joins' => [
                ['left', 'stocks', 'stocks.id', '=', 'releases.stock_id'],
                ['left', 'users', 'users.id', '=', 'stocks.customer_id'],
            ],
            'headers' => [
                'id' => '#',
                'reference' => 'Reference',
                'customer' => 'Customer',
                'before_qty' => 'Before Qty',
                'quantity' => 'Quantity (kg)',
                'after_qty' => 'After Qty',
                'delivery' => 'Delivery',
                'created_at' => 'Created At',
            ],
        ],
        'rottens' => [
            'select' => [
                ['rottens.id', 'id'],
                ['stocks.ref', 'reference'],
                ['users.name', 'customer'],
                ['rottens.before_qty', 'before_qty'],
                ['rottens.qty', 'quantity'],
                ['rottens.after_qty', 'after_qty'],
                ['rottens.created_at', 'created_at'],
            ],
            'joins' => [
                ['left', 'stocks', 'stocks.id', '=', 'rottens.stock_id'],
                ['left', 'users', 'users.id', '=', 'stocks.customer_id'],
            ],
            'headers' => [
                'id' => '#',
                'reference' => 'Reference',
                'customer' => 'Customer',
                'before_qty' => 'Before Qty',
                'quantity' => 'Quantity (kg)',
                'after_qty' => 'After Qty',
                'created_at' => 'Created At',
            ],
        ],
        'details' => [
            'select' => [
                ['details.id', 'id'],
                ['stocks.ref', 'reference'],
                ['products.name', 'product'],
                ['capacities.name', 'container'],
                ['details.qty', 'quantity'],
                ['details.created_at', 'created_at'],
            ],
            'joins' => [
                ['left', 'stocks', 'stocks.id', '=', 'details.stock_id'],
                ['left', 'products', 'products.id', '=', 'details.product_id'],
                ['left', 'capacities', 'capacities.id', '=', 'details.container_id'],
            ],
            'headers' => [
                'id' => '#',
                'reference' => 'Reference',
                'product' => 'Product',
                'container' => 'Container',
                'quantity' => 'Quantity',
                'created_at' => 'Created At',
            ],
        ],
        'capacities' => [
            'select' => [
                ['id', 'id'],
                ['name', 'name'],
            ],
            'headers' => [
                'id' => '#',
                'name' => 'Name',
            ],
        ],
        'tariffs' => [
            'select' => [
                ['tariffs.id', 'id'],
                ['tariffs.price', 'price_from'],
                ['tariffs.min_qty', 'min_qty'],
                ['tariffs.max_qty', 'max_qty'],
                ['tariffs.duration', 'duration_days'],
            ],
            'headers' => [
                'id' => '#',
                'price_from' => 'Price From',
                'min_qty' => 'Min Qty',
                'max_qty' => 'Max Qty',
                'duration_days' => 'Duration (days)',
            ],
        ],
        'temperatures' => [
            'select' => [
                ['temperatures.id', 'id'],
                ['temperatures.type_storage', 'storage_type'],
                ['temperatures.session', 'session'],
                ['temperatures.degree', 'degree'],
                ['temperatures.session_time', 'time'],
                ['storages.name', 'storage'],
                ['temperatures.created_at', 'created_at'],
            ],
            'joins' => [
                ['left', 'storages', 'storages.id', '=', 'temperatures.storage_id'],
            ],
            'headers' => [
                'id' => '#',
                'storage_type' => 'Storage Type',
                'session' => 'Session',
                'degree' => 'Degree',
                'time' => 'Time',
                'storage' => 'Storage',
                'created_at' => 'Created At',
            ],
        ],
        'incidents' => [
            'select' => [
                ['id', 'id'],
                ['type', 'type'],
                ['status', 'status'],
                ['description', 'description'],
                ['created_at', 'created_at'],
            ],
            'headers' => [
                'id' => '#',
                'type' => 'Type',
                'status' => 'Status',
                'description' => 'Description',
                'created_at' => 'Created At',
            ],
        ],
        'claims' => [
            'select' => [
                ['claims.id', 'id'],
                ['claims.name', 'type'],
                ['claims.status', 'status'],
                ['users.name', 'customer'],
                ['storages.name', 'storage'],
                ['claims.created_at', 'created_at'],
            ],
            'joins' => [
                ['left', 'users', 'users.id', '=', 'claims.customer_id'],
                ['left', 'storages', 'storages.id', '=', 'claims.storage_id'],
            ],
            'headers' => [
                'id' => '#',
                'type' => 'Type',
                'status' => 'Status',
                'customer' => 'Customer',
                'storage' => 'Storage',
                'created_at' => 'Created At',
            ],
        ],
    ];

    public function index(Request $request)
    {
        $tables = $this->availableTables();

        if ($tables->isEmpty()) {
            return view('exports.individual', [
                'tables' => $tables,
                'activeTable' => null,
                'columns' => [],
                'columnLabels' => [],
                'rows' => collect(),
                'hasId' => false,
            ]);
        }

        $activeTable = $request->get('table', $tables->first());

        if (! $tables->contains($activeTable)) {
            $activeTable = $tables->first();
        }

        $columns = $this->columnsFor($activeTable);
        $columnLabels = $this->headersFor($activeTable, $columns);
        $rows = $this->rowsFor($activeTable, $columns, [], false);
        $hasId = in_array('id', $columns, true);

        return view('exports.individual', compact('tables', 'activeTable', 'columns', 'columnLabels', 'rows', 'hasId'));
    }

    public function export(Request $request)
    {
        $request->validate([
            'table' => 'required|string',
            'format' => 'required|in:csv,pdf,excel',
            'rows' => 'array',
            'rows.*' => 'string',
        ]);

        $table = $request->input('table');
        $format = $request->input('format', 'csv');
        $selectedRows = $request->input('rows', []);

        $tables = $this->availableTables();
        abort_unless($tables->contains($table), 404);

        $columns = $this->columnsFor($table);
        $columnLabels = $this->headersFor($table, $columns);
        $rows = $this->rowsFor($table, $columns, $selectedRows, true);

        return match ($format) {
            'pdf' => $this->exportPdf($table, $columns, $columnLabels, $rows),
            'excel' => $this->exportCsv($table, $columns, $columnLabels, $rows, 'xls', 'application/vnd.ms-excel'),
            default => $this->exportCsv($table, $columns, $columnLabels, $rows),
        };
    }

    public function exportAll()
    {
        try {
            $tables = $this->availableTables();

            abort_if($tables->isEmpty(), 404);

            $zipPath = tempnam(sys_get_temp_dir(), 'exports_');
            $zip = new ZipArchive();
            $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            foreach ($tables as $table) {
                $columns = $this->columnsFor($table);
                $columnLabels = $this->headersFor($table, $columns);
                $rows = $this->rowsFor($table, $columns, [], true);
                $csv = $this->buildCsvString($columns, $columnLabels, $rows);
                $zip->addFromString($table.'.csv', $csv);
            }

            $zip->close();

            $fileName = 'all_exports_'.now()->format('Ymd_His').'.zip';

            return response()->download($zipPath, $fileName, [
                'Content-Type' => 'application/zip',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()->with('error', 'Export failed: '.$e->getMessage());
        }
    }

    private function availableTables(): Collection
    {
        if (! empty($this->tableConfigs)) {
            return collect(array_keys($this->tableConfigs));
        }

        $database = DB::getDatabaseName();
        $tables = DB::select('SHOW TABLES');
        $key = 'Tables_in_'.$database;

        return collect($tables)
            ->map(fn ($table) => $table->$key ?? null)
            ->filter()
            ->reject(fn ($name) => in_array($name, $this->ignoredTables, true))
            ->values();
    }

    private function columnsFor(string $table): array
    {
        if (isset($this->tableConfigs[$table])) {
            $config = $this->tableConfigs[$table];

            if (! empty($config['headers'])) {
                return array_keys($config['headers']);
            }

            return collect($config['select'] ?? [])
                ->map(fn ($col) => $col[1] ?? $col[0])
                ->toArray();
        }

        $columns = DB::select('SHOW COLUMNS FROM `'.$table.'`');

        return collect($columns)->pluck('Field')->toArray();
    }

    private function rowsFor(string $table, array $columns, array $selected, bool $forExport): Collection
    {
        if (isset($this->tableConfigs[$table])) {
            return $this->buildConfiguredRows($table, $selected, $forExport);
        }

        $query = DB::table($table);
        $hasId = in_array('id', $columns, true);

        if ($hasId && ! empty($selected)) {
            $query->whereIn('id', $selected);
        }

        if (! $forExport) {
            $query->limit(200);
        }

        $rows = $query->get();

        if (! $hasId && ! empty($selected)) {
            $rows = $rows->filter(function ($row, $index) use ($selected) {
                return in_array((string) $index, $selected, true);
            });
        }

        return $rows;
    }

    private function buildConfiguredRows(string $table, array $selected, bool $forExport): Collection
    {
        $config = $this->tableConfigs[$table];
        $query = DB::table($table);

        foreach ($config['joins'] ?? [] as $join) {
            [$type, $joinTable, $first, $operator, $second] = $join;
            $method = $type === 'left' ? 'leftJoin' : ($type === 'right' ? 'rightJoin' : ($type === 'cross' ? 'crossJoin' : 'join'));
            $query->{$method}($joinTable, $first, $operator, $second);
        }

        if (! empty($config['where'])) {
            foreach ($config['where'] as $where) {
                $query->where(...$where);
            }
        }

        if (! $forExport) {
            $query->limit(200);
        }

        $hasId = in_array('id', collect($config['select'])->pluck(1)->toArray(), true);

        if ($hasId && ! empty($selected)) {
            $query->whereIn('id', $selected);
        }

        $selects = collect($config['select'])->map(function ($col) {
            [$column, $alias] = $col;
            return DB::raw($column.' as '.$alias);
        });

        $rows = $query->select($selects->all())->get();

        if (! $hasId && ! empty($selected)) {
            $rows = $rows->filter(function ($row, $index) use ($selected) {
                return in_array((string) $index, $selected, true);
            });
        }

        return $rows;
    }

    private function headersFor(string $table, array $columns): array
    {
        if (isset($this->tableConfigs[$table]['headers'])) {
            $map = $this->tableConfigs[$table]['headers'];
            return collect($columns)->map(fn ($col) => $map[$col] ?? $col)->toArray();
        }

        return $columns;
    }

    private function exportCsv(string $table, array $columns, array $labels, Collection $rows, string $extension = 'csv', string $contentType = 'text/csv')
    {
        $fileName = $table.'_'.now()->format('Ymd_His').'.'.$extension;

        return response()->streamDownload(function () use ($columns, $labels, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $labels);

            foreach ($rows as $row) {
                $line = [];
                foreach ($columns as $column) {
                    $line[] = $row->{$column} ?? '';
                }
                fputcsv($handle, $line);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => $contentType,
        ]);
    }

    private function exportPdf(string $table, array $columns, array $labels, Collection $rows)
    {
        $pdf = new Fpdf();
        $pdf->AddPage('L');
        $pdf->SetFont('Arial', 'B', 9);

        $limitedColumns = array_slice($columns, 0, 10);
        $limitedLabels = array_slice($labels, 0, 10);
        $columnWidth = $pdf->GetPageWidth() / max(count($limitedColumns), 1);

        foreach ($limitedLabels as $label) {
            $pdf->Cell($columnWidth, 10, strtoupper($label), 1, 0, 'C');
        }
        $pdf->Ln();

        $pdf->SetFont('Arial', '', 8);

        foreach ($rows as $row) {
            foreach ($limitedColumns as $column) {
                $value = (string) ($row->{$column} ?? '');
                $pdf->Cell($columnWidth, 8, mb_strimwidth($value, 0, 25, '...'), 1);
            }
            $pdf->Ln();
        }

        return response($pdf->Output('S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$table.'_'.now()->format('Ymd_His').'.pdf"',
        ]);
    }

    private function buildCsvString(array $columns, array $labels, Collection $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $labels);

        foreach ($rows as $row) {
            $line = [];
            foreach ($columns as $column) {
                $line[] = $row->{$column} ?? '';
            }
            fputcsv($handle, $line);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv ?: '';
    }
}
