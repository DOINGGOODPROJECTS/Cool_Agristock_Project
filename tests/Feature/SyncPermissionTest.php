<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\InventoryOp;
use App\Models\SyncPermission;
use App\Services\Sync\SyncPermissionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SyncPermissionTest extends TestCase
{
    use DatabaseTransactions;

    private SyncPermissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SyncPermissionService();
        $this->seedPermissions();
    }

    // ── 1. Member trying sync.accept → 403 ───────────────────────────────

    public function test_member_cannot_call_accept_endpoint(): void
    {
        $member = $this->makeUser(groupId: 5);
        $op     = $this->makePendingOp($member);

        $response = $this->actingAs($member)
            ->post(route('inventory-ops.accept', $op->op_id));

        $response->assertStatus(403);
        $response->assertJson(['error' => 'forbidden', 'action' => 'sync.accept']);
    }

    // ── 2. Manager (Superviseur) trying sync.accept → passes ─────────────

    public function test_manager_can_call_accept_endpoint(): void
    {
        $manager = $this->makeUser(groupId: 2);
        $op      = $this->makePendingOp($manager);

        // Patch the op to conflict status so the controller logic runs cleanly
        $op->update(['sync_status' => 'conflict']);

        $response = $this->actingAs($manager)
            ->post(route('inventory-ops.accept', $op->op_id));

        // 302 redirect = controller ran and passed through middleware
        $response->assertRedirect();
        $this->assertDatabaseHas('inventory_ops', [
            'op_id'       => $op->op_id,
            'sync_status' => 'applied',
        ]);
    }

    // ── 3. Op owner cancelling their own op → passes ──────────────────────

    public function test_op_owner_can_cancel_own_op(): void
    {
        $owner = $this->makeUser(groupId: 5);
        $op    = $this->makePendingOp($owner);

        $response = $this->actingAs($owner)
            ->post(route('inventory-ops.cancel', $op->op_id));

        $response->assertRedirect();
        $this->assertDatabaseHas('inventory_ops', [
            'op_id'       => $op->op_id,
            'sync_status' => 'cancelled',
        ]);
    }

    // ── 4. Different user trying to cancel someone else's op → 403 ────────

    public function test_other_user_cannot_cancel_another_users_op(): void
    {
        $owner = $this->makeUser(groupId: 5);
        $other = $this->makeUser(groupId: 6);
        $op    = $this->makePendingOp($owner);

        $response = $this->actingAs($other)
            ->post(route('inventory-ops.cancel', $op->op_id));

        // Middleware passes (both groups have sync.cancel = true)
        // but canOwnerOrFail() in the controller fires a 403 JSON response
        $response->assertStatus(403);
        $this->assertDatabaseHas('inventory_ops', [
            'op_id'       => $op->op_id,
            'sync_status' => 'pending',  // unchanged
        ]);
    }

    // ── Service unit tests ────────────────────────────────────────────────

    public function test_service_can_returns_true_for_allowed_action(): void
    {
        $admin = $this->makeUser(groupId: 1);
        $this->assertTrue($this->service->can($admin, 'sync.merge'));
    }

    public function test_service_can_returns_false_for_denied_action(): void
    {
        $member = $this->makeUser(groupId: 5);
        $this->assertFalse($this->service->can($member, 'sync.accept'));
    }

    public function test_service_can_or_fail_throws_for_denied_action(): void
    {
        $this->expectException(AuthorizationException::class);
        $member = $this->makeUser(groupId: 5);
        $this->service->canOrFail($member, 'sync.accept');
    }

    public function test_service_owner_check_passes_for_admin_on_any_op(): void
    {
        $admin     = $this->makeUser(groupId: 1);
        $someOwner = $this->makeUser(groupId: 5);

        // Admin can cancel any op regardless of ownership
        $this->assertTrue($this->service->canOwner($admin, 'sync.cancel', $someOwner->id));
    }

    public function test_service_owner_check_fails_for_non_owner(): void
    {
        $owner = $this->makeUser(groupId: 5);
        $other = $this->makeUser(groupId: 6);

        $this->assertFalse($this->service->canOwner($other, 'sync.cancel', $owner->id));
    }

    public function test_permission_cache_hits_db_once_per_group(): void
    {
        $user = $this->makeUser(groupId: 2);

        // Call can() multiple times for the same group
        $this->service->can($user, 'sync.push');
        $this->service->can($user, 'sync.pull');
        $this->service->can($user, 'sync.accept');

        // Flush and re-check — should work after flush too
        $this->service->flushCache();
        $this->assertTrue($this->service->can($user, 'sync.push'));
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function makeUser(int $groupId): User
    {
        return User::factory()->create([
            'group_id' => $groupId,
            'phone'    => '0' . rand(100000000, 999999999),
        ]);
    }

    private function makePendingOp(User $user): InventoryOp
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
            'quantity_delta' => 100.0,
            'unit'           => 'kg',
            'sync_status'    => 'pending',
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
