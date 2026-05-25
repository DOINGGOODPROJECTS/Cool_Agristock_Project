<?php

namespace App\Services\Sync;

use App\Models\InventoryOp;
use App\Models\SyncAuditLog;
use App\Models\User;

class AuditLogService
{
    /**
     * Append an immutable entry to sync_audit_log.
     *
     * Design rule: call this BEFORE applying the state mutation so that
     * before_value is auto-captured from the op's current DB state.
     * Pass options['after'] with the intended post-mutation snapshot.
     *
     * Options:
     *   before     array|null  Explicit before override. Pass null for 'submitted'
     *                          (op has no prior state). Omit to auto-snapshot.
     *   after      array|null  State after the action — caller supplies.
     *   reason     string      Human note for manager/admin actions.
     *   device_id  string      Source device; defaults to 'web'.
     *   ip_address string|null Client IP address.
     */
    public function log(
        string $action,
        InventoryOp $op,
        User $actor,
        array $options = []
    ): void {
        // 'submitted' never has a before-state; all other actions auto-snapshot
        // the op's current state unless the caller overrides with options['before'].
        if (array_key_exists('before', $options)) {
            $before = $options['before'];
        } elseif ($action === 'submitted') {
            $before = null;
        } else {
            $before = $this->snapshot($op);
        }

        SyncAuditLog::create([
            'op_id'          => $op->op_id,
            'actor_id'       => $actor->id,
            'actor_group_id' => $actor->group_id,   // snapshot — accurate even if group changes later
            'action'         => $action,
            'before_value'   => $before,
            'after_value'    => $options['after']     ?? null,
            'reason'         => $options['reason']    ?? null,
            'device_id'      => $options['device_id'] ?? 'web',
            'ip_address'     => $options['ip_address'] ?? null,
        ]);
    }

    /**
     * Snapshot the mutable state fields of an op.
     * Public so controllers can capture before-state when they must apply
     * the mutation before calling log() (e.g. when they need the fresh model).
     * Null fields are omitted to keep the JSON compact.
     */
    public function snapshot(InventoryOp $op): array
    {
        return array_filter([
            'sync_status'         => $op->sync_status,
            'quantity_delta'      => (float) $op->quantity_delta,
            'notes'               => $op->notes,
            'conflict_reason'     => $op->conflict_reason,
            'conflict_with_op_id' => $op->conflict_with_op_id,
        ], fn($v) => $v !== null);
    }
}
