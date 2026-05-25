<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use App\Services\Sync\SyncPermissionService;
use Symfony\Component\HttpFoundation\Response;

class SyncPermissionMiddleware
{
    public function __construct(private SyncPermissionService $service) {}

    /**
     * Usage on a route:  middleware('sync.permission:sync.accept')
     *
     * Returns a JSON 403 so API and web clients both get a machine-readable
     * response rather than a redirect to /login or an HTML error page.
     */
    public function handle(Request $request, Closure $next, string $action): Response
    {
        try {
            $this->service->canOrFail($request->user(), $action);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error'   => 'forbidden',
                'message' => $e->getMessage(),
                'action'  => $action,
            ], 403);
        }

        return $next($request);
    }
}
