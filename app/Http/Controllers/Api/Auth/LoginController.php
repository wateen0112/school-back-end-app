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
            'password' => 'required|string|min:6'
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

        // Try to authenticate with each guard type
        $guards = ['web', 'student', 'teacher', 'parent'];
        
        foreach ($guards as $guardName) {
            if (Auth::guard($guardName)->attempt($credentials)) {
                $user = Auth::guard($guardName)->user();
                $token = $user->createToken('API Token');
                
                // Determine user type based on model
                $userType = $this->getUserType($user);

                return response()->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'user' => $user,
                    'access_token' => $token->plainTextToken,
                    'bearer_token' => 'Bearer ' . $token->plainTextToken,
                    'token_type' => 'Bearer',
                    'expires_at' => $token->accessToken->expires_at,
                    'type' => $userType,
                    'guard' => $guardName
                ], 200);
            }
        }

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
        
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }
        
        // Get the guard name based on user model
        $guardName = $this->getUserGuard($user);
        
        if ($guardName && Auth::guard($guardName)->check()) {
            Auth::guard($guardName)->logout();
        }
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();

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

        $token = $user->createToken('API Token')->accessToken;

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully',
            'user' => $user,
            'token' => $token
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
            'user' => $user
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
}
