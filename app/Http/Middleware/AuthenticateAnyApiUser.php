<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Middleware that authenticates any API user regardless of their role/guard.
 * Checks all user models (User, Teacher, Student, My_Parent) via Sanctum tokens.
 */
class AuthenticateAnyApiUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  ...$guards
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        // Try to authenticate via Sanctum token from any user model
        $user = $this->authenticateFromToken($request);

        if ($user) {
            // Set the user directly on the request so $request->user() works
            $request->setUserResolver(function ($guard = null) use ($user) {
                return $user;
            });

            // Also set the user on the auth facade so auth()->user() works
            // We need to determine the correct guard based on the user type
            $class = get_class($user);
            if (strpos($class, 'Teacher') !== false) {
                Auth::guard('teacher')->setUser($user);
                Auth::shouldUse('teacher');
            } elseif (strpos($class, 'Student') !== false) {
                Auth::guard('student')->setUser($user);
                Auth::shouldUse('student');
            } elseif (strpos($class, 'My_Parent') !== false || strpos($class, 'Parent') !== false) {
                Auth::guard('parent')->setUser($user);
                Auth::shouldUse('parent');
            } else {
                Auth::guard('web')->setUser($user);
                Auth::shouldUse('web');
            }

            return $next($request);
        }

        // A presented-but-invalid bearer token must never fall through to the
        // guard/session fallbacks below: those may hold a stale in-memory user
        // from an earlier request in long-running processes (tests, Octane,
        // RoadRunner). If the client sent a token, only that token may authenticate.
        if ($request->bearerToken()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // If no token auth succeeded, try the standard guards
        foreach ($guards ?: ['api'] as $guard) {
            if (Auth::guard($guard)->check()) {
                Auth::shouldUse($guard);
                return $next($request);
            }
        }

        // Try session-based guards for backward compatibility
        $sessionGuards = ['web', 'teacher', 'student', 'parent'];
        foreach ($sessionGuards as $guard) {
            if (Auth::guard($guard)->check()) {
                Auth::shouldUse($guard);
                return $next($request);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated.'
        ], 401);
    }

    /**
     * Authenticate user from Sanctum token by checking all user models.
     */
    private function authenticateFromToken(Request $request): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        $token = $request->bearerToken();

        if (!$token) {
            return null;
        }

        // Sanctum stores tokens in personal_access_tokens table with tokenable_type
        // We can query the token directly and resolve the user model
        $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return null;
        }

        $tokenable = $accessToken->tokenable;

        if ($tokenable && method_exists($tokenable, 'currentAccessToken')) {
            // Set the current access token on the user for Sanctum compatibility
            $tokenable->withAccessToken($accessToken);
        }

        return $tokenable;
    }
}
