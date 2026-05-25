<?php

namespace App\Services\Sync;

use App\Models\User;
use App\Models\SyncPermission;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;

class SyncPermissionService
{
    /**
     * Check whether the given user is allowed to perform the sync action.
     *
     * Permissions are cached per group_id for the duration of the request
     * (in-memory array) so the sync_permissions table is hit at most once
     * per group per request, regardless of how many ops are processed.
     */
    public function can(User $user, string $action): bool
    {
        return in_array($action, $this->permissionsFor($user->group_id), true);
    }

    /**
     * Assert permission — throws AuthorizationException if denied.
     *
     * @throws AuthorizationException
     */
    public function canOrFail(User $user, string $action): void
    {
        if (! $this->can($user, $action)) {
            throw new AuthorizationException(
                "Group [{$user->group_id}] is not permitted to perform [{$action}]."
            );
        }
    }

    /**
     * Returns true when both the action is permitted AND the user owns the op
     * (or is Admin). Use this for owner_only actions (sync.cancel, sync.edit).
     */
    public function canOwner(User $user, string $action, int $opOwnerId): bool
    {
        if (! $this->can($user, $action)) {
            return false;
        }

        $ownerOnlyActions = config('sync_permissions.owner_only', []);

        if (in_array($action, $ownerOnlyActions, true)) {
            return $user->group_id === 1 || $user->id === $opOwnerId;
        }

        return true;
    }

    /**
     * Assert owner permission — throws AuthorizationException if denied.
     *
     * @throws AuthorizationException
     */
    public function canOwnerOrFail(User $user, string $action, int $opOwnerId): void
    {
        if (! $this->canOwner($user, $action, $opOwnerId)) {
            $ownerOnlyActions = config('sync_permissions.owner_only', []);

            if (in_array($action, $ownerOnlyActions, true) && ! $this->can($user, $action)) {
                throw new AuthorizationException(
                    "Group [{$user->group_id}] is not permitted to perform [{$action}]."
                );
            }

            throw new AuthorizationException(
                "User [{$user->id}] may only perform [{$action}] on their own ops."
            );
        }
    }

    /**
     * Flush the in-memory permission cache (useful in tests between requests).
     */
    public function flushCache(): void
    {
        $this->resolved = [];
    }

    // ── Internal ──────────────────────────────────────────────────────────

    /** In-memory cache: group_id → string[] of allowed actions */
    private array $resolved = [];

    /**
     * Load and cache the allowed actions for a group.
     * Hits the DB once per group per request; subsequent calls are O(1).
     */
    private function permissionsFor(int $groupId): array
    {
        if (! array_key_exists($groupId, $this->resolved)) {
            $this->resolved[$groupId] = SyncPermission::where('group_id', $groupId)
                ->where('allowed', true)
                ->pluck('action')
                ->all();
        }

        return $this->resolved[$groupId];
    }
}
