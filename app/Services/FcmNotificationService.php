<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class FcmNotificationService
{
    /**
     * Send FCM notification to specific users.
     *
     * @param array $userIds      Array of ['id' => userId, 'type' => modelClass]
     * @param string $title       Notification title
     * @param string $body        Notification body
     * @param array  $data        Additional data payload
     * @return array              Results per token
     */
    public function sendToUsers(array $userIds, string $title, string $body, array $data = [])
    {
        $tokens = DB::table('fcm_tokens')
            ->whereIn('user_type', array_unique(array_column($userIds, 'type')))
            ->whereIn('user_id', array_column($userIds, 'id'))
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return ['success' => false, 'message' => 'No FCM tokens found'];
        }

        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * Send FCM notification to tokens directly.
     *
     * @param array  $tokens  FCM device tokens
     * @param string $title   Notification title
     * @param string $body    Notification body
     * @param array  $data    Additional data payload
     * @return array
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = [])
    {
        $serverKey = env('FCM_SERVER_KEY');

        if (empty($serverKey)) {
            return ['success' => false, 'message' => 'FCM_SERVER_KEY not configured'];
        }

        $results = [];

        foreach (array_chunk($tokens, 500) as $chunk) {
            $payload = [
                'registration_ids' => $chunk,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                    'sound' => 'default',
                    'android_channel_id' => 'school_notifications_channel',
                ],
                'data' => $data,
                'priority' => 'high',
                'android' => [
                    'notification' => [
                        'channel_id' => 'school_notifications_channel',
                        'sound' => 'default',
                    ],
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type'  => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', $payload);

            $results[] = $response->json();
        }

        return [
            'success' => true,
            'results' => $results,
        ];
    }

    /**
     * Send FCM notification to a role group.
     *
     * @param string $role   Role name (student, teacher, parent, admin)
     * @param string $title  Notification title
     * @param string $body   Notification body
     * @param array  $data   Additional data payload
     * @return array
     */
    public function sendToRole(string $role, string $title, string $body, array $data = [])
    {
        $modelMap = [
            'student' => 'App\Models\Student',
            'teacher' => 'App\Models\Teacher',
            'parent'  => 'App\Models\My_Parent',
            'admin'   => 'App\User',
        ];

        $userType = $modelMap[$role] ?? null;

        if (! $userType) {
            return ['success' => false, 'message' => 'Invalid role'];
        }

        $tokens = DB::table('fcm_tokens')
            ->where('user_type', $userType)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return ['success' => false, 'message' => 'No FCM tokens for this role'];
        }

        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * Send FCM notification to all users.
     *
     * @param string $title  Notification title
     * @param string $body   Notification body
     * @param array  $data   Additional data payload
     * @return array
     */
    public function sendToAll(string $title, string $body, array $data = [])
    {
        $tokens = DB::table('fcm_tokens')
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return ['success' => false, 'message' => 'No FCM tokens found'];
        }

        return $this->sendToTokens($tokens, $title, $body, $data);
    }
}
