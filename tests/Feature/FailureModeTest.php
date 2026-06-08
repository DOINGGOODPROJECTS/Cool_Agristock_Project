<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\InventoryOp;
use App\Models\InventoryStock;
use App\Models\SyncAuditLog;
use App\Models\SyncPermission;
use App\Models\SyncSession;
use App\Services\Sync\ReconciliationEngine;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;

/**
 * S15-01 → S15-10: Failure-mode tests.
 *
 * Each test targets a specific edge-case or adversarial scenario and confirms
 * the engine, API, and audit trail all behave correctly under that condition.
 */
class FailureModeTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private User $admin;
    private int  $storageId;
    private int  $productId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();

        $this->user  = User::factory()->create(['group_id' => 5, 'phone' => '0' . rand(100000000, 999999999)]);
        $this->admin = User::factory()->create(['group_id' => 1, 'phone' => '0' . rand(100000000, 999999999)]);

        $this->storageId = \App\Models\Storage::orderBy('id')->value('id');
        $this->productId = \App\Models\Product::orderBy('id')->value('id');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  S15-01 — Extended offline: queue 10 ops, reconnect, confirm all saved
    // ══════════════════════════════════════════════════════════════════════

    public function test_ten_queued_ops_all_land_in_inventory_ops_and_audit_log(): void
    {
        $ops = array_map(
            fn($i) => $this->buildOp(['logical_seq' => $i]),
            range(1, 10)
        );

        $this->pushOps($ops)
             ->assertStatus(200)
             ->assertJsonPath('applied_count', 10)
             ->assertJsonPath('conflict_count', 0)
             ->assertJsonPath('already_seen_count', 0);

        foreach ($ops as $op) {
            $this->assertDatabaseHas('inventory_ops', ['op_id' => $op['op_id']]);

            $this->assertDatabaseHas('sync_audit_log', [
                'op_id'  => $op['op_id'],
                'action' => 'submitted',
            ]);
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    //  S15-02 — Mid-sync drop: resubmit same ops, confirm no duplicates
    // ══════════════════════════════════════════════════════════════════════

    public function test_resubmitting_same_ops_after_mid_sync_drop_produces_no_duplicates(): void
    {
        $ops = array_map(
            fn($i) => $this->buildOp(['logical_seq' => $i]),
            range(1, 3)
        );

        // First push — simulates initial sync
        $this->pushOps($ops)
             ->assertStatus(200)
             ->assertJsonPath('applied_count',      3)
             ->assertJsonPath('already_seen_count', 0);

        // Second push with identical op_ids — simulates retry after connection drop
        $this->pushOps($ops)
             ->assertStatus(200)
             ->assertJsonPath('applied_count',      0)
             ->assertJsonPath('already_seen_count', 3);

        // Each op appears exactly once
        foreach ($ops as $op) {
            $this->assertEquals(
                1,
                InventoryOp::where('op_id', $op['op_id'])->count(),
                "op {$op['op_id']} must appear exactly once"
            );
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    //  S15-03 — Clock skew: two devices with very different logical clocks
    // ══════════════════════════════════════════════════════════════════════

    public function test_clock_skew_both_devices_ops_accepted_with_correct_logical_seq(): void
    {
        // Device A has a low clock (e.g. just reset)
        $deviceAOps = array_map(
            fn($i) => $this->buildOp(['device_id' => 'web-device-A', 'logical_seq' => $i]),
            range(1, 3)
        );

        // Device B has a very high clock (drifted far ahead)
        $deviceBOps = array_map(
            fn($i) => $this->buildOp(['device_id' => 'web-device-B', 'logical_seq' => 99990 + $i]),
            range(1, 3)
        );

        $this->pushOps($deviceAOps)->assertStatus(200)->assertJsonPath('applied_count', 3);
        $this->pushOps($deviceBOps)->assertStatus(200)->assertJsonPath('applied_count', 3);

        // logical_seq values preserved exactly as sent by each device
        foreach ($deviceAOps as $op) {
            $this->assertDatabaseHas('inventory_ops', [
                'op_id'       => $op['op_id'],
                'device_id'   => 'web-device-A',
                'logical_seq' => $op['logical_seq'],
            ]);
        }
        foreach ($deviceBOps as $op) {
            $this->assertDatabaseHas('inventory_ops', [
                'op_id'       => $op['op_id'],
                'device_id'   => 'web-device-B',
                'logical_seq' => $op['logical_seq'],
            ]);
        }

        // Device A's highest seq < device B's lowest seq (skew preserved, not normalised)
        $maxA = InventoryOp::whereIn('op_id', collect($deviceAOps)->pluck('op_id'))->max('logical_seq');
        $minB = InventoryOp::whereIn('op_id', collect($deviceBOps)->pluck('op_id'))->min('logical_seq');

        $this->assertLessThan($minB, $maxA,
            'Device A logical_seq must sit entirely below device B — clock skew preserved, not flattened');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  S15-04 — Concurrent adjustments: same product+storage, both offline
    // ══════════════════════════════════════════════════════════════════════

    public function test_concurrent_adjustments_on_same_product_storage_conflict_surfaced(): void
    {
        // Device A applies an adjustment first
        $opA = $this->buildOp([
            'op_type'   => 'adjustment',
            'device_id' => 'web-device-A',
            'logical_seq' => 10,
        ]);
        $this->pushOps([$opA])
             ->assertStatus(200)
             ->assertJsonPath('applied_count', 1);

        // Device B was offline at the same time and now pushes its own adjustment
        // The engine finds device A's applied adjustment as a rival → conflict
        $opB = $this->buildOp([
            'op_type'   => 'adjustment',
            'device_id' => 'web-device-B',
            'logical_seq' => 11,
        ]);
        $this->pushOps([$opB])
             ->assertStatus(200)
             ->assertJsonPath('conflict_count', 1)
             ->assertJsonPath('applied_count',  0);

        // Both ops are preserved in inventory_ops
        $this->assertDatabaseHas('inventory_ops', ['op_id' => $opA['op_id'], 'sync_status' => 'applied']);
        $this->assertDatabaseHas('inventory_ops', ['op_id' => $opB['op_id'], 'sync_status' => 'conflict']);

        // Both have 'submitted' audit entries
        $this->assertDatabaseHas('sync_audit_log', ['op_id' => $opA['op_id'], 'action' => 'submitted']);
        $this->assertDatabaseHas('sync_audit_log', ['op_id' => $opB['op_id'], 'action' => 'submitted']);

        // Conflict is flagged in the log for opB
        $this->assertDatabaseHas('sync_audit_log', ['op_id' => $opB['op_id'], 'action' => 'conflict_flagged']);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  S15-05 — Negative stock: stock_out exceeds available quantity
    // ══════════════════════════════════════════════════════════════════════

    public function test_stock_out_exceeding_current_quantity_flagged_stock_unchanged_log_written(): void
    {
        // No InventoryStock row exists → currentQty = 0
        // quantity_delta = -100 → projected = 0 + (-100) = -100 < 0 → conflict
        $op = $this->buildOp([
            'op_type'        => 'stock_out',
            'quantity_delta' => -100,
        ]);

        $this->pushOps([$op])
             ->assertStatus(200)
             ->assertJsonPath('conflict_count', 1)
             ->assertJsonPath('applied_count',  0);

        // Op exists but is flagged, not applied
        $stored = InventoryOp::where('op_id', $op['op_id'])->first();
        $this->assertNotNull($stored);
        $this->assertEquals('conflict', $stored->sync_status);
        $this->assertStringContainsStringIgnoringCase('negative', $stored->conflict_reason);

        // InventoryStock row NOT touched by this op
        $this->assertDatabaseMissing('inventory_stock', ['last_op_id' => $op['op_id']]);

        // Audit log entries written: submitted + conflict_flagged
        $this->assertDatabaseHas('sync_audit_log', ['op_id' => $op['op_id'], 'action' => 'submitted']);
        $this->assertDatabaseHas('sync_audit_log', ['op_id' => $op['op_id'], 'action' => 'conflict_flagged']);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  S15-06 — Permission boundary: member tries to accept a conflict
    // ══════════════════════════════════════════════════════════════════════

    public function test_member_cannot_accept_conflict_and_no_log_entry_written(): void
    {
        $op = $this->makeConflictOp($this->user);

        $logsBefore = SyncAuditLog::where('op_id', $op->op_id)->count();

        $this->actingAs($this->user)
             ->post(route('inventory-ops.accept', $op->op_id))
             ->assertStatus(403)
             ->assertJson(['error' => 'forbidden', 'action' => 'sync.accept']);

        // Op status unchanged
        $this->assertDatabaseHas('inventory_ops', [
            'op_id'       => $op->op_id,
            'sync_status' => 'conflict',
        ]);

        // No new log entries (middleware blocked before controller ran)
        $this->assertEquals(
            $logsBefore,
            SyncAuditLog::where('op_id', $op->op_id)->count(),
            'No audit log entry must be written when the action is rejected at middleware level'
        );
    }

    // ══════════════════════════════════════════════════════════════════════
    //  S15-07 — Cancel after apply: applied op cannot be cancelled
    // ══════════════════════════════════════════════════════════════════════

    public function test_cancelling_applied_op_is_rejected_and_status_unchanged(): void
    {
        $op = $this->buildOp(['logical_seq' => 1]);
        $this->pushOps([$op])->assertStatus(200);

        $applied = InventoryOp::where('op_id', $op['op_id'])->first();
        $this->assertEquals('applied', $applied->sync_status, 'Precondition: op must be applied');

        // InvalidArgumentException from engine → 500
        $this->actingAs($this->user)
             ->post(route('inventory-ops.cancel', $applied->op_id))
             ->assertStatus(500);

        $this->assertDatabaseHas('inventory_ops', [
            'op_id'       => $applied->op_id,
            'sync_status' => 'applied',
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  S15-08 — Edit after apply: applied op cannot be edited
    // ══════════════════════════════════════════════════════════════════════

    public function test_editing_applied_op_is_rejected_and_quantity_unchanged(): void
    {
        $op = $this->buildOp(['logical_seq' => 1, 'quantity_delta' => 10]);
        $this->pushOps([$op])->assertStatus(200);

        $applied = InventoryOp::where('op_id', $op['op_id'])->first();
        $this->assertEquals('applied', $applied->sync_status, 'Precondition: op must be applied');

        // InvalidArgumentException from engine → 500
        $this->actingAs($this->user)
             ->put(route('inventory-ops.edit', $applied->op_id), [
                 'quantity_delta' => 999,
                 'reason'         => 'attempt to edit applied op',
             ])
             ->assertStatus(500);

        $this->assertDatabaseHas('inventory_ops', [
            'op_id'          => $applied->op_id,
            'quantity_delta' => 10, // original value unchanged
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  S15-09 — SMS + PWA concurrent: both reconcile, both in audit log
    // ══════════════════════════════════════════════════════════════════════

    public function test_sms_and_pwa_ops_submitted_concurrently_both_reconcile_and_both_logged(): void
    {
        // Register a phone for the user
        $phone = '+2250799' . rand(100000, 999999);
        \App\Models\MemberPhone::firstOrCreate(
            ['phone'   => $phone],
            ['user_id' => $this->user->id, 'verified_at' => now()]
        );

        // Suppress real AT SDK calls
        config([
            'services.africastalking.api_key'    => 'sandbox',
            'services.africastalking.webhook_key' => null,
        ]);

        $product = \App\Models\Product::orderBy('id')->first();

        // --- SMS op (comes through /webhook/sms) ---
        $this->post('/webhook/sms', [
            'from' => $phone,
            'to'   => '20098',
            'text' => "ENTREE 25 kg {$product->name} S1",
            'date' => now()->toIso8601String(),
            'id'   => 'AT-concurrent-test',
        ])->assertStatus(200);

        // --- PWA op (comes through /api/sync/push) ---
        $pwaOp = $this->buildOp(['device_id' => 'web-pwa-device', 'logical_seq' => 9999]);
        $this->pushOps([$pwaOp])->assertStatus(200);

        // Both in inventory_ops
        $smsOp = InventoryOp::where('device_id', "sms:{$phone}")->first();
        $this->assertNotNull($smsOp, 'SMS op must appear in inventory_ops');
        $this->assertDatabaseHas('inventory_ops', ['op_id' => $pwaOp['op_id']]);

        // Both have submitted audit log entries
        $this->assertDatabaseHas('sync_audit_log', ['op_id' => $smsOp->op_id,   'action' => 'submitted']);
        $this->assertDatabaseHas('sync_audit_log', ['op_id' => $pwaOp['op_id'], 'action' => 'submitted']);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  S15-10 — Large queue: 500 ops, all recorded, all audit entries written
    // ══════════════════════════════════════════════════════════════════════

    public function test_500_ops_batch_all_recorded_and_all_audit_entries_written(): void
    {
        $ops = array_map(
            fn($i) => $this->buildOp(['logical_seq' => $i]),
            range(1, 500)
        );

        // Drive the engine directly to avoid HTTP validation overhead on 500 items
        $session = SyncSession::create([
            'session_id'         => (string) Str::uuid(),
            'user_id'            => $this->user->id,
            'device_id'          => 'web-test-device',
            'ops_submitted'      => 0,
            'ops_applied'        => 0,
            'ops_conflicted'     => 0,
            'status'             => 'in_progress',
            'client_logical_seq' => 500,
        ]);

        $engine = app(ReconciliationEngine::class);
        $counts = $engine->processBatch($ops, $this->user, $session);

        $this->assertEquals(500, $counts[ReconciliationEngine::RESULT_APPLIED],  '500 ops must be applied');
        $this->assertEquals(0,   $counts[ReconciliationEngine::RESULT_CONFLICT], 'No conflicts expected');

        $opIds = collect($ops)->pluck('op_id');

        // All 500 in inventory_ops
        $this->assertEquals(500, InventoryOp::whereIn('op_id', $opIds)->count(),
            'All 500 ops must be in inventory_ops');

        // All 500 have submitted audit log entries
        $this->assertEquals(500,
            SyncAuditLog::whereIn('op_id', $opIds)->where('action', 'submitted')->count(),
            'All 500 ops must have a submitted audit log entry');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  Helpers
    // ══════════════════════════════════════════════════════════════════════

    private function buildOp(array $overrides = []): array
    {
        return array_merge([
            'op_id'             => (string) Str::uuid(),
            'user_id'           => $this->user->id,
            'device_id'         => 'web-test-device',
            'logical_seq'       => rand(1, 9999),
            'storage_id'        => $this->storageId,
            'product_id'        => $this->productId,
            'stock_id'          => null,
            'op_type'           => 'stock_in',
            'quantity_delta'    => 10,
            'unit'              => 'kg',
            'notes'             => null,
            'client_created_at' => now()->toIso8601String(),
        ], $overrides);
    }

    private function pushOps(array $ops, ?string $lastSyncAt = null, ?User $as = null): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($as ?? $this->user, 'sanctum')
            ->postJson('/api/sync/push', [
                'session_id'   => (string) Str::uuid(),
                'device_id'    => 'web-test-device',
                'last_sync_at' => $lastSyncAt,
                'ops'          => $ops,
            ]);
    }

    private function makeConflictOp(User $owner): InventoryOp
    {
        return InventoryOp::create([
            'op_id'          => (string) Str::uuid(),
            'user_id'        => $owner->id,
            'device_id'      => 'web-test-device',
            'logical_seq'    => rand(1, 9999),
            'storage_id'     => $this->storageId,
            'product_id'     => $this->productId,
            'op_type'        => 'stock_in',
            'quantity_delta' => 50,
            'unit'           => 'kg',
            'sync_status'    => 'conflict',
            'conflict_reason' => 'test conflict',
        ]);
    }

    private function seedPermissions(): void
    {
        $config = config('sync_permissions.groups');
        foreach ($config as $groupId => $group) {
            foreach ($group['actions'] as $action => $allowed) {
                SyncPermission::updateOrCreate(
                    ['group_id' => $groupId, 'action' => $action],
                    ['allowed'  => $allowed]
                );
            }
        }
    }
}
