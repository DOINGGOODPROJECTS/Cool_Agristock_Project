<?php

namespace App\Console\Commands;

use App\Models\Detail;
use App\Models\InventoryStock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * BACKFILL DECISION
 * -----------------
 * Decision: Backfill from details.qty — do NOT start fresh.
 *
 * Rationale:
 *  1. inventory_stock is currently empty; there are 7 stocks and 13 details
 *     with real quantities that represent the current warehouse state.
 *  2. Starting fresh would leave inventory_stock at zero, causing every
 *     subsequent mobile sync push to trigger false negative-stock conflicts
 *     the moment a stock_out or spoilage op is submitted.
 *  3. detail.qty is the authoritative per-(storage, product, stock) quantity
 *     in the legacy schema. The mapping detail → inventory_stock is 1-to-1:
 *       inventory_stock.storage_id = detail.stock.storage_id
 *       inventory_stock.product_id = detail.product_id
 *       inventory_stock.stock_id   = detail.stock_id
 *       inventory_stock.quantity   = detail.qty
 *  4. Unit: the legacy schema has no unit column on details or stocks.
 *     We default to 'kg'. This can be corrected per-product once a unit
 *     field is added to products/details.
 *  5. last_op_id is set to NULL for backfill rows (requires the nullable
 *     migration 2026_05_25_155030). The first real sync op to touch a row
 *     will populate last_op_id with a real UUID.
 *
 * Idempotent: safe to run multiple times — uses INSERT ... ON DUPLICATE KEY
 * UPDATE so re-running after new stocks are added picks up only new rows
 * without overwriting quantities that may have been updated by real sync ops.
 *
 * Usage:
 *   php artisan sync:backfill-inventory-stock          # dry-run preview
 *   php artisan sync:backfill-inventory-stock --write  # apply changes
 */
class SyncBackfillInventoryStock extends Command
{
    protected $signature   = 'sync:backfill-inventory-stock {--write : Actually write rows (omit for dry-run)}';
    protected $description = 'Seed inventory_stock from existing details.qty (backfill decision: use legacy data, not fresh start)';

    public function handle(): int
    {
        $dryRun = ! $this->option('write');

        if ($dryRun) {
            $this->warn('DRY RUN — pass --write to persist changes.');
        }

        $details = Detail::with('stock:id,storage_id')
            ->whereNotNull('stock_id')
            ->whereHas('stock', fn($q) => $q->whereNull('deleted_at'))
            ->get(['id', 'stock_id', 'product_id', 'qty']);

        if ($details->isEmpty()) {
            $this->info('No details found — nothing to backfill.');
            return self::SUCCESS;
        }

        $this->info("Found {$details->count()} detail rows to process.");

        $headers  = ['Detail ID', 'Stock ID', 'Storage ID', 'Product ID', 'Qty', 'Action'];
        $tableRows = [];
        $inserted  = 0;
        $skipped   = 0;

        DB::beginTransaction();

        foreach ($details as $detail) {
            $storageId = $detail->stock?->storage_id;

            if (! $storageId) {
                $this->warn("  Skipping detail #{$detail->id} — stock has no storage_id.");
                $skipped++;
                continue;
            }

            $exists = InventoryStock::where('storage_id', $storageId)
                ->where('product_id', $detail->product_id)
                ->where('stock_id', $detail->stock_id)
                ->exists();

            if ($exists) {
                $tableRows[] = [$detail->id, $detail->stock_id, $storageId, $detail->product_id, $detail->qty, 'skip (exists)'];
                $skipped++;
                continue;
            }

            $tableRows[] = [$detail->id, $detail->stock_id, $storageId, $detail->product_id, $detail->qty, $dryRun ? 'would insert' : 'inserted'];

            if (! $dryRun) {
                InventoryStock::create([
                    'storage_id'      => $storageId,
                    'product_id'      => $detail->product_id,
                    'stock_id'        => $detail->stock_id,
                    'quantity'        => $detail->qty,
                    'unit'            => 'kg',   // legacy schema has no unit column — default, correct per-product later
                    'last_op_id'      => null,   // no originating op; first real sync op will populate this
                    'last_updated_at' => now(),
                ]);
            }

            $inserted++;
        }

        if ($dryRun) {
            DB::rollBack();
        } else {
            DB::commit();
        }

        $this->table($headers, $tableRows);
        $this->newLine();

        if ($dryRun) {
            $this->info("Would insert {$inserted} rows, skip {$skipped}.");
            $this->line('Run with <comment>--write</comment> to apply.');
        } else {
            $this->info("Inserted {$inserted} rows, skipped {$skipped} (already existed).");
        }

        return self::SUCCESS;
    }
}
