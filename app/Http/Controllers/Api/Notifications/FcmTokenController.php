<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FcmTokenController extends Controller
{
    /**
     * Register or update FCM token for the authenticated user.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'token' => 'required|string',
            ]);

            $user = auth()->user();
            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            $userClass = get_class($user);
            $userId = $user->id;

            // Upsert FCM token
            DB::table('fcm_tokens')->updateOrInsert(
                [
                    'user_id'    => $userId,
                    'user_type'  => $userClass,
                ],
                [
                    'token'      => $request->token,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'FCM token registered successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error registering FCM token',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete FCM token for the authenticated user (on logout).
     */
    public function destroy(Request $request)
    {
        try {
            $user = auth()->user();
            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            DB::table('fcm_tokens')
                ->where('user_id', $user->id)
                ->where('user_type', get_class($user))
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'FCM token removed successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error removing FCM token',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
