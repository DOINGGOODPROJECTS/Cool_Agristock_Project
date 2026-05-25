<?php

namespace App\Services\Sync;

use App\Models\InventoryOp;
use App\Models\InventoryStock;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Parallel write layer — mirrors every legacy stock write into inventory_ops
 * and inventory_stock so the sync layer stays in lock-step with the old path.
 *
 * DESIGN: Both paths run simultaneously ("dual-write") until the sync layer
 * is proven stable. The old path (stocks.qty / details.qty) remains the
 * primary source of truth for the web UI. inventory_stock is the sync-layer
 * shadow. Once consistency is confirmed, the old qty columns can be retired.
 *
 * This service does NOT call ReconciliationEngine::applyOpToStock() because
 * that method also creates Rotten/Release side records — which the old path
 * already handles. Calling it here would duplicate those records.
 */
class SyncStockWriter
{
    public function __construct(private AuditLogService $audit) {}

    /**
     * Record a server-side stock mutation as an inventory_op and mirror it
     * into inventory_stock. The op is stamped 'applied' immediately — no
     * conflict detection is run for web-originated writes.
     *
     * @param  string      $opType        stock_in | stock_out | spoilage | correction | adjustment
     * @param  float       $quantityDelta Positive for inbound, negative for outbound
     * @param  int         $storageId
     * @param  int         $productId
     * @param  int         $stockId
     * @param  string      $unit          Defaults to 'kg' — legacy schema has no unit column on details
     * @param  string|null $notes
     */
    public function record(
        string  $opType,
        float   $quantityDelta,
        int     $storageId,
        int     $productId,
        int     $stockId,
        string  $unit  = 'kg',
        ?string $notes = null,
    ): ?InventoryOp {
        try {
            return $this->writeOp($opType, $quantityDelta, $storageId, $productId, $stockId, $unit, $notes);
        } catch (\Throwable $e) {
            // Sync path must never crash the primary write path
            Log::error('SyncStockWriter failed', [
                'op_type'        => $opType,
                'storage_id'     => $storageId,
                'product_id'     => $productId,
                'stock_id'       => $stockId,
                'quantity_delta' => $quantityDelta,
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    private function writeOp(
        string  $opType,
        float   $quantityDelta,
        int     $storageId,
        int     $productId,
        int     $stockId,
        string  $unit,
        ?string $notes,
    ): InventoryOp {
        $user = auth()->user();

        $op = InventoryOp::create([
            'op_id'              => (string) Str::uuid(),
            'user_id'            => $user->id,
            'device_id'          => 'web',
            'logical_seq'        => ((int) InventoryOp::max('logical_seq')) + 1,
            'storage_id'         => $storageId,
            'product_id'         => $productId,
            'stock_id'           => $stockId,
            'op_type'            => $opType,
            'quantity_delta'     => $quantityDelta,
            'unit'               => $unit,
            'notes'              => $notes,
            'sync_status'        => 'applied',
            'server_received_at' => now(),
            'applied_at'         => now(),
        ]);

        $this->audit->log('submitted', $op, $user, [
            'before'    => null,
            'after'     => $this->audit->snapshot($op),
            'device_id' => 'web',
        ]);

        // Mirror into inventory_stock — direct upsert, no side records
        // (Rotten / Release are already created by the old controller path)
        $row      = InventoryStock::where('storage_id', $storageId)
                        ->where('product_id', $productId)
                        ->where('stock_id', $stockId)
                        ->first();
        $afterQty = ($row ? (float) $row->quantity : 0.0) + $quantityDelta;

        InventoryStock::updateOrCreate(
            ['storage_id' => $storageId, 'product_id' => $productId, 'stock_id' => $stockId],
            ['quantity' => $afterQty, 'unit' => $unit, 'last_op_id' => $op->op_id, 'last_updated_at' => now()]
        );

        $this->audit->log('applied', $op->fresh(), $user, [
            'after' => ['sync_status' => 'applied', 'quantity_after' => $afterQty],
        ]);

        return $op;
    }
}
