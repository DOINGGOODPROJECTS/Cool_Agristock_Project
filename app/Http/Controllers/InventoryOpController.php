<?php

namespace App\Http\Controllers;

use App\Models\InventoryOp;
use App\Services\Sync\AuditLogService;
use App\Services\Sync\ReconciliationEngine;
use App\Services\Sync\SyncPermissionService;
use Illuminate\Http\Request;

class InventoryOpController extends Controller
{
    public function __construct(
        private SyncPermissionService $perms,
        private AuditLogService       $audit,
        private ReconciliationEngine  $engine,
    ) {}

    public function index()
    {
        $ops = InventoryOp::with('user', 'storage', 'product')
            ->when(auth()->user()->group_id > 4, fn($q) => $q->where('user_id', auth()->id()))
            ->orderByDesc('server_received_at')
            ->get();

        return view('admin.inventory-ops', compact('ops'));
    }

    public function accept(string $opId)
    {
        $op = InventoryOp::where('op_id', $opId)->firstOrFail();
        $this->engine->acceptConflict($op, auth()->user(), request('reason', ''));
        return redirect()->back()->with('success', 'Op accepted and applied.');
    }

    public function discard(string $opId)
    {
        $op = InventoryOp::where('op_id', $opId)->firstOrFail();
        $this->engine->discardConflict($op, auth()->user(), request('reason', ''));
        return redirect()->back()->with('success', 'Op discarded.');
    }

    public function cancel(string $opId)
    {
        $op = InventoryOp::where('op_id', $opId)->firstOrFail();
        $this->engine->cancelOp($op, auth()->user(), request('reason', ''));
        return redirect()->back()->with('success', 'Op cancelled.');
    }

    public function edit(Request $request, string $opId)
    {
        $op      = InventoryOp::where('op_id', $opId)->firstOrFail();
        $changes = array_filter(
            $request->only(['quantity_delta', 'notes', 'reason']),
            fn($v) => $v !== null && $v !== ''
        );
        $this->engine->editOp($op, $changes, auth()->user());
        return redirect()->back()->with('success', 'Op updated — replacement pending op created.');
    }

    public function merge(Request $request)
    {
        $request->validate([
            'op_a_id'         => 'required|string',
            'op_b_id'         => 'required|string|different:op_a_id',
            'merged_quantity' => 'required|numeric',
            'reason'          => 'required|string|max:500',
        ]);

        $opA = InventoryOp::where('op_id', $request->op_a_id)->firstOrFail();
        $opB = InventoryOp::where('op_id', $request->op_b_id)->firstOrFail();

        $this->engine->mergeOps($opA, $opB, (float) $request->merged_quantity, auth()->user(), $request->reason);

        return redirect()->back()->with('success', 'Ops merged and applied.');
    }

    public function override(Request $request, string $opId)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:0',
        ]);

        $op = InventoryOp::where('op_id', $opId)->firstOrFail();
        $this->engine->applyOverride($op, (float) $request->quantity, auth()->user());

        return redirect()->back()->with('success', 'Override applied to stock.');
    }
}
