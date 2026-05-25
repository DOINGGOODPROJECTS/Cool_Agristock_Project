<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\InventoryOp;
use App\Models\SyncAuditLog;
use App\Services\Sync\AuditLogService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AuditLogServiceTest extends TestCase
{
    use DatabaseTransactions;

    private AuditLogService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AuditLogService();
    }

    // ── 1. submitted ──────────────────────────────────────────────────────

    public function test_submitted_creates_log_with_null_before_and_op_snapshot_after(): void
    {
        $user = $this->makeUser(groupId: 5);
        $op   = $this->makeOp($user, 'pending');

        $this->service->log('submitted', $op, $user, [
            'before'     => null,
            'after'      => $this->service->snapshot($op),
            'device_id'  => 'sms:0612345678',
            'ip_address' => '192.168.1.1',
        ]);

        $row = $this->latestLog($op, 'submitted');

        $this->assertNull($row->before_value);
        $this->assertSame('pending', $row->after_value['sync_status']);
        $this->assertEquals(100.0, $row->after_value['quantity_delta']);
        $this->assertSame('sms:0612345678', $row->device_id);
        $this->assertSame('192.168.1.1', $row->ip_address);
        $this->assertSame($user->group_id, $row->actor_group_id);
    }

    // ── 2. applied ────────────────────────────────────────────────────────

    public function test_applied_records_pending_before_and_applied_after(): void
    {
        $user = $this->makeUser(groupId: 5);
        $op   = $this->makeOp($user, 'pending');

        // Log BEFORE mutation — service auto-captures current state as before_value
        $this->service->log('applied', $op, $user, [
            'after' => ['sync_status' => 'applied'],
        ]);

        $op->update(['sync_status' => 'applied']);

        $row = $this->latestLog($op, 'applied');

        $this->assertSame('pending', $row->before_value['sync_status']);
        $this->assertSame('applied', $row->after_value['sync_status']);
    }

    // ── 3. conflict_flagged ───────────────────────────────────────────────

    public function test_conflict_flagged_records_pending_before_and_conflict_after(): void
    {
        $user = $this->makeUser(groupId: 5);
        $op   = $this->makeOp($user, 'pending');

        $conflictReason = 'Quantity already applied by op abc-123';

        $this->service->log('conflict_flagged', $op, $user, [
            'after' => [
                'sync_status'     => 'conflict',
                'conflict_reason' => $conflictReason,
            ],
        ]);

        $op->update([
            'sync_status'         => 'conflict',
            'conflict_reason'     => $conflictReason,
            'conflict_with_op_id' => 'abc-123',
        ]);

        $row = $this->latestLog($op, 'conflict_flagged');

        $this->assertSame('pending', $row->before_value['sync_status']);
        $this->assertSame('conflict', $row->after_value['sync_status']);
        $this->assertSame($conflictReason, $row->after_value['conflict_reason']);
    }

    // ── 4. reconciled ─────────────────────────────────────────────────────

    public function test_reconciled_records_session_summary_as_after(): void
    {
        $admin = $this->makeUser(groupId: 1);
        $op    = $this->makeOp($admin, 'pending');

        $sessionSummary = [
            'session_id'      => 'sess-' . uniqid(),
            'ops_submitted'   => 5,
            'ops_applied'     => 3,
            'ops_conflicted'  => 2,
        ];

        $this->service->log('reconciled', $op, $admin, [
            'before' => null,
            'after'  => $sessionSummary,
        ]);

        $row = $this->latestLog($op, 'reconciled');

        $this->assertNull($row->before_value);
        $this->assertSame(3, $row->after_value['ops_applied']);
        $this->assertSame(2, $row->after_value['ops_conflicted']);
        $this->assertSame(1, $row->actor_group_id);
    }

    // ── 5. accepted ───────────────────────────────────────────────────────

    public function test_accepted_records_conflict_before_and_applied_after(): void
    {
        $manager = $this->makeUser(groupId: 2);
        $user    = $this->makeUser(groupId: 5);
        $op      = $this->makeOp($user, 'conflict');

        // Log before mutation
        $this->service->log('accepted', $op, $manager, [
            'after' => ['sync_status' => 'applied'],
        ]);

        $op->update(['sync_status' => 'applied', 'resolved_by' => $manager->id]);

        $row = $this->latestLog($op, 'accepted');

        $this->assertSame('conflict', $row->before_value['sync_status']);
        $this->assertSame('applied', $row->after_value['sync_status']);
        $this->assertSame($manager->id, $row->actor_id);
        $this->assertSame(2, $row->actor_group_id);
    }

    // ── 6. discarded ──────────────────────────────────────────────────────

    public function test_discarded_records_conflict_before_and_superseded_after(): void
    {
        $manager = $this->makeUser(groupId: 2);
        $user    = $this->makeUser(groupId: 5);
        $op      = $this->makeOp($user, 'conflict');

        $this->service->log('discarded', $op, $manager, [
            'after' => ['sync_status' => 'superseded'],
        ]);

        $op->update(['sync_status' => 'superseded', 'resolved_by' => $manager->id]);

        $row = $this->latestLog($op, 'discarded');

        $this->assertSame('conflict', $row->before_value['sync_status']);
        $this->assertSame('superseded', $row->after_value['sync_status']);
        $this->assertSame($manager->group_id, $row->actor_group_id);
    }

    // ── 7. cancelled ──────────────────────────────────────────────────────

    public function test_cancelled_records_pending_before_and_cancelled_after(): void
    {
        $owner = $this->makeUser(groupId: 5);
        $op    = $this->makeOp($owner, 'pending');

        $this->service->log('cancelled', $op, $owner, [
            'after' => ['sync_status' => 'cancelled'],
        ]);

        $op->update([
            'sync_status'  => 'cancelled',
            'cancelled_by' => $owner->id,
        ]);

        $row = $this->latestLog($op, 'cancelled');

        $this->assertSame('pending', $row->before_value['sync_status']);
        $this->assertSame('cancelled', $row->after_value['sync_status']);
        $this->assertSame($owner->id, $row->actor_id);
    }

    // ── 8. merged ─────────────────────────────────────────────────────────

    public function test_merged_records_both_op_snapshots_in_before(): void
    {
        $admin = $this->makeUser(groupId: 1);
        $userA = $this->makeUser(groupId: 5);
        $userB = $this->makeUser(groupId: 6);
        $opA   = $this->makeOp($userA, 'conflict', 100.0);
        $opB   = $this->makeOp($userB, 'conflict', 80.0);

        $this->service->log('merged', $opA, $admin, [
            'before' => [
                'op_a' => $this->service->snapshot($opA),
                'op_b' => $this->service->snapshot($opB),
            ],
            'after'  => [
                'merged_op_id'   => $opA->op_id,
                'quantity_delta' => 100.0,
            ],
            'reason' => 'Manual merge: op_b superseded by op_a',
        ]);

        $row = $this->latestLog($opA, 'merged');

        $this->assertSame('conflict', $row->before_value['op_a']['sync_status']);
        $this->assertSame('conflict', $row->before_value['op_b']['sync_status']);
        $this->assertEquals(100.0, $row->before_value['op_a']['quantity_delta']);
        $this->assertEquals(80.0,  $row->before_value['op_b']['quantity_delta']);
        $this->assertEquals(100.0, $row->after_value['quantity_delta']);
        $this->assertSame('Manual merge: op_b superseded by op_a', $row->reason);
        $this->assertSame(1, $row->actor_group_id);
    }

    // ── 9. edited ─────────────────────────────────────────────────────────

    public function test_edited_records_old_qty_before_and_new_qty_after(): void
    {
        $user = $this->makeUser(groupId: 5);
        $op   = $this->makeOp($user, 'pending', 100.0);

        $this->service->log('edited', $op, $user, [
            'before' => $op->only(['quantity_delta', 'notes']),
            'after'  => ['quantity_delta' => '75.000', 'notes' => 'corrected'],
            'reason' => 'Typo in original entry',
        ]);

        $op->update(['quantity_delta' => 75.0, 'notes' => 'corrected']);

        $row = $this->latestLog($op, 'edited');

        $this->assertEquals(100.0, $row->before_value['quantity_delta']);
        $this->assertSame('75.000', $row->after_value['quantity_delta']);
        $this->assertSame('corrected', $row->after_value['notes']);
        $this->assertSame('Typo in original entry', $row->reason);
    }

    // ── 10. overridden ────────────────────────────────────────────────────

    public function test_overridden_records_old_qty_before_and_override_after_with_reason(): void
    {
        $manager = $this->makeUser(groupId: 2);
        $user    = $this->makeUser(groupId: 5);
        $op      = $this->makeOp($user, 'conflict', 100.0);

        // Log BEFORE override — auto-captures current qty as before_value
        $this->service->log('overridden', $op, $manager, [
            'after'  => ['quantity_delta' => 60.0],
            'reason' => 'Physical count verified: 60 kg on-site',
        ]);

        $op->update(['quantity_delta' => 60.0]);

        $row = $this->latestLog($op, 'overridden');

        $this->assertEquals(100.0, $row->before_value['quantity_delta']);
        $this->assertEquals(60.0, $row->after_value['quantity_delta']);
        $this->assertSame('Physical count verified: 60 kg on-site', $row->reason);
        $this->assertSame($manager->group_id, $row->actor_group_id);
    }

    // ── 11. actor_group_id is a snapshot ─────────────────────────────────

    public function test_actor_group_id_is_snapshot_and_survives_group_change(): void
    {
        $user = $this->makeUser(groupId: 3);
        $op   = $this->makeOp($user, 'pending');

        // Log while user is in group 3
        $this->service->log('submitted', $op, $user, [
            'before' => null,
            'after'  => $this->service->snapshot($op),
        ]);

        // Simulate group change AFTER logging
        $user->update(['group_id' => 5]);

        $row = $this->latestLog($op, 'submitted');

        // Log must still show original group 3, not the new group 5
        $this->assertSame(3, $row->actor_group_id);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function makeUser(int $groupId): User
    {
        return User::factory()->create([
            'group_id' => $groupId,
            'phone'    => '0' . rand(100000000, 999999999),
        ]);
    }

    private function makeOp(User $user, string $status, float $qty = 100.0): InventoryOp
    {
        $storageId = \App\Models\Storage::firstOrCreate(
            ['name' => 'Test Storage'],
            ['location' => 'Test Location', 'capacity' => 1000]
        )->id;

        $productId = \App\Models\Product::first()?->id ?? 1;

        return InventoryOp::create([
            'op_id'          => \Str::uuid(),
            'user_id'        => $user->id,
            'device_id'      => 'test-device',
            'logical_seq'    => rand(1, 9999),
            'storage_id'     => $storageId,
            'product_id'     => $productId,
            'op_type'        => 'stock_in',
            'quantity_delta' => $qty,
            'unit'           => 'kg',
            'sync_status'    => $status,
        ]);
    }

    private function latestLog(InventoryOp $op, string $action): SyncAuditLog
    {
        $row = SyncAuditLog::where('op_id', $op->op_id)
            ->where('action', $action)
            ->latest('id')
            ->first();

        $this->assertNotNull($row, "No [{$action}] log entry found for op [{$op->op_id}]");

        return $row;
    }
}
