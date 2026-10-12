<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNotificationRequest;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\My_Parent;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    /**
     * Helper: resolve model class from role/type string.
     */
    private function resolveModelClass($type)
    {
        $map = [
            'student' => 'App\Models\Student',
            'teacher' => 'App\Models\Teacher',
            'parent'  => 'App\Models\My_Parent',
            'admin'   => 'App\User',
        ];

        return $map[$type] ?? null;
    }

    /**
     * Helper: get current authenticated user type and model class.
     */
    private function getCurrentUserInfo()
    {
        $user = auth()->user();
        if (! $user) {
            return [null, null];
        }

        $class = get_class($user);

        if (strpos($class, 'Student') !== false) {
            return ['student', 'App\Models\Student'];
        }
        if (strpos($class, 'Teacher') !== false) {
            return ['teacher', 'App\Models\Teacher'];
        }
        if (strpos($class, 'My_Parent') !== false || strpos($class, 'Parent') !== false) {
            return ['parent', 'App\Models\My_Parent'];
        }

        return ['admin', 'App\User'];
    }

    /**
     * Helper: format notification for response with sender info.
     */
    private function formatNotification($notification, $includeRecipients = false)
    {
        $createdAt = $notification->created_at
            ?? $notification->updated_at
            ?? now();
        $updatedAt = $notification->updated_at
            ?? $notification->created_at
            ?? now();

        $data = [
            'id'                      => $notification->id,
            'title'                   => Notification::localizeText($notification->title),
            'body'                    => Notification::localizeText($notification->body),
            'type'                    => $notification->type,
            'target_role'             => $notification->target_role,
            'data'                    => $notification->data,
            'created_at'              => $createdAt,
            'updated_at'              => $updatedAt,
            'recipients_count'        => $notification->recipients_count ?? 0,
            'read_recipients_count'   => $notification->read_recipients_count ?? 0,
            'sender'                  => $this->resolveSender($notification),
        ];

        if ($includeRecipients && isset($notification->recipients)) {
            $data['recipients'] = $notification->recipients->map(function ($recipient) {
                return [
                    'id'              => $recipient->id,
                    'recipient_id'    => $recipient->recipient_id,
                    'recipient_type'  => $recipient->recipient_type,
                    'is_read'         => $recipient->is_read,
                    'read_at'         => $recipient->read_at,
                ];
            });
        }

        return $data;
    }

    /**
     * Helper: resolve sender name and info.
     */
    private function resolveSender($notification)
    {
        if (! $notification->sender_id || ! $notification->sender_type) {
            return null;
        }

        $sender = $notification->sender;
        if (! $sender) {
            return [
                'id'   => $notification->sender_id,
                'type' => $notification->sender_type,
                'name' => null,
            ];
        }

        $name = null;
        if (isset($sender->name)) {
            if (is_array($sender->name)) {
                $name = $sender->name;
            } else {
                $name = ['en' => $sender->name, 'ar' => $sender->name];
            }
        } elseif (isset($sender->Name_Father)) {
            $name = ['en' => $sender->Name_Father, 'ar' => $sender->Name_Father];
        } elseif (isset($sender->email)) {
            $name = ['en' => $sender->email, 'ar' => $sender->email];
        }

        return [
            'id'    => $sender->id,
            'type'  => $notification->sender_type,
            'name'  => $name,
            'email' => $sender->email ?? null,
        ];
    }

    /**
     * List all sent notifications (with recipient stats).
     */
    public function index()
    {
        try {
            $notifications = Notification::withCount(['recipients', 'readRecipients'])
                ->with('sender')
                ->orderBy('created_at', 'desc')
                ->get();

            $formatted = $notifications->map(function ($notification) {
                return $this->formatNotification($notification);
            });

            return response()->json([
                'success' => true,
                'message' => 'Notifications retrieved successfully',
                'data'    => $formatted,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving notifications',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send a new notification.
     */
    public function store(StoreNotificationRequest $request)
    {
        try {
            DB::beginTransaction();

            [$senderRole, $senderType] = $this->getCurrentUserInfo();

            $data = [];
            if ($request->action) {
                $data['action'] = $request->action;
            }
            if ($request->action_id) {
                $data['action_id'] = (int) $request->action_id;
            }
            if ($request->type === 'targeted' && ! empty($request->targets)) {
                $data['targets'] = $request->targets;
            }

            $notification = Notification::create([
                'title'       => $request->title,
                'body'        => $request->body,
                'type'        => $request->type,
                'sender_id'   => auth()->id(),
                'sender_type' => $senderType,
                'target_role' => $request->type === 'role' ? $request->target_role : null,
                'data'        => $data ?: null,
            ]);

            $recipientRows = [];

            switch ($request->type) {
                case 'general':
                    $recipientRows = $this->buildAllRecipients($notification->id);
                    break;

                case 'role':
                    $recipientRows = $this->buildRoleRecipients($request->target_role, $notification->id);
                    break;

                case 'targeted':
                    $recipientRows = $this->buildTargetedRecipients($request->targets, $notification->id);
                    break;
            }

            if (! empty($recipientRows)) {
                // Insert in chunks to avoid too-large queries
                foreach (array_chunk($recipientRows, 500) as $chunk) {
                    NotificationRecipient::insert($chunk);
                }
            }

            DB::commit();

            // Send FCM push notification
            $this->sendFcmNotification($request, $notification);

            // Load counts for response
            $notification->loadCount(['recipients', 'readRecipients']);
            $notification->load('sender');

            return response()->json([
                'success' => true,
                'message' => 'Notification sent successfully',
                'data'    => $this->formatNotification($notification),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error sending notification',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send FCM push notification to recipients.
     */
    private function sendFcmNotification($request, $notification)
    {
        try {
            $fcmService = new \App\Services\FcmNotificationService();
            $data = [
                'notification_id' => $notification->id,
                'type'            => $notification->type,
            ];

            $notificationData = $notification->data ?? [];
            if (! empty($notificationData['action'])) {
                $data['action'] = $notificationData['action'];
            }
            if (! empty($notificationData['action_id'])) {
                $data['action_id'] = $notificationData['action_id'];
            }

            // Backward-compatible entity keys for existing clients.
            $action = $notificationData['action'] ?? null;
            $actionId = $notificationData['action_id'] ?? null;
            if ($action === 'exam' && $actionId) {
                $data['quizze_id'] = $actionId;
            }
            if (in_array($action, ['installment_reminder', 'installment_overdue'], true) && $actionId) {
                $data['student_installment_id'] = $actionId;
            }

            switch ($request->type) {
                case 'general':
                    $fcmService->sendToAll($notification->title, $notification->body, $data);
                    break;

                case 'role':
                    $fcmService->sendToRole($request->target_role, $notification->title, $notification->body, $data);
                    break;

                case 'targeted':
                    $targets = collect($request->targets)->map(function ($target) {
                        $class = $this->resolveModelClass($target['type']);
                        return ['id' => $target['id'], 'type' => $class];
                    })->toArray();
                    $fcmService->sendToUsers($targets, $notification->title, $notification->body, $data);
                    break;
            }
        } catch (\Exception $e) {
            // Silently fail - FCM is best-effort
            \Log::warning('FCM send failed: ' . $e->getMessage());
        }
    }

    /**
     * Build recipient rows for all users.
     */
    private function buildAllRecipients($notificationId)
    {
        $rows = [];
        $now  = now();

        foreach (User::pluck('id') as $id) {
            $rows[] = [
                'notification_id' => $notificationId,
                'recipient_id'    => $id,
                'recipient_type'  => 'App\User',
                'is_read'         => false,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        foreach (Student::pluck('id') as $id) {
            $rows[] = [
                'notification_id' => $notificationId,
                'recipient_id'    => $id,
                'recipient_type'  => 'App\Models\Student',
                'is_read'         => false,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        foreach (Teacher::pluck('id') as $id) {
            $rows[] = [
                'notification_id' => $notificationId,
                'recipient_id'    => $id,
                'recipient_type'  => 'App\Models\Teacher',
                'is_read'         => false,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        foreach (My_Parent::pluck('id') as $id) {
            $rows[] = [
                'notification_id' => $notificationId,
                'recipient_id'    => $id,
                'recipient_type'  => 'App\Models\My_Parent',
                'is_read'         => false,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        return $rows;
    }

    /**
     * Build recipient rows for a specific role.
     */
    private function buildRoleRecipients($role, $notificationId)
    {
        if ($role === 'all') {
            return $this->buildAllRecipients($notificationId);
        }

        $class = $this->resolveModelClass($role);
        if (! $class) {
            return [];
        }

        $rows = [];
        $now  = now();

        foreach ($class::pluck('id') as $id) {
            $rows[] = [
                'notification_id' => $notificationId,
                'recipient_id'    => $id,
                'recipient_type'  => $class,
                'is_read'         => false,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        return $rows;
    }

    /**
     * Build recipient rows for targeted users.
     */
    private function buildTargetedRecipients(array $targets, $notificationId)
    {
        $rows = [];
        $now  = now();

        foreach ($targets as $target) {
            $class = $this->resolveModelClass($target['type']);
            if (! $class) {
                continue;
            }

            // Verify user exists
            $exists = $class::where('id', $target['id'])->exists();
            if (! $exists) {
                continue;
            }

            $rows[] = [
                'notification_id' => $notificationId,
                'recipient_id'    => $target['id'],
                'recipient_type'  => $class,
                'is_read'         => false,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        return $rows;
    }

    /**
     * Show a single notification with recipients.
     */
    public function show($id)
    {
        try {
            $notification = Notification::withCount(['recipients', 'readRecipients'])
                ->with(['sender', 'recipients' => function ($query) {
                    $query->with('recipient')->orderBy('created_at', 'desc');
                }])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Notification retrieved successfully',
                'data'    => $this->formatNotification($notification, true),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
                'error'   => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Delete a notification (and cascade recipients).
     */
    public function destroy(Request $request, $id = null)
    {
        try {
            $id = $request->input('id', $id);
            $notification = Notification::findOrFail($id);
            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting notification',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current user's unread notifications count.
     */
    public function unreadCount()
    {
        try {
            [, $recipientType] = $this->getCurrentUserInfo();

            $count = NotificationRecipient::where('recipient_id', auth()->id())
                ->where('recipient_type', $recipientType)
                ->where('is_read', false)
                ->count();

            return response()->json([
                'success' => true,
                'message' => 'Unread count retrieved successfully',
                'data'    => ['count' => $count],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving unread count',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current user's received notifications (inbox).
     */
    public function myNotifications(Request $request)
    {
        try {
            [, $recipientType] = $this->getCurrentUserInfo();

            $query = NotificationRecipient::with(['notification.sender'])
                ->where('recipient_id', auth()->id())
                ->where('recipient_type', $recipientType)
                ->orderBy('created_at', 'desc');

            if ($request->boolean('unread_only')) {
                $query->where('is_read', false);
            }

            $recipients = $query->get();

            // Format as notification objects with is_read status
            $formatted = $recipients->map(function ($recipient) {
                $notification = $recipient->notification;
                if (! $notification) {
                    return null;
                }

                $data = $this->formatNotification($notification);
                $data['is_read'] = $recipient->is_read;
                $data['read_at'] = $recipient->read_at;
                $data['recipient_id'] = $recipient->id;

                return $data;
            })->filter();

            return response()->json([
                'success' => true,
                'message' => 'My notifications retrieved successfully',
                'data'    => $formatted,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving my notifications',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, $id = null)
    {
        try {
            $id = $request->input('id', $id);
            [, $recipientType] = $this->getCurrentUserInfo();

            $recipient = NotificationRecipient::where('notification_id', $id)
                ->where('recipient_id', auth()->id())
                ->where('recipient_type', $recipientType)
                ->first();

            if (! $recipient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found in your inbox',
                ], 404);
            }

            $recipient->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

            $notification = $recipient->notification;
            $data = $notification ? $this->formatNotification($notification) : null;
            if ($data) {
                $data['is_read'] = true;
                $data['read_at'] = $recipient->read_at;
                $data['recipient_id'] = $recipient->id;
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read',
                'data'    => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error marking notification as read',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        try {
            [, $recipientType] = $this->getCurrentUserInfo();

            $count = NotificationRecipient::where('recipient_id', auth()->id())
                ->where('recipient_type', $recipientType)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read',
                'count'   => $count,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error marking all notifications as read',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark a notification as unread.
     */
    public function markAsUnread(Request $request, $id = null)
    {
        try {
            $id = $request->input('id', $id);
            [, $recipientType] = $this->getCurrentUserInfo();

            $recipient = NotificationRecipient::where('notification_id', $id)
                ->where('recipient_id', auth()->id())
                ->where('recipient_type', $recipientType)
                ->first();

            if (! $recipient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found in your inbox',
                ], 404);
            }

            $recipient->update([
                'is_read' => false,
                'read_at' => null,
            ]);

            $notification = $recipient->notification;
            $data = $notification ? $this->formatNotification($notification) : null;
            if ($data) {
                $data['is_read'] = false;
                $data['read_at'] = null;
                $data['recipient_id'] = $recipient->id;
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as unread',
                'data'    => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error marking notification as unread',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a notification from user's inbox (removes recipient pivot).
     */
    public function deleteMyNotification(Request $request, $id = null)
    {
        try {
            $id = $request->input('id', $id);
            [, $recipientType] = $this->getCurrentUserInfo();

            $recipient = NotificationRecipient::where('notification_id', $id)
                ->where('recipient_id', auth()->id())
                ->where('recipient_type', $recipientType)
                ->first();

            if (! $recipient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found in your inbox',
                ], 404);
            }

            $recipient->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification removed from your inbox',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error removing notification',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
