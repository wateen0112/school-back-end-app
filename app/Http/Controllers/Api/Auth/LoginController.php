<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Traits\AuthTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    use AuthTrait;

    public function __construct()
    {
        $this->middleware('guest')->except('logout', 'refresh', 'me');
    }

    /**
     * Unified login function for all user types
     * Automatically detects user type by searching across all user tables
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'type' => 'nullable|string|in:web,student,teacher,parent'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = [
            'email' => $request->email,
            'password' => $request->password
        ];

        // If the client asked for a specific guard, only try that guard so the
        // returned token always matches the requested role (e.g. teacher token).
        // Fallback to all guards only when no type was sent.
        $requestedGuard = $request->input('type');
        $defaultGuards = ['web', 'student', 'teacher', 'parent'];
        $guards = $requestedGuard ? [$requestedGuard] : $defaultGuards;
        
        foreach ($guards as $guardName) {
            if (Auth::guard($guardName)->attempt($credentials)) {
                $user = Auth::guard($guardName)->user();
                $token = $user->createToken('API Token');
                
                // Determine user type based on model
                $userType = $this->getUserType($user);

                return response()->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'user' => $this->withPermissions($user),
                    'access_token' => $token->plainTextToken,
                    'bearer_token' => 'Bearer ' . $token->plainTextToken,
                    'token_type' => 'Bearer',
                    'expires_at' => $token->accessToken->expires_at,
                    'type' => $userType,
                    'guard' => $guardName
                ], 200);
            }
        }

        \App\Services\AuditLogger::log('auth.login_failed', 'auth', null, 'فشل تسجيل الدخول', [
            'email' => $request->email,
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Invalid email or password'
        ], 401);
    }

    /**
     * Legacy login method for backward compatibility
     * @deprecated Use login() method instead
     */
    public function loginWithType(Request $request, $type)
    {
        $request->merge(['type' => $type]);
        return $this->login($request);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }
        
        // Revoke only the token used for this request so other devices stay signed in.
        if (method_exists($user, 'currentAccessToken') && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }
        
        // Get the guard name based on user model
        $guardName = $this->getUserGuard($user);
        
        if ($guardName && Auth::guard($guardName)->check()) {
            Auth::guard($guardName)->logout();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ], 200);
    }

    public function refresh(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        // Rotate the token: revoke the one used for this request, then issue a fresh one.
        $current = method_exists($user, 'currentAccessToken') ? $user->currentAccessToken() : null;

        $token = $user->createToken('API Token');

        if ($current) {
            $current->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully',
            'user' => $this->withPermissions($user),
            'access_token' => $token->plainTextToken,
            'bearer_token' => 'Bearer ' . $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at
        ], 200);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'user' => $this->withPermissions($user)
        ], 200);
    }

    /**
     * Get user type based on user model
     */
    private function getUserType($user)
    {
        $userClass = get_class($user);
        
        if (strpos($userClass, 'Student') !== false) {
            return 'student';
        } elseif (strpos($userClass, 'Teacher') !== false) {
            return 'teacher';
        } elseif (strpos($userClass, 'Parent') !== false) {
            return 'parent';
        } elseif ($user instanceof \App\User) {
            // School bus drivers sign in with a regular staff account but get
            // the dedicated driver experience in the mobile app.
            if (\App\Models\Transportation\DriverProfile::where('user_id', $user->id)->where('status', 'ACTIVE')->exists()) {
                return 'driver';
            }

            // Staff whose role is a finance role (e.g. finance_officer,
            // finance_director) get the dedicated accountant experience.
            foreach ($user->roles as $role) {
                $roleName = strtolower((string) $role->getTranslation('name', 'en', false));

                if ($roleName !== '' && str_contains($roleName, 'finance')) {
                    return 'accountant';
                }
            }

            return 'admin';
        } else {
            return 'admin';
        }
    }

    /**
     * Get guard name based on user model
     */
    private function getUserGuard($user)
    {
        $userClass = get_class($user);

        if (strpos($userClass, 'Student') !== false) {
            return 'student';
        } elseif (strpos($userClass, 'Teacher') !== false) {
            return 'teacher';
        } elseif (strpos($userClass, 'Parent') !== false) {
            return 'parent';
        } else {
            return 'web';
        }
    }

    /**
     * Return the user array with effective permissions for any user model
     * that implements the RBAC helpers (User, Teacher, Student, My_Parent).
     */
    private function withPermissions($user)
    {
        $data = $user->toArray();

        if (method_exists($user, 'effectivePermissions')) {
            $data['permissions'] = $user->effectivePermissions();
        }

        return $data;
    }
}
