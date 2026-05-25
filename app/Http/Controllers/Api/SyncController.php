<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryOp;
use App\Models\InventoryStock;
use App\Models\SyncAuditLog;
use App\Models\SyncSession;
use App\Services\Sync\ReconciliationEngine;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class SyncController extends Controller
{
    public function __construct(private ReconciliationEngine $engine) {}

    // ── POST /api/sync/push ──────────────────────────────────────────────

    public function push(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id'              => 'required|uuid',
            'device_id'               => 'required|string|max:191',
            'last_sync_at'            => 'nullable|date',
            'ops'                     => 'required|array|min:1',
            'ops.*.op_id'             => 'required|uuid',
            'ops.*.user_id'           => 'required|integer|exists:users,id',
            'ops.*.device_id'         => 'required|string|max:191',
            'ops.*.logical_seq'       => 'required|integer|min:0',
            'ops.*.storage_id'        => ['required', 'integer', Rule::exists('storages', 'id')->whereNull('deleted_at')],
            'ops.*.product_id'        => 'required|integer|exists:products,id',
            'ops.*.stock_id'          => 'nullable|integer',
            'ops.*.op_type'           => 'required|string|in:adjustment,stock_in,stock_out,spoilage,transfer,correction',
            'ops.*.quantity_delta'    => 'required|numeric',
            'ops.*.unit'              => 'required|string|max:50',
            'ops.*.notes'             => 'nullable|string|max:500',
            'ops.*.client_created_at' => 'nullable|date',
        ]);

        $user       = $request->user();
        $lastSyncAt = $validated['last_sync_at']
            ? Carbon::parse($validated['last_sync_at'])
            : null;

        $session = SyncSession::firstOrCreate(
            ['session_id' => $validated['session_id']],
            [
                'user_id'            => $user->id,
                'device_id'          => $validated['device_id'],
                'ops_submitted'      => 0,
                'ops_applied'        => 0,
                'ops_conflicted'     => 0,
                'status'             => 'in_progress',
                'client_logical_seq' => collect($validated['ops'])->max('logical_seq') ?? 0,
            ]
        );

        $counts = $this->engine->processBatch($validated['ops'], $user, $session, $lastSyncAt);

        // Current inventory state for every (storage, product, stock) tuple in this batch
        $authoritativeState = collect($validated['ops'])
            ->map(fn($op) => [
                'storage_id' => $op['storage_id'],
                'product_id' => $op['product_id'],
                'stock_id'   => $op['stock_id'] ?? null,
            ])
            ->unique(fn($t) => "{$t['storage_id']}_{$t['product_id']}_" . ($t['stock_id'] ?? 'null'))
            ->map(function ($t) {
                $row = InventoryStock::where('storage_id', $t['storage_id'])
                    ->where('product_id', $t['product_id'])
                    ->when($t['stock_id'], fn($q) => $q->where('stock_id', $t['stock_id']))
                    ->first();

                return [
                    'storage_id' => $t['storage_id'],
                    'product_id' => $t['product_id'],
                    'stock_id'   => $t['stock_id'],
                    'quantity'   => $row ? (float) $row->quantity : 0.0,
                    'unit'       => $row?->unit,
                ];
            })
            ->values();

        $conflicts = $this->enrichConflicts(
            InventoryOp::with(['product:id,name', 'storage:id,name'])
                ->where('sync_status', 'conflict')
                ->whereIn('op_id', collect($validated['ops'])->pluck('op_id'))
                ->get(['op_id', 'conflict_reason', 'conflict_with_op_id',
                       'storage_id', 'product_id', 'stock_id',
                       'op_type', 'quantity_delta', 'unit'])
        );

        return response()->json([
            'session_id'          => $session->session_id,
            'applied_count'       => $counts[ReconciliationEngine::RESULT_APPLIED],
            'conflict_count'      => $counts[ReconciliationEngine::RESULT_CONFLICT],
            'already_seen_count'  => $counts[ReconciliationEngine::RESULT_ALREADY_SEEN],
            'conflicts'           => $conflicts,
            'authoritative_state' => $authoritativeState,
            'server_logical_seq'  => (int) InventoryOp::max('logical_seq'),
        ]);
    }

    // ── GET /api/sync/pull ───────────────────────────────────────────────

    public function pull(Request $request): JsonResponse
    {
        $request->validate([
            'since_logical_seq' => 'nullable|integer|min:0',
            'device_id'         => 'nullable|string|max:191',
        ]);

        $user      = $request->user();
        $sinceLSeq = (int) ($request->since_logical_seq ?? 0);
        $deviceId  = $request->device_id;

        // Applied ops from other devices that the client may not have
        $remoteOps = InventoryOp::with(['product:id,name', 'storage:id,name'])
            ->where('sync_status', 'applied')
            ->when($sinceLSeq > 0, fn($q) => $q->where('logical_seq', '>', $sinceLSeq))
            ->when($deviceId, fn($q) => $q->where('device_id', '!=', $deviceId))
            ->orderBy('logical_seq')
            ->get([
                'op_id', 'user_id', 'device_id', 'logical_seq',
                'storage_id', 'product_id', 'stock_id',
                'op_type', 'quantity_delta', 'unit', 'notes',
                'applied_at', 'server_received_at',
            ]);

        // This user's ops that are still waiting for conflict resolution
        $pendingConflicts = $this->enrichConflicts(
            InventoryOp::with(['product:id,name', 'storage:id,name'])
                ->where('sync_status', 'conflict')
                ->where('user_id', $user->id)
                ->get([
                    'op_id', 'device_id', 'logical_seq',
                    'storage_id', 'product_id', 'stock_id',
                    'op_type', 'quantity_delta', 'unit', 'notes',
                    'conflict_reason', 'conflict_with_op_id', 'server_received_at',
                ])
        );

        return response()->json([
            'remote_ops'         => $remoteOps,
            'pending_conflicts'  => $pendingConflicts,
            'server_logical_seq' => (int) InventoryOp::max('logical_seq'),
        ]);
    }

    // ── POST /api/sync/resolve-conflict ──────────────────────────────────

    public function resolveConflict(Request $request): JsonResponse
    {
        $request->validate([
            'op_id'             => 'required|uuid',
            'resolution'        => 'required|in:accept,discard,override,merge',
            'reason'            => 'nullable|string|max:500',
            'override_quantity' => 'required_if:resolution,override|nullable|numeric|min:0',
            'merge_op_id'       => 'required_if:resolution,merge|nullable|uuid',
            'merged_quantity'   => 'required_if:resolution,merge|nullable|numeric',
        ]);

        $user   = $request->user();
        $op     = InventoryOp::where('op_id', $request->op_id)->firstOrFail();
        $reason = $request->reason ?? '';

        if ($request->resolution === 'accept') {
            $this->engine->acceptConflict($op, $user, $reason);
        } elseif ($request->resolution === 'discard') {
            $this->engine->discardConflict($op, $user, $reason);
        } elseif ($request->resolution === 'override') {
            $this->engine->applyOverride($op, (float) $request->override_quantity, $user);
        } elseif ($request->resolution === 'merge') {
            $opB = InventoryOp::where('op_id', $request->merge_op_id)->firstOrFail();
            $this->engine->mergeOps($op, $opB, (float) $request->merged_quantity, $user, $reason);
        }

        return response()->json([
            'message'    => 'Resolved.',
            'op_id'      => $op->op_id,
            'resolution' => $request->resolution,
        ]);
    }

    // ── POST /api/sync/cancel ────────────────────────────────────────────

    public function cancel(Request $request): JsonResponse
    {
        $request->validate([
            'op_id'  => 'required|uuid',
            'reason' => 'nullable|string|max:500',
        ]);

        $op = InventoryOp::where('op_id', $request->op_id)->firstOrFail();

        try {
            $this->engine->cancelOp($op, $request->user(), $request->reason ?? '');
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => 'invalid_state', 'message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Op cancelled.', 'op_id' => $op->op_id]);
    }

    // ── POST /api/sync/edit ──────────────────────────────────────────────

    public function edit(Request $request): JsonResponse
    {
        $request->validate([
            'op_id'                  => 'required|uuid',
            'changes'                => 'required|array',
            'changes.quantity_delta' => 'nullable|numeric',
            'changes.notes'          => 'nullable|string|max:500',
            'changes.reason'         => 'nullable|string|max:500',
        ]);

        $op = InventoryOp::where('op_id', $request->op_id)->firstOrFail();

        try {
            $newOp = $this->engine->editOp($op, $request->input('changes', []), $request->user());
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => 'invalid_state', 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message'        => 'Op edited — replacement pending op created.',
            'original_op_id' => $op->op_id,
            'new_op_id'      => $newOp->op_id,
        ]);
    }

    // ── GET /api/sync/log ────────────────────────────────────────────────

    public function log(Request $request): JsonResponse
    {
        $request->validate([
            'op_id'      => 'nullable|string',
            'user_id'    => 'nullable|integer',
            'action'     => 'nullable|string|in:submitted,applied,conflict_flagged,accepted,discarded,cancelled,edited,merged,reconciled,overridden,superseded',
            'date_from'  => 'nullable|date',
            'date_to'    => 'nullable|date',
            'storage_id' => 'nullable|integer',
            'product_id' => 'nullable|integer',
            'per_page'   => 'nullable|integer|min:1|max:200',
        ]);

        $perPage = (int) ($request->per_page ?? 50);

        $query = SyncAuditLog::with([
                'actor:id,name,group_id',
                'inventoryOp:op_id,storage_id,product_id,op_type,quantity_delta,unit',
            ])
            ->when($request->op_id,     fn($q) => $q->where('op_id', $request->op_id))
            ->when($request->user_id,   fn($q) => $q->where('actor_id', $request->user_id))
            ->when($request->action,    fn($q) => $q->where('action', $request->action))
            ->when($request->date_from, fn($q) => $q->where('created_at', '>=', $request->date_from))
            ->when($request->date_to,   fn($q) => $q->where('created_at', '<=', $request->date_to . ' 23:59:59'))
            ->when($request->storage_id || $request->product_id,
                fn($q) => $q->whereHas('inventoryOp', function ($sub) use ($request) {
                    $sub->when($request->storage_id, fn($s) => $s->where('storage_id', $request->storage_id))
                        ->when($request->product_id,  fn($s) => $s->where('product_id', $request->product_id));
                })
            )
            ->latest('id');

        $rows = $query->paginate($perPage);

        $data = $rows->map(fn($log) => [
            'id'               => $log->id,
            'op_id'            => $log->op_id,
            'actor_id'         => $log->actor_id,
            'actor_name'       => $log->actor->name ?? '—',
            'actor_group_id'   => $log->actor_group_id,
            'actor_group_name' => config('sync_permissions.groups.' . $log->actor_group_id . '.name', '—'),
            'action'           => $log->action,
            'device_id'        => $log->device_id,
            'ip_address'       => $log->ip_address,
            'reason'           => $log->reason,
            'before_value'     => $log->before_value,
            'after_value'      => $log->after_value,
            'created_at'       => $log->created_at,
            'op'               => $log->inventoryOp ? [
                'storage_id'     => $log->inventoryOp->storage_id,
                'product_id'     => $log->inventoryOp->product_id,
                'op_type'        => $log->inventoryOp->op_type,
                'quantity_delta' => $log->inventoryOp->quantity_delta,
                'unit'           => $log->inventoryOp->unit,
            ] : null,
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $rows->currentPage(),
                'per_page'     => $rows->perPage(),
                'total'        => $rows->total(),
                'last_page'    => $rows->lastPage(),
            ],
        ]);
    }

    // ── GET /api/sync/log/export ─────────────────────────────────────────

    public function exportLog(Request $request)
    {
        $request->validate([
            'op_id'      => 'nullable|string',
            'user_id'    => 'nullable|integer',
            'action'     => 'nullable|string',
            'date_from'  => 'nullable|date',
            'date_to'    => 'nullable|date',
            'storage_id' => 'nullable|integer',
            'product_id' => 'nullable|integer',
        ]);

        $query = SyncAuditLog::with([
                'actor:id,name,group_id',
                'inventoryOp:op_id,storage_id,product_id,op_type,quantity_delta,unit',
            ])
            ->when($request->op_id,     fn($q) => $q->where('op_id', $request->op_id))
            ->when($request->user_id,   fn($q) => $q->where('actor_id', $request->user_id))
            ->when($request->action,    fn($q) => $q->where('action', $request->action))
            ->when($request->date_from, fn($q) => $q->where('created_at', '>=', $request->date_from))
            ->when($request->date_to,   fn($q) => $q->where('created_at', '<=', $request->date_to . ' 23:59:59'))
            ->when($request->storage_id || $request->product_id,
                fn($q) => $q->whereHas('inventoryOp', function ($sub) use ($request) {
                    $sub->when($request->storage_id, fn($s) => $s->where('storage_id', $request->storage_id))
                        ->when($request->product_id,  fn($s) => $s->where('product_id', $request->product_id));
                })
            )
            ->latest('id');

        $filename = 'sync_audit_log_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-cache, no-store',
        ];

        $callback = function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

            fputcsv($out, [
                'ID', 'Op ID', 'Actor ID', 'Actor Name', 'Group ID', 'Group Name',
                'Action', 'Device', 'IP Address', 'Reason',
                'Before Value', 'After Value',
                'Storage ID', 'Product ID', 'Op Type', 'Qty Delta', 'Unit',
                'Created At',
            ]);

            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $log) {
                    fputcsv($out, [
                        $log->id,
                        $log->op_id,
                        $log->actor_id,
                        $log->actor->name ?? '—',
                        $log->actor_group_id,
                        config('sync_permissions.groups.' . $log->actor_group_id . '.name', '—'),
                        $log->action,
                        $log->device_id,
                        $log->ip_address ?? '—',
                        $log->reason ?? '—',
                        $log->before_value ? json_encode($log->before_value, JSON_UNESCAPED_UNICODE) : '—',
                        $log->after_value  ? json_encode($log->after_value,  JSON_UNESCAPED_UNICODE) : '—',
                        $log->inventoryOp?->storage_id ?? '—',
                        $log->inventoryOp?->product_id ?? '—',
                        $log->inventoryOp?->op_type ?? '—',
                        $log->inventoryOp?->quantity_delta ?? '—',
                        $log->inventoryOp?->unit ?? '—',
                        $log->created_at,
                    ]);
                }
            });

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /**
     * Attach server_qty, rival_qty, product name, and storage name to a
     * collection of conflict InventoryOp records so the client modal can
     * display them without a separate lookup.
     */
    private function enrichConflicts(\Illuminate\Support\Collection $ops): array
    {
        return $ops->map(function ($op) {
            $serverQty = (float) (InventoryStock::where('storage_id', $op->storage_id)
                ->where('product_id', $op->product_id)
                ->when($op->stock_id, fn($q) => $q->where('stock_id', $op->stock_id))
                ->value('quantity') ?? 0);

            $rivalQty = $op->conflict_with_op_id
                ? (float) (InventoryOp::where('op_id', $op->conflict_with_op_id)
                    ->value('quantity_delta') ?? 0)
                : null;

            return [
                'op_id'               => $op->op_id,
                'conflict_reason'     => $op->conflict_reason,
                'conflict_with_op_id' => $op->conflict_with_op_id,
                'storage_id'          => $op->storage_id,
                'product_id'          => $op->product_id,
                'stock_id'            => $op->stock_id ?? null,
                'op_type'             => $op->op_type,
                'quantity_delta'      => (float) $op->quantity_delta,
                'unit'                => $op->unit,
                'server_qty'          => $serverQty,
                'rival_qty'           => $rivalQty,
                'product'             => $op->product
                    ? ['id' => $op->product->id, 'name' => $op->product->name]
                    : null,
                'storage'             => $op->storage
                    ? ['id' => $op->storage->id, 'name' => $op->storage->name]
                    : null,
            ];
        })->values()->all();
    }
}
