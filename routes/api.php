<?php

use App\Http\Controllers\Api\SyncController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ── Token issuance — needed for Postman / mobile authentication ───────────
// Issues a Sanctum personal-access token. Any existing token for the same
// device_name is revoked first to prevent token accumulation.
Route::post('/auth/token', function (Request $request) {
    $request->validate([
        'email'       => 'required|email',
        'password'    => 'required|string',
        'device_name' => 'required|string|max:191',
    ]);

    $user = User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials.'], 401);
    }

    // Revoke any existing token for this device to avoid duplicates
    $user->tokens()->where('name', $request->device_name)->delete();

    $token = $user->createToken($request->device_name)->plainTextToken;

    return response()->json([
        'token'      => $token,
        'user_id'    => $user->id,
        'group_id'   => $user->group_id,
        'group_name' => config('sync_permissions.groups.' . $user->group_id . '.name', 'Unknown'),
    ]);
});

// ── Authenticated user info ───────────────────────────────────────────────
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ── Sync API ──────────────────────────────────────────────────────────────
// All routes require a valid Sanctum token (auth:sanctum).
// Per-route sync.permission middleware is applied where the action
// requires a specific RBAC permission beyond mere authentication.
// cancel and edit have no route-level permission gate — the
// ReconciliationEngine enforces owner-or-admin internally.
Route::middleware('auth:sanctum')->prefix('sync')->name('api.sync.')->group(function () {

    // Push a batch of offline operations to the server
    Route::post('/push',
        [SyncController::class, 'push'])
        ->middleware('sync.permission:sync.push')
        ->name('push');

    // Pull remote applied ops and pending conflicts
    Route::get('/pull',
        [SyncController::class, 'pull'])
        ->middleware('sync.permission:sync.pull')
        ->name('pull');

    // Resolve a conflicted op (accept / discard / override / merge)
    // No route-level permission gate — the ReconciliationEngine enforces
    // sync.accept (accept/override), sync.discard (discard), and sync.merge
    // (merge) per resolution type, so all groups can reach this endpoint
    // and the engine rejects actions their group cannot perform.
    Route::post('/resolve-conflict',
        [SyncController::class, 'resolveConflict'])
        ->name('resolve-conflict');

    // Cancel a pending op (engine enforces owner-or-admin)
    Route::post('/cancel',
        [SyncController::class, 'cancel'])
        ->name('cancel');

    // Edit a pending op, creating a replacement (engine enforces owner-or-admin)
    Route::post('/edit',
        [SyncController::class, 'edit'])
        ->name('edit');

    // Paginated audit log — log/export must come before log to avoid path shadowing
    Route::get('/log/export',
        [SyncController::class, 'exportLog'])
        ->middleware('sync.permission:log.export')
        ->name('log.export');

    Route::get('/log',
        [SyncController::class, 'log'])
        ->middleware('sync.permission:log.view')
        ->name('log');
});
