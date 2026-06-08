<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\InventoryOp;
use App\Models\SyncPermission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;

/**
 * S13-12 — Test every sync-layer button with each user group.
 *
 * Verifies:
 *   • Correct HTTP 403 from SyncPermissionMiddleware for blocked groups
 *   • Correct HTTP 302 / 200 pass-through for permitted groups
 *   • Correct UI hiding (buttons present/absent in rendered views)
 *
 * Permission matrix (config/sync_permissions.php):
 *   sync.accept  → groups 1–4 YES ; groups 5–10 NO
 *   sync.discard → all groups YES
 *   sync.cancel  → all groups, but owner-only for non-Admin
 *   sync.merge   → group 1 only
 *   sync.edit    → all groups, but owner-only for non-Admin
 *   log.view     → all groups YES
 *   log.export   → group 1 only
 */
class SyncButtonPermissionsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  ACCEPT  (POST /inventory-ops/{opId}/accept  →  sync.permission:sync.accept)
    // ══════════════════════════════════════════════════════════════════════

    /** Groups 5–10 must receive a 403 when hitting the accept endpoint. */
    public function test_user_groups_cannot_accept(): void
    {
        foreach ([5, 6, 7, 8, 10] as $gid) {
            $user = $this->makeUser($gid);
            $op   = $this->makeOp($user, 'conflict');

            $this->actingAs($user)
                ->post(route('inventory-ops.accept', $op->op_id))
                ->assertStatus(403)
                ->assertJson(['error' => 'forbidden', 'action' => 'sync.accept']);
        }
    }

    /** Groups 1–4 must pass the accept middleware (302 redirect). */
    public function test_supervisor_groups_can_accept(): void
    {
        foreach ([1, 2, 3, 4] as $gid) {
            $user = $this->makeUser($gid);
            $op   = $this->makeOp($user, 'conflict');

            $this->actingAs($user)
                ->post(route('inventory-ops.accept', $op->op_id))
                ->assertRedirect();
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    //  DISCARD  (POST /inventory-ops/{opId}/discard  →  sync.permission:sync.discard)
    // ══════════════════════════════════════════════════════════════════════

    /** Every group can reach the discard endpoint — sync.discard is true for all. */
    public function test_all_groups_can_discard(): void
    {
        foreach ([1, 2, 3, 4, 5, 6] as $gid) {
            $user = $this->makeUser($gid);
            $op   = $this->makeOp($user, 'conflict');

            $this->actingAs($user)
                ->post(route('inventory-ops.discard', $op->op_id))
                ->assertRedirect();  // not a 403
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    //  CANCEL  (POST /inventory-ops/{opId}/cancel  →  owner-or-Admin)
    // ══════════════════════════════════════════════════════════════════════

    /** Any group can cancel their own pending op (middleware passes, owner check passes). */
    public function test_owner_can_cancel_own_op(): void
    {
        foreach ([1, 2, 5, 6] as $gid) {
            $owner = $this->makeUser($gid);
            $op    = $this->makeOp($owner, 'pending');

            $this->actingAs($owner)
                ->post(route('inventory-ops.cancel', $op->op_id))
                ->assertRedirect();
        }
    }

    /** A non-owner from a user group is blocked — engine fires 403 for ownership check. */
    public function test_non_owner_non_admin_cannot_cancel_others_op(): void
    {
        $owner = $this->makeUser(5);
        $other = $this->makeUser(6);
        $op    = $this->makeOp($owner, 'pending');

        $this->actingAs($other)
            ->post(route('inventory-ops.cancel', $op->op_id))
            ->assertStatus(403);
    }

    /** Admin (group 1) can cancel any op regardless of ownership. */
    public function test_admin_can_cancel_any_op(): void
    {
        $owner = $this->makeUser(5);
        $admin = $this->makeUser(1);
        $op    = $this->makeOp($owner, 'pending');

        $this->actingAs($admin)
            ->post(route('inventory-ops.cancel', $op->op_id))
            ->assertRedirect();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  EDIT  (PUT /inventory-ops/{opId}  →  owner-or-Admin)
    // ══════════════════════════════════════════════════════════════════════

    /** Any group can edit their own pending op. */
    public function test_owner_can_edit_own_op(): void
    {
        foreach ([1, 2, 5] as $gid) {
            $owner = $this->makeUser($gid);
            $op    = $this->makeOp($owner, 'pending');

            $this->actingAs($owner)
                ->put(route('inventory-ops.edit', $op->op_id), [
                    'quantity_delta' => 99,
                    'reason'         => 'test edit',
                ])
                ->assertRedirect();
        }
    }

    /** Non-owner non-admin gets 403 when trying to edit someone else's op. */
    public function test_non_owner_non_admin_cannot_edit_others_op(): void
    {
        $owner = $this->makeUser(5);
        $other = $this->makeUser(6);
        $op    = $this->makeOp($owner, 'pending');

        $this->actingAs($other)
            ->put(route('inventory-ops.edit', $op->op_id), [
                'quantity_delta' => 99,
                'reason'         => 'test',
            ])
            ->assertStatus(403);
    }

    /** Admin can edit any op. */
    public function test_admin_can_edit_any_op(): void
    {
        $owner = $this->makeUser(5);
        $admin = $this->makeUser(1);
        $op    = $this->makeOp($owner, 'pending');

        $this->actingAs($admin)
            ->put(route('inventory-ops.edit', $op->op_id), [
                'quantity_delta' => 50,
                'reason'         => 'admin correction',
            ])
            ->assertRedirect();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  MERGE  (POST /inventory-ops/merge  →  sync.permission:sync.merge)
    // ══════════════════════════════════════════════════════════════════════

    /** Groups 2–10 get 403 — sync.merge is Admin-only. */
    public function test_non_admin_cannot_merge(): void
    {
        foreach ([2, 3, 4, 5, 6] as $gid) {
            $user = $this->makeUser($gid);

            $this->actingAs($user)
                ->post(route('inventory-ops.merge'), [
                    'op_a_id'         => Str::uuid(),
                    'op_b_id'         => Str::uuid(),
                    'merged_quantity'  => 100,
                    'reason'          => 'test',
                ])
                ->assertStatus(403)
                ->assertJson(['action' => 'sync.merge']);
        }
    }

    /** Admin passes the merge middleware and reaches the controller (real ops required). */
    public function test_admin_can_call_merge_endpoint(): void
    {
        $admin = $this->makeUser(1);
        $opA   = $this->makeOp($admin, 'conflict');
        $opB   = $this->makeOp($admin, 'conflict');

        $this->actingAs($admin)
            ->post(route('inventory-ops.merge'), [
                'op_a_id'         => $opA->op_id,
                'op_b_id'         => $opB->op_id,
                'merged_quantity'  => 100,
                'reason'          => 'physical recount',
            ])
            ->assertRedirect();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  OVERRIDE  (POST /inventory-ops/{opId}/override  →  sync.permission:sync.accept)
    // ══════════════════════════════════════════════════════════════════════

    /** Groups 5–10 are blocked — override reuses the sync.accept permission gate. */
    public function test_user_groups_cannot_override(): void
    {
        foreach ([5, 6] as $gid) {
            $user = $this->makeUser($gid);
            $op   = $this->makeOp($user, 'conflict');

            $this->actingAs($user)
                ->post(route('inventory-ops.override', $op->op_id), ['quantity' => 100])
                ->assertStatus(403);
        }
    }

    /** Groups 1–4 can override. */
    public function test_supervisor_groups_can_override(): void
    {
        foreach ([1, 2, 3, 4] as $gid) {
            $user = $this->makeUser($gid);
            $op   = $this->makeOp($user, 'conflict');

            $this->actingAs($user)
                ->post(route('inventory-ops.override', $op->op_id), ['quantity' => 100])
                ->assertRedirect();
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    //  AUDIT LOG VIEW  (GET /sync-audit-log  →  sync.permission:log.view)
    // ══════════════════════════════════════════════════════════════════════

    /** All groups have log.view = true. */
    public function test_all_groups_can_view_audit_log(): void
    {
        foreach ([1, 2, 3, 4, 5, 6] as $gid) {
            $user = $this->makeUser($gid);

            $this->actingAs($user)
                ->get(route('sync-audit-log.index'))
                ->assertStatus(200);
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    //  AUDIT LOG EXPORT  (GET /sync-audit-log/export  →  sync.permission:log.export)
    // ══════════════════════════════════════════════════════════════════════

    /** Groups 2–10 are blocked — log.export is Admin-only. */
    public function test_non_admin_cannot_export_audit_log(): void
    {
        foreach ([2, 3, 4, 5, 6] as $gid) {
            $user = $this->makeUser($gid);

            $this->actingAs($user)
                ->get(route('sync-audit-log.export'))
                ->assertStatus(403);
        }
    }

    /** Admin gets a 200 CSV stream. */
    public function test_admin_can_export_audit_log(): void
    {
        $admin = $this->makeUser(1);

        $this->actingAs($admin)
            ->get(route('sync-audit-log.export'))
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  INVENTORY OPS INDEX  (GET /inventory-ops  →  sync.permission:sync.pull)
    // ══════════════════════════════════════════════════════════════════════

    /** All groups can reach the inventory ops index (sync.pull = true for all). */
    public function test_all_groups_can_view_inventory_ops(): void
    {
        foreach ([1, 2, 3, 4, 5, 6] as $gid) {
            $this->actingAs($this->makeUser($gid))
                ->get(route('inventory-ops.index'))
                ->assertStatus(200);
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    //  UI HIDING — inventory-ops rendered view
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Admin sees the merge checkbox column (op-select class).
     * Non-admin must not see it.
     */
    public function test_merge_checkbox_visible_to_admin_only(): void
    {
        $admin = $this->makeUser(1);
        $this->makeOp($admin, 'pending');

        $this->actingAs($admin)
            ->get(route('inventory-ops.index'))
            ->assertStatus(200)
            ->assertSee('op-select');

        $supervisor = $this->makeUser(2);
        $this->makeOp($supervisor, 'pending');

        $this->actingAs($supervisor)
            ->get(route('inventory-ops.index'))
            ->assertStatus(200)
            ->assertDontSee('op-select');
    }

    /**
     * Group 2 (supervisor) sees the accept form action URL for a conflict op.
     * Group 5 (user) must not see the accept URL for their own conflict op.
     */
    public function test_accept_button_visibility_by_group(): void
    {
        // Supervisor sees the accept button
        $supervisor = $this->makeUser(2);
        $supOp      = $this->makeOp($supervisor, 'conflict');

        $this->actingAs($supervisor)
            ->get(route('inventory-ops.index'))
            ->assertStatus(200)
            ->assertSee(route('inventory-ops.accept', $supOp->op_id), false);

        // User (group 5) does NOT see the accept button even on their own conflict op
        $member    = $this->makeUser(5);
        $memberOp  = $this->makeOp($member, 'conflict');

        $this->actingAs($member)
            ->get(route('inventory-ops.index'))
            ->assertStatus(200)
            ->assertDontSee(route('inventory-ops.accept', $memberOp->op_id), false);
    }

    /**
     * An op owner sees the cancel button for their own pending op.
     * Another user from a different group who cannot see that op does not.
     */
    public function test_cancel_button_visibility(): void
    {
        $owner  = $this->makeUser(5);
        $op     = $this->makeOp($owner, 'pending');

        // Owner sees their own cancel button
        $this->actingAs($owner)
            ->get(route('inventory-ops.index'))
            ->assertStatus(200)
            ->assertSee(route('inventory-ops.cancel', $op->op_id), false);

        // Another group-6 user doesn't even see the op (user filter), so no cancel URL
        $other = $this->makeUser(6);

        $this->actingAs($other)
            ->get(route('inventory-ops.index'))
            ->assertStatus(200)
            ->assertDontSee(route('inventory-ops.cancel', $op->op_id), false);
    }

    /**
     * An op owner sees the edit button for their own pending op.
     */
    public function test_edit_button_visibility(): void
    {
        $owner = $this->makeUser(5);
        $op    = $this->makeOp($owner, 'pending');

        $this->actingAs($owner)
            ->get(route('inventory-ops.index'))
            ->assertStatus(200)
            ->assertSee(route('inventory-ops.edit', $op->op_id), false);

        $other = $this->makeUser(6);

        $this->actingAs($other)
            ->get(route('inventory-ops.index'))
            ->assertStatus(200)
            ->assertDontSee(route('inventory-ops.edit', $op->op_id), false);
    }

    /**
     * Groups 1–4 see the override button for conflict ops.
     * Groups 5+ do not.
     */
    public function test_override_button_visibility(): void
    {
        $supervisor = $this->makeUser(2);
        $op         = $this->makeOp($supervisor, 'conflict');

        // Supervisor sees override button (data-bs-target="#override-modal")
        $this->actingAs($supervisor)
            ->get(route('inventory-ops.index'))
            ->assertStatus(200)
            ->assertSee('override-modal');

        // User (group 5) does not see any reference to the override modal
        $member   = $this->makeUser(5);
        $memberOp = $this->makeOp($member, 'conflict');

        $this->actingAs($member)
            ->get(route('inventory-ops.index'))
            ->assertStatus(200)
            ->assertDontSee('override-modal');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  UI HIDING — sync-audit-log rendered view
    // ══════════════════════════════════════════════════════════════════════

    /** Admin sees the "Exporter CSV" button. Non-admin does not. */
    public function test_export_button_visibility_in_audit_log(): void
    {
        $admin = $this->makeUser(1);

        $this->actingAs($admin)
            ->get(route('sync-audit-log.index'))
            ->assertStatus(200)
            ->assertSee('Exporter CSV');

        $supervisor = $this->makeUser(2);

        $this->actingAs($supervisor)
            ->get(route('sync-audit-log.index'))
            ->assertStatus(200)
            ->assertDontSee('Exporter CSV');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  Helpers
    // ══════════════════════════════════════════════════════════════════════

    private function makeUser(int $groupId): User
    {
        return User::factory()->create([
            'group_id' => $groupId,
            'phone'    => '0' . rand(100000000, 999999999),
        ]);
    }

    private function makeOp(User $user, string $status = 'pending'): InventoryOp
    {
        $storageId = \App\Models\Storage::firstOrCreate(
            ['name' => 'Test Storage S13'],
            ['location' => 'Test Location', 'capacity' => 1000]
        )->id;

        $productId = \App\Models\Product::first()?->id ?? 1;

        return InventoryOp::create([
            'op_id'          => Str::uuid(),
            'user_id'        => $user->id,
            'device_id'      => 'test-device-' . $user->id,
            'logical_seq'    => rand(1, 99999),
            'storage_id'     => $storageId,
            'product_id'     => $productId,
            'op_type'        => 'stock_in',
            'quantity_delta' => 100.0,
            'unit'           => 'kg',
            'sync_status'    => $status,
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
