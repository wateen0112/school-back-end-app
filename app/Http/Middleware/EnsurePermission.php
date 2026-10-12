<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards a route behind a permission key.
 *
 * Usage:
 *   ->middleware('permission:students.manage')   // explicit key
 *   ->middleware('permission')                    // auto-resolve key from the URI
 *
 * Access rules:
 *  - super admins always pass,
 *  - users with no roles yet keep full access (RBAC opt-in),
 *  - users with at least one role must hold the key (union of their roles).
 */
class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission = null): Response
    {
        $user = $request->user() ?? Auth::guard('api')->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // The permission layer governs school staff: dashboard admins (App\User)
        // and teachers (App\Models\Teacher). Students / parents are not gated.
        $isGovUser = $user instanceof \App\User;
        $isTeacher = $user instanceof \App\Models\Teacher;

        if (! $isGovUser && ! $isTeacher) {
            return $next($request);
        }

        if ($isGovUser && $user->is_super_admin) {
            return $next($request);
        }

        $permission = $permission
            ?? \App\Services\Roles\PermissionRegistry::permissionForUri($this->withoutApiPrefix($request->path()));

        // No tab permission maps to this endpoint → leave it open.
        if (! $permission) {
            return $next($request);
        }

        if ($user->hasPermission($permission)) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized: missing permission '.$permission,
        ], 403);
    }

    private function withoutApiPrefix(string $path): string
    {
        if (str_starts_with($path, 'api/')) {
            return substr($path, 4);
        }
        return $path === 'api' ? '' : $path;
    }
}