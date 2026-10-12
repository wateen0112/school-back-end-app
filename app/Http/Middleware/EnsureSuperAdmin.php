<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Restrict a route to the super admin account only.
     *
     * Must run after auth:api (or auth.any) so a user is already resolved.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('api')->user() ?? $request->user();

        if (! $user || ! $user->is_super_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: super admin only',
            ], 403);
        }

        return $next($request);
    }
}