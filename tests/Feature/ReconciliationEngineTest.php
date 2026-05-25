<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Stock;
use App\Models\Detail;
use App\Models\InventoryOp;
use App\Models\InventoryStock;
use App\Models\Release;
use App\Models\Rotten;
use App\Models\SyncAuditLog;
use App\Models\SyncPermission;
use App\Models\SyncSession;
use App\Services\Sync\AuditLogService;
use App\Services\Sync\ReconciliationEngine;
use App\Services\Sync\SyncPermissionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ReconciliationEngineTest extends TestCase
{
    use DatabaseTransactions;

    private ReconciliationEngine $engine;
    private \App\Models\Storage  $storage;
    private \App\Models\Product  $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();

        $audit  = new AuditLogService();
        $perms  = new SyncPermissionService();
        $this->engine = new ReconciliationEngine($audit, $perms);

        // Shared storage + product used by most tests
        $this->storage = \App\Models\Storage::firstOrCreate(
            ['name' => 'Test Storage'],
            ['location' => 'Test Location', 'capacity' => 10000]
        );

        $this->product = \App\Models\Product::first();
    }

    // ══════════════════════════════════════════════════════════════════════
    // processOp
    // ══════════════════════════════════════════════════════════════════════

    public function test_process_op_returns_already_seen_for_duplicate(): void
    {
        $user    = $this->makeUser(5);
        $opData  = $this->opData($user, qty: 100.0);

        $first  = $this->engine->processOp($opData, $user);
        $second = $this->engine->processOp($opData, $user);

        $this->assertSame(ReconciliationEngine::RESULT_APPLIED,      $first);
        $this->assertSame(ReconciliationEngine::RESULT_ALREADY_SEEN, $second);

        // Only one row in inventory_ops
        $this->assertSame(1, InventoryOp::where('op_id', $opData['op_id'])->count());
    }

    public function test_process_op_applies_clean_op_and_creates_inventory_stock(): void
    {
        $user   = $this->makeUser(5);
        $stock  = $this->makeStock($user);
        $opData = $this->opData($user, stockId: $stock->id, qty: 150.0);

        $result = $this->engine->processOp($opData, $user);

        $this->assertSame(ReconciliationEngine::RESULT_APPLIED, $result);

        $this->assertDatabaseHas('inventory_ops', [
            'op_id'       => $opData['op_id'],
            'sync_status' => 'applied',
        ]);

        $row = InventoryStock::where('storage_id', $this->storage->id)
            ->where('product_id', $this->product->id)
            ->where('stock_id', $stock->id)
            ->first();

        $this->assertNotNull($row);
        $this->assertEquals(150.0, (float) $row->quantity);
    }

    public function test_process_op_logs_submitted_and_applied(): void
    {
        $user   = $this->makeUser(5);
        $opData = $this->opData($user, qty: 100.0);

        $this->engine->processOp($opData, $user);

        $this->assertSame(1, SyncAuditLog::where('op_id', $opData['op_id'])->where('action', 'submitted')->count());
        $this->assertSame(1, SyncAuditLog::where('op_id', $opData['op_id'])->where('action', 'applied')->count());
    }

    public function test_process_op_flags_concurrent_adjustment_conflict(): void
    {
        $user   = $this->makeUser(5);
        $stock  = $this->makeStock($user);

        // First adjustment — applied cleanly
        $first = $this->opData($user, stockId: $stock->id, type: 'adjustment', qty: 10.0);
        $this->engine->processOp($first, $user);

        // Second adjustment on the same storage+product since last sync
        $lastSync = now()->subMinutes(5);
        $second   = $this->opData($user, stockId: $stock->id, type: 'adjustment', qty: 5.0);

        $result = $this->engine->processOp($second, $user, $lastSync);

        $this->assertSame(ReconciliationEngine::RESULT_CONFLICT, $result);

        $this->assertDatabaseHas('inventory_ops', [
            'op_id'       => $second['op_id'],
            'sync_status' => 'conflict',
        ]);

        $this->assertSame(1, SyncAuditLog::where('op_id', $second['op_id'])->where('action', 'conflict_flagged')->count());
    }

    public function test_process_op_flags_negative_stock_conflict(): void
    {
        $user   = $this->makeUser(5);
        $stock  = $this->makeStock($user);

        // Seed 50 kg already in stock
        InventoryStock::create([
            'storage_id'      => $this->storage->id,
            'product_id'      => $this->product->id,
            'stock_id'        => $stock->id,
            'quantity'        => 50.0,
            'unit'            => 'kg',
            'last_op_id'      => (string) Str::uuid(),
            'last_updated_at' => now(),
        ]);

        // Stock-out of 100 kg would make quantity -50
        $opData = $this->opData($user, stockId: $stock->id, type: 'stock_out', qty: -100.0);

        $result = $this->engine->processOp($opData, $user);

        $this->assertSame(ReconciliationEngine::RESULT_CONFLICT, $result);
        $op = InventoryOp::where('op_id', $opData['op_id'])->first();
        $this->assertStringContainsString('negative', $op->conflict_reason);
    }

    // ══════════════════════════════════════════════════════════════════════
    // detectConflict
    // ══════════════════════════════════════════════════════════════════════

    public function test_detect_conflict_returns_null_for_clean_stock_in(): void
    {
        $user   = $this->makeUser(5);
        $opData = $this->opData($user, qty: 100.0);

        $this->assertNull($this->engine->detectConflict($opData));
    }

    public function test_detect_conflict_catches_adjustment_after_applied_adjustment(): void
    {
        $user  = $this->makeUser(5);
        $stock = $this->makeStock($user);

        // An adjustment was applied 1 minute ago
        $applied = $this->makeOp($user, $stock, 'adjustment', 10.0, 'applied');
        $applied->update(['applied_at' => now()->subMinute()]);

        $newOpData = $this->opData($user, stockId: $stock->id, type: 'adjustment', qty: 5.0);
        $lastSync  = now()->subMinutes(5);

        $conflict = $this->engine->detectConflict($newOpData, $lastSync);

        $this->assertNotNull($conflict);
        $this->assertSame($applied->op_id, $conflict['conflict_with_op_id']);
        $this->assertStringContainsString('Concurrent adjustment', $conflict['reason']);
    }

    public function test_detect_conflict_catches_negative_projection(): void
    {
        $user  = $this->makeUser(5);
        $stock = $this->makeStock($user);

        InventoryStock::create([
            'storage_id'      => $this->storage->id,
            'product_id'      => $this->product->id,
            'stock_id'        => $stock->id,
            'quantity'        => 30.0,
            'unit'            => 'kg',
            'last_op_id'      => (string) Str::uuid(),
            'last_updated_at' => now(),
        ]);

        $opData = $this->opData($user, stockId: $stock->id, type: 'stock_out', qty: -50.0);

        $conflict = $this->engine->detectConflict($opData);

        $this->assertNotNull($conflict);
        $this->assertNull($conflict['conflict_with_op_id']);
        $this->assertStringContainsString('negative', $conflict['reason']);
    }

    // ══════════════════════════════════════════════════════════════════════
    // applyOpToStock
    // ══════════════════════════════════════════════════════════════════════

    public function test_apply_op_to_stock_creates_row_on_first_op(): void
    {
        $user  = $this->makeUser(5);
        $stock = $this->makeStock($user);
        $op    = $this->makeOp($user, $stock, 'stock_in', 200.0, 'pending');

        $this->engine->applyOpToStock($op);

        $row = InventoryStock::where('stock_id', $stock->id)->first();
        $this->assertNotNull($row);
        $this->assertEquals(200.0, (float) $row->quantity);
    }

    public function test_apply_op_to_stock_accumulates_on_subsequent_ops(): void
    {
        $user  = $this->makeUser(5);
        $stock = $this->makeStock($user);

        $op1 = $this->makeOp($user, $stock, 'stock_in', 100.0, 'pending');
        $this->engine->applyOpToStock($op1);

        $op2 = $this->makeOp($user, $stock, 'stock_in', 50.0, 'pending');
        $this->engine->applyOpToStock($op2);

        $row = InventoryStock::where('stock_id', $stock->id)->first();
        $this->assertEquals(150.0, (float) $row->quantity);
    }

    public function test_apply_op_to_stock_skips_when_stock_id_is_null(): void
    {
        $user = $this->makeUser(5);
        $op   = $this->makeOp($user, null, 'stock_in', 100.0, 'pending');

        $countBefore = InventoryStock::count();
        $this->engine->applyOpToStock($op);

        $this->assertSame($countBefore, InventoryStock::count());
    }

    public function test_apply_op_to_stock_writes_rotten_for_spoilage(): void
    {
        $user   = $this->makeUser(5);
        $stock  = $this->makeStock($user);
        $detail = $this->makeDetail($stock);

        // Seed 200 kg first
        InventoryStock::create([
            'storage_id' => $this->storage->id, 'product_id' => $this->product->id,
            'stock_id' => $stock->id, 'quantity' => 200.0, 'unit' => 'kg',
            'last_op_id' => (string) Str::uuid(), 'last_updated_at' => now(),
        ]);

        $op = $this->makeOp($user, $stock, 'spoilage', -20.0, 'pending');
        $this->engine->applyOpToStock($op);

        $this->assertDatabaseHas('rottens', [
            'before_qty' => 200.0,
            'qty'        => 20.0,
            'after_qty'  => 180.0,
            'stock_id'   => $stock->id,
        ]);
    }

    public function test_apply_op_to_stock_writes_release_for_stock_out(): void
    {
        $user   = $this->makeUser(5);
        $stock  = $this->makeStock($user);
        $detail = $this->makeDetail($stock);

        InventoryStock::create([
            'storage_id' => $this->storage->id, 'product_id' => $this->product->id,
            'stock_id' => $stock->id, 'quantity' => 100.0, 'unit' => 'kg',
            'last_op_id' => (string) Str::uuid(), 'last_updated_at' => now(),
        ]);

        $op = $this->makeOp($user, $stock, 'stock_out', -30.0, 'pending');
        $this->engine->applyOpToStock($op);

        $this->assertDatabaseHas('releases', [
            'before_qty' => 100.0,
            'qty'        => 30.0,
            'after_qty'  => 70.0,
            'stock_id'   => $stock->id,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // acceptConflict
    // ══════════════════════════════════════════════════════════════════════

    public function test_accept_conflict_applies_op_and_updates_stock(): void
    {
        $manager = $this->makeUser(2);
        $user    = $this->makeUser(5);
        $stock   = $this->makeStock($user);
        $op      = $this->makeOp($user, $stock, 'stock_in', 80.0, 'conflict');

        $this->engine->acceptConflict($op, $manager, 'Reviewed and approved');

        $op->refresh();
        $this->assertSame('applied', $op->sync_status);
        $this->assertSame($manager->id, $op->resolved_by);

        $row = InventoryStock::where('stock_id', $stock->id)->first();
        $this->assertEquals(80.0, (float) $row->quantity);

        $this->assertSame(1, SyncAuditLog::where('op_id', $op->op_id)->where('action', 'accepted')->count());
    }

    public function test_accept_conflict_throws_for_insufficient_permission(): void
    {
        $this->expectException(AuthorizationException::class);

        $member = $this->makeUser(5);
        $op     = $this->makeOp($member, null, 'stock_in', 80.0, 'conflict');

        $this->engine->acceptConflict($op, $member);
    }

    // ══════════════════════════════════════════════════════════════════════
    // discardConflict
    // ══════════════════════════════════════════════════════════════════════

    public function test_discard_conflict_supersedes_op(): void
    {
        $manager = $this->makeUser(2);
        $user    = $this->makeUser(5);
        $op      = $this->makeOp($user, null, 'stock_in', 50.0, 'conflict');

        $this->engine->discardConflict($op, $manager, 'Duplicate entry');

        $op->refresh();
        $this->assertSame('superseded', $op->sync_status);
        $this->assertSame($manager->id, $op->resolved_by);
        $this->assertSame(1, SyncAuditLog::where('op_id', $op->op_id)->where('action', 'discarded')->count());
    }

    public function test_discard_conflict_throws_for_insufficient_permission(): void
    {
        $this->expectException(AuthorizationException::class);

        $member = $this->makeUser(5);
        $op     = $this->makeOp($member, null, 'stock_in', 50.0, 'conflict');

        // group 5 has sync.discard = true, but let's use a group that doesn't
        $noPerms = $this->makeUser(99); // group 99 has no seeded permissions → false
        $this->engine->discardConflict($op, $noPerms);
    }

    // ══════════════════════════════════════════════════════════════════════
    // cancelOp
    // ══════════════════════════════════════════════════════════════════════

    public function test_cancel_op_sets_cancelled_for_owner(): void
    {
        $owner = $this->makeUser(5);
        $op    = $this->makeOp($owner, null, 'stock_in', 60.0, 'pending');

        $this->engine->cancelOp($op, $owner);

        $op->refresh();
        $this->assertSame('cancelled', $op->sync_status);
        $this->assertSame($owner->id, $op->cancelled_by);
        $this->assertSame(1, SyncAuditLog::where('op_id', $op->op_id)->where('action', 'cancelled')->count());
    }

    public function test_cancel_op_allowed_for_admin_on_any_op(): void
    {
        $admin = $this->makeUser(1);
        $user  = $this->makeUser(5);
        $op    = $this->makeOp($user, null, 'stock_in', 60.0, 'pending');

        $this->engine->cancelOp($op, $admin);

        $op->refresh();
        $this->assertSame('cancelled', $op->sync_status);
    }

    public function test_cancel_op_throws_for_non_owner(): void
    {
        $this->expectException(AuthorizationException::class);

        $owner = $this->makeUser(5);
        $other = $this->makeUser(6);
        $op    = $this->makeOp($owner, null, 'stock_in', 60.0, 'pending');

        $this->engine->cancelOp($op, $other);
    }

    public function test_cancel_op_throws_if_not_pending(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $user = $this->makeUser(5);
        $op   = $this->makeOp($user, null, 'stock_in', 60.0, 'applied');

        $this->engine->cancelOp($op, $user);
    }

    // ══════════════════════════════════════════════════════════════════════
    // mergeOps
    // ══════════════════════════════════════════════════════════════════════

    public function test_merge_ops_creates_merged_op_and_supersedes_originals(): void
    {
        $admin = $this->makeUser(1);
        $userA = $this->makeUser(5);
        $userB = $this->makeUser(6);
        $stock = $this->makeStock($userA);

        $opA = $this->makeOp($userA, $stock, 'adjustment', 100.0, 'conflict');
        $opB = $this->makeOp($userB, $stock, 'adjustment',  80.0, 'conflict');

        $merged = $this->engine->mergeOps($opA, $opB, 90.0, $admin, 'Merged: average of both readings');

        $this->assertNotNull($merged);
        $this->assertSame('applied', $merged->sync_status);
        $this->assertEquals(90.0, (float) $merged->quantity_delta);
        $this->assertSame($opA->op_id, $merged->edited_from_op_id);

        $opA->refresh();
        $opB->refresh();
        $this->assertSame('superseded', $opA->sync_status);
        $this->assertSame('superseded', $opB->sync_status);

        $invStock = InventoryStock::where('stock_id', $stock->id)->first();
        $this->assertEquals(90.0, (float) $invStock->quantity);

        // Both originals get a 'merged' log; merged op gets 'applied' log
        $this->assertSame(1, SyncAuditLog::where('op_id', $opA->op_id)->where('action', 'merged')->count());
        $this->assertSame(1, SyncAuditLog::where('op_id', $opB->op_id)->where('action', 'merged')->count());
        $this->assertSame(1, SyncAuditLog::where('op_id', $merged->op_id)->where('action', 'applied')->count());
    }

    public function test_merge_ops_throws_for_insufficient_permission(): void
    {
        $this->expectException(AuthorizationException::class);

        $supervisor = $this->makeUser(2); // sync.merge = false for group 2
        $userA      = $this->makeUser(5);
        $userB      = $this->makeUser(6);

        $opA = $this->makeOp($userA, null, 'adjustment', 100.0, 'conflict');
        $opB = $this->makeOp($userB, null, 'adjustment',  80.0, 'conflict');

        $this->engine->mergeOps($opA, $opB, 90.0, $supervisor, 'Should fail');
    }

    // ══════════════════════════════════════════════════════════════════════
    // editOp
    // ══════════════════════════════════════════════════════════════════════

    public function test_edit_op_creates_replacement_and_supersedes_original(): void
    {
        $owner = $this->makeUser(5);
        $op    = $this->makeOp($owner, null, 'stock_in', 100.0, 'pending');

        $newOp = $this->engine->editOp($op, [
            'quantity_delta' => 75.0,
            'notes'          => 'corrected weight',
            'reason'         => 'Scale was miscalibrated',
        ], $owner);

        $op->refresh();
        $this->assertSame('superseded', $op->sync_status);
        $this->assertSame('pending',    $newOp->sync_status);
        $this->assertEquals(75.0, (float) $newOp->quantity_delta);
        $this->assertSame($op->op_id, $newOp->edited_from_op_id);

        $log = SyncAuditLog::where('op_id', $op->op_id)->where('action', 'edited')->first();
        $this->assertNotNull($log);
        $this->assertEquals(100.0, (float) $log->before_value['quantity_delta']);
        $this->assertSame('Scale was miscalibrated', $log->reason);
    }

    public function test_edit_op_throws_for_non_owner(): void
    {
        $this->expectException(AuthorizationException::class);

        $owner = $this->makeUser(5);
        $other = $this->makeUser(6);
        $op    = $this->makeOp($owner, null, 'stock_in', 100.0, 'pending');

        $this->engine->editOp($op, ['quantity_delta' => 50.0], $other);
    }

    public function test_edit_op_throws_if_not_pending(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $user = $this->makeUser(5);
        $op   = $this->makeOp($user, null, 'stock_in', 100.0, 'applied');

        $this->engine->editOp($op, ['quantity_delta' => 50.0], $user);
    }

    public function test_edit_op_allowed_for_admin_on_any_pending_op(): void
    {
        $admin = $this->makeUser(1);
        $user  = $this->makeUser(5);
        $op    = $this->makeOp($user, null, 'stock_in', 100.0, 'pending');

        $newOp = $this->engine->editOp($op, ['quantity_delta' => 55.0], $admin);

        $this->assertEquals(55.0, (float) $newOp->quantity_delta);
    }

    // ══════════════════════════════════════════════════════════════════════
    // applyOverride
    // ══════════════════════════════════════════════════════════════════════

    public function test_apply_override_force_sets_inventory_stock_quantity(): void
    {
        $manager = $this->makeUser(2);
        $user    = $this->makeUser(5);
        $stock   = $this->makeStock($user);
        $op      = $this->makeOp($user, $stock, 'adjustment', 100.0, 'conflict');

        // Seed existing qty
        InventoryStock::create([
            'storage_id' => $this->storage->id, 'product_id' => $this->product->id,
            'stock_id' => $stock->id, 'quantity' => 200.0, 'unit' => 'kg',
            'last_op_id' => (string) Str::uuid(), 'last_updated_at' => now(),
        ]);

        $this->engine->applyOverride($op, 175.0, $manager);

        $row = InventoryStock::where('stock_id', $stock->id)->first();
        $this->assertEquals(175.0, (float) $row->quantity);

        $log = SyncAuditLog::where('op_id', $op->op_id)->where('action', 'overridden')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('175', $log->reason);
    }

    public function test_apply_override_throws_for_insufficient_permission(): void
    {
        $this->expectException(AuthorizationException::class);

        $member = $this->makeUser(5);
        // Group 5 cannot sync.accept
        $noAccept = $this->makeUser(99);
        $op = $this->makeOp($member, null, 'adjustment', 100.0, 'conflict');

        $this->engine->applyOverride($op, 50.0, $noAccept);
    }

    // ══════════════════════════════════════════════════════════════════════
    // processBatch
    // ══════════════════════════════════════════════════════════════════════

    public function test_process_batch_returns_correct_counts_and_updates_session(): void
    {
        $user    = $this->makeUser(5);
        $stock   = $this->makeStock($user);
        $session = $this->makeSession($user);

        $ops = [
            $this->opData($user, stockId: $stock->id, qty:  100.0), // will apply
            $this->opData($user, stockId: $stock->id, qty:   50.0), // will apply
        ];

        $counts = $this->engine->processBatch($ops, $user, $session);

        $this->assertSame(2, $counts[ReconciliationEngine::RESULT_APPLIED]);
        $this->assertSame(0, $counts[ReconciliationEngine::RESULT_CONFLICT]);
        $this->assertSame(0, $counts[ReconciliationEngine::RESULT_ALREADY_SEEN]);

        $session->refresh();
        $this->assertSame(2, $session->ops_submitted);
        $this->assertSame(2, $session->ops_applied);
        $this->assertSame('completed', $session->status);
    }

    public function test_process_batch_logs_one_reconciled_entry(): void
    {
        $user    = $this->makeUser(5);
        $session = $this->makeSession($user);
        $ops     = [$this->opData($user, qty: 50.0)];

        $this->engine->processBatch($ops, $user, $session);

        $firstOpId = $ops[0]['op_id'];
        $this->assertSame(1, SyncAuditLog::where('op_id', $firstOpId)->where('action', 'reconciled')->count());

        $log = SyncAuditLog::where('op_id', $firstOpId)->where('action', 'reconciled')->first();
        $this->assertSame($session->session_id, $log->after_value['session_id']);
    }

    // ══════════════════════════════════════════════════════════════════════
    // Helpers
    // ══════════════════════════════════════════════════════════════════════

    private function makeUser(int $groupId): User
    {
        return User::factory()->create([
            'group_id' => $groupId,
            'phone'    => '0' . rand(100000000, 999999999),
        ]);
    }

    private function makeStock(User $owner): Stock
    {
        return Stock::create([
            'ref'          => 'TEST-' . rand(1000, 9999),
            'type_storage' => 'STOCKAGE SEC',
            'qty'          => 1000.0,
            'storage_id'   => $this->storage->id,
            'customer_id'  => $owner->id,
            'created_by'   => $owner->id,
            'expired_at'   => 365,
        ]);
    }

    private function makeDetail(Stock $stock): Detail
    {
        $capacityId = \App\Models\Capacity::first()?->id
            ?? \App\Models\Capacity::create(['name' => 'Test Container'])->id;

        return Detail::create([
            'qty'          => 1000.0,
            'stock_id'     => $stock->id,
            'product_id'   => $this->product->id,
            'container_id' => $capacityId,
        ]);
    }

    private function makeSession(User $user): SyncSession
    {
        return SyncSession::create([
            'session_id'         => (string) Str::uuid(),
            'user_id'            => $user->id,
            'device_id'          => 'test-device',
            'client_logical_seq' => 0,
            'ops_submitted'      => 0,
            'ops_applied'        => 0,
            'ops_conflicted'     => 0,
            'status'             => 'in_progress',
        ]);
    }

    private function makeOp(User $user, ?Stock $stock, string $type, float $qty, string $status): InventoryOp
    {
        return InventoryOp::create([
            'op_id'          => (string) Str::uuid(),
            'user_id'        => $user->id,
            'device_id'      => 'test-device',
            'logical_seq'    => rand(1, 99999),
            'storage_id'     => $this->storage->id,
            'product_id'     => $this->product->id,
            'stock_id'       => $stock?->id,
            'op_type'        => $type,
            'quantity_delta' => $qty,
            'unit'           => 'kg',
            'sync_status'    => $status,
        ]);
    }

    private function opData(
        User $user,
        ?int $stockId = null,
        string $type  = 'stock_in',
        float $qty    = 100.0
    ): array {
        return [
            'op_id'             => (string) Str::uuid(),
            'user_id'           => $user->id,
            'device_id'         => 'test-device',
            'logical_seq'       => rand(1, 99999),
            'storage_id'        => $this->storage->id,
            'product_id'        => $this->product->id,
            'stock_id'          => $stockId,
            'op_type'           => $type,
            'quantity_delta'    => $qty,
            'unit'              => 'kg',
            'notes'             => null,
            'client_created_at' => now()->toDateTimeString(),
        ];
    }

    private function seedPermissions(): void
    {
        foreach (config('sync_permissions.groups') as $groupId => $group) {
            foreach ($group['actions'] as $action => $allowed) {
                SyncPermission::updateOrCreate(
                    ['group_id' => $groupId, 'action' => $action],
                    ['allowed'  => $allowed]
                );
            }
        }
    }
}
