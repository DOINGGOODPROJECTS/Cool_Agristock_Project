<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Storage;
use App\Models\SyncAuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncAuditLogController extends Controller
{
    /** All known action values for the filter dropdown. */
    private const ACTIONS = [
        'submitted', 'applied', 'conflict_flagged', 'accepted',
        'discarded', 'cancelled', 'merged', 'edited',
        'overridden', 'superseded', 'reconciled',
    ];

    public function index(Request $request)
    {
        $request->validate([
            'action'     => 'nullable|string|in:' . implode(',', self::ACTIONS),
            'user_id'    => 'nullable|integer|exists:users,id',
            'storage_id' => 'nullable|integer',
            'product_id' => 'nullable|integer',
            'date_from'  => 'nullable|date',
            'date_to'    => 'nullable|date|after_or_equal:date_from',
        ]);

        $query = SyncAuditLog::with('actor')
            ->when($request->action,     fn($q) => $q->where('action', $request->action))
            ->when($request->user_id,    fn($q) => $q->where('actor_id', $request->user_id))
            ->when($request->date_from,  fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to,    fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->when($request->storage_id || $request->product_id,
                fn($q) => $q->whereHas('inventoryOp', function ($sub) use ($request) {
                    $sub->when($request->storage_id, fn($s) => $s->where('storage_id', $request->storage_id))
                        ->when($request->product_id,  fn($s) => $s->where('product_id',  $request->product_id));
                })
            )
            ->orderByDesc('created_at');

        $logs     = $query->get();
        $filters  = $request->only(['action', 'user_id', 'storage_id', 'product_id', 'date_from', 'date_to']);
        $users    = User::orderBy('name')->get(['id', 'name']);
        $storages = Storage::whereNull('deleted_at')->orderBy('name')->get(['id', 'name']);
        $products = Product::orderBy('name')->get(['id', 'name']);
        $actions  = self::ACTIONS;

        return view('admin.sync-audit-log',
            compact('logs', 'filters', 'users', 'storages', 'products', 'actions'));
    }

    public function forOp(string $opId): JsonResponse
    {
        $logs = SyncAuditLog::where('op_id', $opId)
            ->with('actor')
            ->orderBy('id')
            ->get()
            ->map(fn($log) => [
                'action'         => $log->action,
                'actor'          => $log->actor->name ?? '—',
                'actor_group_id' => $log->actor_group_id,
                'device_id'      => $log->device_id,
                'reason'         => $log->reason,
                'before_value'   => $log->before_value,
                'after_value'    => $log->after_value,
                'created_at'     => $log->created_at?->format('d/m/Y H:i:s'),
            ]);

        return response()->json($logs);
    }

    public function export(Request $request)
    {
        if (auth()->user()->group_id !== 1) {
            abort(403);
        }

        $request->validate([
            'action'     => 'nullable|string|in:' . implode(',', self::ACTIONS),
            'user_id'    => 'nullable|integer',
            'storage_id' => 'nullable|integer',
            'product_id' => 'nullable|integer',
            'date_from'  => 'nullable|date',
            'date_to'    => 'nullable|date',
        ]);

        $query = SyncAuditLog::with('actor')
            ->when($request->action,     fn($q) => $q->where('action', $request->action))
            ->when($request->user_id,    fn($q) => $q->where('actor_id', $request->user_id))
            ->when($request->date_from,  fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to,    fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->when($request->storage_id || $request->product_id,
                fn($q) => $q->whereHas('inventoryOp', function ($sub) use ($request) {
                    $sub->when($request->storage_id, fn($s) => $s->where('storage_id', $request->storage_id))
                        ->when($request->product_id,  fn($s) => $s->where('product_id',  $request->product_id));
                })
            )
            ->orderByDesc('created_at');

        $filename = 'sync_audit_log_' . now()->format('Ymd_His') . '.csv';

        return response()->stream(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

            fputcsv($out, ['ID', 'Op ID', 'Acteur', 'Groupe', 'Action', 'Appareil', 'IP', 'Motif', 'Créé le']);

            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $log) {
                    fputcsv($out, [
                        $log->id,
                        $log->op_id,
                        $log->actor->name ?? '—',
                        $log->actor_group_id,
                        $log->action,
                        $log->device_id,
                        $log->ip_address ?? '—',
                        $log->reason     ?? '—',
                        $log->created_at,
                    ]);
                }
            });

            fclose($out);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-cache, no-store',
        ]);
    }
}
