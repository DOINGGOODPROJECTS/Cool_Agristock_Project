<?php

namespace App\Services\Sync;

use App\Models\Detail;
use App\Models\InventoryOp;
use App\Models\InventoryStock;
use App\Models\Release;
use App\Models\Rotten;
use App\Models\SyncSession;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ReconciliationEngine
{
    const RESULT_APPLIED      = 'applied';
    const RESULT_CONFLICT     = 'conflict';
    const RESULT_ALREADY_SEEN = 'already_seen';

    public function __construct(
        private AuditLogService       $audit,
        private SyncPermissionService $perms,
    ) {}

    // ── Batch entry-point ────────────────────────────────────────────────

    /**
     * Process a complete mobile sync batch in a single DB transaction.
     * Returns counts keyed by result: applied, conflict, already_seen.
     *
     * @param  array<int, array<string, mixed>> $opsData      Raw op payloads from device
     * @param  \DateTimeInterface|null          $lastSyncAt   Client's previous successful sync time
     */
    public function processBatch(
        array $opsData,
        User $actor,
        SyncSession $session,
        ?\DateTimeInterface $lastSyncAt = null
    ): array {
        $counts = [
            self::RESULT_APPLIED      => 0,
            self::RESULT_CONFLICT     => 0,
            self::RESULT_ALREADY_SEEN => 0,
        ];

        $firstProcessedOp = null;

        DB::transaction(function () use ($opsData, $actor, $lastSyncAt, $session, &$counts, &$firstProcessedOp) {
            foreach ($opsData as $opData) {
                $result  = $this->processOp($opData, $actor, $lastSyncAt);
                $counts[$result]++;

                if ($firstProcessedOp === null && $result !== self::RESULT_ALREADY_SEEN) {
                    $firstProcessedOp = InventoryOp::where('op_id', $opData['op_id'])->first();
                }
            }

            // One reconciled log entry per session anchored to the first processed op
            if ($firstProcessedOp !== null) {
                $this->audit->log('reconciled', $firstProcessedOp, $actor, [
                    'before' => null,
                    'after'  => array_merge($counts, ['session_id' => $session->session_id]),
                ]);
            }

            $session->update([
                'ops_submitted'  => $session->ops_submitted + count($opsData),
                'ops_applied'    => $session->ops_applied    + $counts[self::RESULT_APPLIED],
                'ops_conflicted' => $session->ops_conflicted + $counts[self::RESULT_CONFLICT],
                'status'         => 'completed',
            ]);
        });

        return $counts;
    }

    // ── Per-op processing ────────────────────────────────────────────────

    /**
     * Process a single op payload.
     * Returns RESULT_ALREADY_SEEN | RESULT_APPLIED | RESULT_CONFLICT.
     * Intended to be called inside a transaction (from processBatch or standalone).
     */
    public function processOp(
        array $opData,
        User $actor,
        ?\DateTimeInterface $lastSyncAt = null
    ): string {
        // Idempotency: same op_id submitted twice → acknowledge without re-processing
        if (InventoryOp::where('op_id', $opData['op_id'])->exists()) {
            return self::RESULT_ALREADY_SEEN;
        }

        $conflict = $this->detectConflict($opData, $lastSyncAt);

        $op = InventoryOp::create([
            'op_id'               => $opData['op_id'],
            'user_id'             => $opData['user_id'],
            'device_id'           => $opData['device_id'],
            'logical_seq'         => $opData['logical_seq'],
            'storage_id'          => $opData['storage_id'],
            'product_id'          => $opData['product_id'],
            'stock_id'            => $opData['stock_id'] ?? null,
            'op_type'             => $opData['op_type'],
            'quantity_delta'      => $opData['quantity_delta'],
            'unit'                => $opData['unit'],
            'notes'               => $opData['notes'] ?? null,
            'sync_status'         => $conflict ? 'conflict' : 'pending',
            'client_created_at'   => $opData['client_created_at'] ?? null,
            'server_received_at'  => now(),
            'conflict_with_op_id' => $conflict['conflict_with_op_id'] ?? null,
            'conflict_reason'     => $conflict['reason'] ?? null,
        ]);

        $this->audit->log('submitted', $op, $actor, [
            'before'    => null,
            'after'     => $this->audit->snapshot($op),
            'device_id' => $op->device_id,
        ]);

        if ($conflict) {
            $this->audit->log('conflict_flagged', $op, $actor, [
                'after' => [
                    'sync_status'     => 'conflict',
                    'conflict_reason' => $conflict['reason'],
                ],
            ]);
            return self::RESULT_CONFLICT;
        }

        $this->applyOpToStock($op);

        $op->update(['sync_status' => 'applied', 'applied_at' => now()]);

        $this->audit->log('applied', $op->fresh(), $actor, [
            'after' => ['sync_status' => 'applied'],
        ]);

        return self::RESULT_APPLIED;
    }

    // ── Conflict detection ───────────────────────────────────────────────

    /**
     * Returns null (no conflict) or an array with 'reason' and optional
     * 'conflict_with_op_id' pointing at the specific conflicting applied op.
     *
     * Two checks:
     *   1. Adjustment conflict — a concurrent adjustment was already applied
     *      on the same storage+product since the client's last sync point.
     *   2. Negative stock — the projected balance after this op would be < 0.
     */
    public function detectConflict(array $opData, ?\DateTimeInterface $lastSyncAt = null): ?array
    {
        // ── 1. Concurrent adjustment ──────────────────────────────────────
        if ($opData['op_type'] === 'adjustment') {
            $rival = InventoryOp::where('storage_id', $opData['storage_id'])
                ->where('product_id', $opData['product_id'])
                ->where('op_type', 'adjustment')
                ->where('sync_status', 'applied')
                ->when($lastSyncAt, fn($q) => $q->where('applied_at', '>', $lastSyncAt))
                ->latest('applied_at')
                ->first();

            if ($rival) {
                return [
                    'reason'              => 'Concurrent adjustment already applied since last sync.',
                    'conflict_with_op_id' => $rival->op_id,
                ];
            }
        }

        // ── 2. Negative stock projection ──────────────────────────────────
        $currentQty = (float) (InventoryStock::where('storage_id', $opData['storage_id'])
            ->where('product_id', $opData['product_id'])
            ->when(isset($opData['stock_id']), fn($q) => $q->where('stock_id', $opData['stock_id']))
            ->value('quantity') ?? 0);

        $projected = $currentQty + (float) $opData['quantity_delta'];

        if ($projected < 0) {
            return [
                'reason' => sprintf(
                    'Projected quantity %.3f would be negative (current: %.3f, delta: %.3f).',
                    $projected,
                    $currentQty,
                    (float) $opData['quantity_delta']
                ),
                'conflict_with_op_id' => null,
            ];
        }

        return null;
    }

    // ── Stock application ────────────────────────────────────────────────

    /**
     * Upsert inventory_stock and write side records for spoilage / stock_out.
     * Skips gracefully when op has no stock_id (can't resolve inventory_stock row).
     */
    public function applyOpToStock(InventoryOp $op): void
    {
        if ($op->stock_id === null) {
            return;
        }

        $row = InventoryStock::where('storage_id', $op->storage_id)
            ->where('product_id', $op->product_id)
            ->where('stock_id', $op->stock_id)
            ->first();

        $beforeQty = $row ? (float) $row->quantity : 0.0;
        $afterQty  = $beforeQty + (float) $op->quantity_delta;

        if ($row) {
            $row->update([
                'quantity'        => $afterQty,
                'last_op_id'      => $op->op_id,
                'last_updated_at' => now(),
            ]);
        } else {
            InventoryStock::create([
                'storage_id'      => $op->storage_id,
                'product_id'      => $op->product_id,
                'stock_id'        => $op->stock_id,
                'quantity'        => $afterQty,
                'unit'            => $op->unit,
                'last_op_id'      => $op->op_id,
                'last_updated_at' => now(),
            ]);
        }

        // Side records require a detail linking this product to this stock
        $detail = Detail::where('product_id', $op->product_id)
            ->where('stock_id', $op->stock_id)
            ->first();

        if ($detail) {
            if ($op->op_type === 'spoilage') {
                Rotten::create([
                    'before_qty' => $beforeQty,
                    'qty'        => abs((float) $op->quantity_delta),
                    'after_qty'  => $afterQty,
                    'detail_id'  => $detail->id,
                    'stock_id'   => $op->stock_id,
                ]);
            }

            if ($op->op_type === 'stock_out') {
                Release::create([
                    'before_qty' => $beforeQty,
                    'qty'        => abs((float) $op->quantity_delta),
                    'after_qty'  => $afterQty,
                    'detail_id'  => $detail->id,
                    'stock_id'   => $op->stock_id,
                    'delivery'   => 'Client',
                ]);
            }
        }
    }

    // ── Manager actions on conflicted ops ────────────────────────────────

    /**
     * @throws AuthorizationException
     */
    public function acceptConflict(InventoryOp $op, User $actor, string $reason = ''): void
    {
        $this->perms->canOrFail($actor, 'sync.accept');

        DB::transaction(function () use ($op, $actor, $reason) {
            $this->audit->log('accepted', $op, $actor, [
                'after'  => ['sync_status' => 'applied'],
                'reason' => $reason ?: null,
            ]);

            $this->applyOpToStock($op);

            $op->update([
                'sync_status' => 'applied',
                'resolved_by' => $actor->id,
                'resolved_at' => now(),
                'applied_at'  => now(),
            ]);
        });
    }

    /**
     * @throws AuthorizationException
     */
    public function discardConflict(InventoryOp $op, User $actor, string $reason = ''): void
    {
        $this->perms->canOrFail($actor, 'sync.discard');

        DB::transaction(function () use ($op, $actor, $reason) {
            $this->audit->log('discarded', $op, $actor, [
                'after'  => ['sync_status' => 'superseded'],
                'reason' => $reason ?: null,
            ]);

            $op->update([
                'sync_status' => 'superseded',
                'resolved_by' => $actor->id,
                'resolved_at' => now(),
            ]);
        });
    }

    // ── Owner / admin actions ────────────────────────────────────────────

    /**
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     */
    public function cancelOp(InventoryOp $op, User $actor, string $reason = ''): void
    {
        if ($op->sync_status !== 'pending') {
            throw new InvalidArgumentException(
                "Cannot cancel op [{$op->op_id}]: status is [{$op->sync_status}], only pending ops may be cancelled."
            );
        }

        $this->perms->canOwnerOrFail($actor, 'sync.cancel', $op->user_id);

        DB::transaction(function () use ($op, $actor, $reason) {
            $this->audit->log('cancelled', $op, $actor, [
                'after'  => ['sync_status' => 'cancelled'],
                'reason' => $reason ?: null,
            ]);

            $op->update([
                'sync_status'  => 'cancelled',
                'cancelled_by' => $actor->id,
                'cancelled_at' => now(),
            ]);
        });
    }

    /**
     * Merge two conflicting ops into a single applied op with a reconciled quantity.
     * Both originals are superseded; the merged op is applied immediately to stock.
     *
     * @throws AuthorizationException
     */
    public function mergeOps(
        InventoryOp $opA,
        InventoryOp $opB,
        float $mergedQuantity,
        User $actor,
        string $reason
    ): InventoryOp {
        $this->perms->canOrFail($actor, 'sync.merge');

        return DB::transaction(function () use ($opA, $opB, $mergedQuantity, $actor, $reason) {
            $beforeA = $this->audit->snapshot($opA);
            $beforeB = $this->audit->snapshot($opB);

            $mergedOp = InventoryOp::create([
                'op_id'              => (string) Str::uuid(),
                'user_id'            => $actor->id,
                'device_id'          => 'web',
                'logical_seq'        => $opA->logical_seq,
                'storage_id'         => $opA->storage_id,
                'product_id'         => $opA->product_id,
                'stock_id'           => $opA->stock_id,
                'op_type'            => $opA->op_type,
                'quantity_delta'     => $mergedQuantity,
                'unit'               => $opA->unit,
                'notes'              => $reason,
                'sync_status'        => 'pending',
                'server_received_at' => now(),
                'edited_from_op_id'  => $opA->op_id,
            ]);

            $mergedInfo = ['merged_op_id' => $mergedOp->op_id, 'quantity_delta' => $mergedQuantity];

            $this->audit->log('merged', $opA, $actor, [
                'before' => ['op_a' => $beforeA, 'op_b' => $beforeB],
                'after'  => $mergedInfo,
                'reason' => $reason,
            ]);
            $opA->update(['sync_status' => 'superseded', 'resolved_by' => $actor->id, 'resolved_at' => now()]);

            $this->audit->log('merged', $opB, $actor, [
                'before' => ['op_a' => $beforeA, 'op_b' => $beforeB],
                'after'  => $mergedInfo,
                'reason' => $reason,
            ]);
            $opB->update(['sync_status' => 'superseded', 'resolved_by' => $actor->id, 'resolved_at' => now()]);

            $this->applyOpToStock($mergedOp);

            $mergedOp->update(['sync_status' => 'applied', 'applied_at' => now()]);

            $this->audit->log('applied', $mergedOp->fresh(), $actor, [
                'before' => null,
                'after'  => ['sync_status' => 'applied', 'quantity_delta' => $mergedQuantity],
            ]);

            return $mergedOp;
        });
    }

    /**
     * Edit a pending op: supersede the original, return the corrected replacement.
     * The replacement op is left pending — the next reconciliation processes it.
     *
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     */
    public function editOp(InventoryOp $op, array $changes, User $actor): InventoryOp
    {
        if ($op->sync_status !== 'pending') {
            throw new InvalidArgumentException(
                "Cannot edit op [{$op->op_id}]: only pending ops may be edited (status: {$op->sync_status})."
            );
        }

        $this->perms->canOwnerOrFail($actor, 'sync.edit', $op->user_id);

        return DB::transaction(function () use ($op, $changes, $actor) {
            $allowed      = ['quantity_delta', 'notes'];
            $validChanges = array_intersect_key($changes, array_flip($allowed));

            $this->audit->log('edited', $op, $actor, [
                'before' => $op->only($allowed),
                'after'  => $validChanges,
                'reason' => $changes['reason'] ?? null,
            ]);

            $newOp = InventoryOp::create(array_merge(
                $op->only(['user_id', 'device_id', 'logical_seq', 'storage_id', 'product_id', 'stock_id', 'op_type', 'unit']),
                $validChanges,
                [
                    'op_id'              => (string) Str::uuid(),
                    'sync_status'        => 'pending',
                    'server_received_at' => now(),
                    'edited_from_op_id'  => $op->op_id,
                ]
            ));

            $op->update(['sync_status' => 'superseded']);

            return $newOp;
        });
    }

    /**
     * Force-set inventory_stock.quantity to an exact value (override physical count).
     * Uses sync.accept permission — same tier that can resolve conflicts.
     *
     * @throws AuthorizationException
     */
    public function applyOverride(InventoryOp $op, float $quantity, User $actor): void
    {
        $this->perms->canOrFail($actor, 'sync.accept');

        DB::transaction(function () use ($op, $quantity, $actor) {
            $this->audit->log('overridden', $op, $actor, [
                'after'  => ['quantity' => $quantity],
                'reason' => "Override: quantity forced to {$quantity} {$op->unit}",
            ]);

            if ($op->stock_id !== null) {
                InventoryStock::updateOrCreate(
                    [
                        'storage_id' => $op->storage_id,
                        'product_id' => $op->product_id,
                        'stock_id'   => $op->stock_id,
                    ],
                    [
                        'quantity'        => $quantity,
                        'unit'            => $op->unit,
                        'last_op_id'      => $op->op_id,
                        'last_updated_at' => now(),
                    ]
                );
            }
        });
    }
}
