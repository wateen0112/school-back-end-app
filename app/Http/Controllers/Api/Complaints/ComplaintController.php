<?php

namespace App\Http\Controllers\Api\Complaints;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\ComplaintReply;
use App\Models\ComplaintAttachment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\My_Parent;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ComplaintController extends Controller
{
    private function getCurrentUserInfo()
    {
        $user = auth()->user();
        if (!$user) return [null, null, null];

        $class = get_class($user);
        if (strpos($class, 'Student') !== false) {
            return [$user, 'student', 'App\Models\Student'];
        }
        if (strpos($class, 'Teacher') !== false) {
            return [$user, 'teacher', 'App\Models\Teacher'];
        }
        if (strpos($class, 'My_Parent') !== false || strpos($class, 'Parent') !== false) {
            return [$user, 'parent', 'App\Models\My_Parent'];
        }
        return [$user, 'admin', 'App\User'];
    }

    private function getComplainantType($role)
    {
        $map = [
            'student' => 'App\Models\Student',
            'teacher' => 'App\Models\Teacher',
            'parent'  => 'App\Models\My_Parent',
            'admin'   => 'App\User',
        ];
        return $map[$role] ?? 'App\User';
    }

    private function formatAttachment($att)
    {
        return [
            'id'               => $att->id,
            'filename'         => $att->filename,
            'original_filename' => $att->original_filename,
            'file_type'        => $att->file_type,
            'file_size'        => $att->file_size,
            'mime_type'        => $att->mime_type,
            'url'              => asset('attachments/complaints/' . $att->filename),
            'created_at'       => $att->created_at,
        ];
    }

    /**
     * List complaints (admin sees all, others see their own).
     */
    public function index(Request $request)
    {
        try {
            [$user, $role, $type] = $this->getCurrentUserInfo();
            if (!$user) return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);

            $query = Complaint::with(['replies', 'attachments', 'latestReply'])
                ->withCount('replies');

            // Filter by role - non-admins see only their own
            if ($role !== 'admin') {
                $query->where('complainant_id', $user->id)
                      ->where('complainant_type', $type);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('priority')) {
                $query->where('priority', $request->priority);
            }

            $complaints = $query->orderBy('created_at', 'desc')->get();

            // Add complainant name to each complaint
            $complaints = $complaints->map(function ($c) {
                $complainant = $c->complainant;
                $c->complainant_name = $complainant
                    ? ($complainant->name ?? $complainant->Name_Father ?? 'Unknown')
                    : 'Unknown';
                return $c;
            });

            return response()->json([
                'success' => true,
                'message' => 'Complaints retrieved successfully',
                'data'    => $complaints,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving complaints',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single complaint with all replies and attachments.
     */
    public function show($id)
    {
        try {
            [$user, $role] = $this->getCurrentUserInfo();

            $complaint = Complaint::with([
                'replies' => function ($q) { $q->orderBy('created_at', 'asc'); },
                'replies.user',
                'replies.attachments',
                'attachments',
            ])->withCount('replies')->findOrFail($id);

            // Non-admins can only see their own complaints
            if ($role !== 'admin' &&
                ($complaint->complainant_id !== $user->id ||
                 $complaint->complainant_type !== $this->getComplainantType($role))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to view this complaint',
                ], 403);
            }

            // Format complainant
            $complainant = $complaint->complainant;
            $complaint->complainant_name = $complainant
                ? ($complainant->name ?? $complainant->Name_Father ?? 'Unknown')
                : 'Unknown';

            // Format replies with user names
            $complaint->replies->each(function ($reply) {
                $u = $reply->user;
                $reply->user_name = $u
                    ? ($u->name ?? $u->Name_Father ?? 'Unknown')
                    : 'Unknown';
                $reply->user_role = $this->resolveRole($reply->user_type);
                $reply->attachments->each(function ($att) {
                    $att->url = asset('attachments/complaints/' . $att->filename);
                });
            });

            // Format attachments
            $complaint->attachments->each(function ($att) {
                $att->url = asset('attachments/complaints/' . $att->filename);
            });

            return response()->json([
                'success' => true,
                'message' => 'Complaint retrieved successfully',
                'data'    => $complaint,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Complaint not found',
                'error'   => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Create a new complaint with optional file attachments.
     */
    public function store(Request $request)
    {
        try {
            [$user, $role, $type] = $this->getCurrentUserInfo();
            if (!$user) return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);

            $validator = Validator::make($request->all(), [
                'title'       => 'required|string|max:255',
                'description' => 'nullable|string',
                'priority'    => 'nullable|in:normal,high,urgent',
                'files'       => 'nullable|array',
                'files.*'     => 'file|mimes:pdf,jpeg,jpg,png,gif,webp,mp3,wav,ogg,mp4,mov,avi|max:51200',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            $complaint = Complaint::create([
                'title'            => $request->title,
                'description'      => $request->description,
                'status'           => 'pending',
                'priority'         => $request->priority ?? 'normal',
                'complainant_id'   => $user->id,
                'complainant_type' => $type,
            ]);

            // Handle file uploads
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                    $file->storeAs('attachments/complaints', $filename, 'upload_attachments');

                    ComplaintAttachment::create([
                        'filename'          => $filename,
                        'original_filename' => $file->getClientOriginalName(),
                        'file_type'         => $file->getClientOriginalExtension(),
                        'file_size'         => $file->getSize(),
                        'mime_type'         => $file->getMimeType(),
                        'complaint_id'      => $complaint->id,
                        'complaint_reply_id' => null,
                    ]);
                }
            }

            DB::commit();

            // Send notification to admins
            $this->notifyAdmins('complaint_new', $complaint);

            $complaint->load(['attachments', 'replies']);

            return response()->json([
                'success' => true,
                'message' => 'Complaint submitted successfully',
                'data'    => $complaint,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error submitting complaint',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update complaint status (admin only).
     */
    public function update(Request $request, $id)
    {
        try {
            [$user, $role] = $this->getCurrentUserInfo();
            if ($role !== 'admin') {
                return response()->json(['success' => false, 'message' => 'Only admins can update complaint status'], 403);
            }

            $complaint = Complaint::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'status'   => 'sometimes|in:pending,in_progress,resolved,rejected',
                'priority' => 'sometimes|in:normal,high,urgent',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $complaint->update($request->only(['status', 'priority']));

            // Notify complainant about status change
            $this->notifyComplainant('complaint_status', $complaint);

            return response()->json([
                'success' => true,
                'message' => 'Complaint updated successfully',
                'data'    => $complaint->fresh()->load(['attachments', 'replies']),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating complaint',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a complaint (admin only, or own complaint by non-admin).
     */
    public function destroy($id)
    {
        try {
            [$user, $role, $type] = $this->getCurrentUserInfo();
            $complaint = Complaint::findOrFail($id);

            $isOwner = $complaint->complainant_id === $user->id &&
                       $complaint->complainant_type === $type;

            if ($role !== 'admin' && !$isOwner) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            // Delete files from storage
            foreach ($complaint->attachments as $att) {
                Storage::disk('upload_attachments')->delete('attachments/complaints/' . $att->filename);
            }
            foreach ($complaint->replies as $reply) {
                foreach ($reply->attachments as $att) {
                    Storage::disk('upload_attachments')->delete('attachments/complaints/' . $att->filename);
                }
            }

            $complaint->delete();

            return response()->json([
                'success' => true,
                'message' => 'Complaint deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting complaint',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add a reply to a complaint (with optional file attachments).
     */
    public function reply(Request $request, $id)
    {
        try {
            [$user, $role, $type] = $this->getCurrentUserInfo();
            if (!$user) return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);

            $complaint = Complaint::findOrFail($id);

            // Non-admins can only reply to their own complaints
            if ($role !== 'admin') {
                $isOwner = $complaint->complainant_id === $user->id &&
                           $complaint->complainant_type === $type;
                if (!$isOwner) {
                    return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
                }
            }

            $validator = Validator::make($request->all(), [
                'message' => 'required|string',
                'files'   => 'nullable|array',
                'files.*' => 'file|mimes:pdf,jpeg,jpg,png,gif,webp,mp3,wav,ogg,mp4,mov,avi|max:51200',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            $reply = ComplaintReply::create([
                'complaint_id' => $complaint->id,
                'user_id'      => $user->id,
                'user_type'    => $type,
                'message'      => $request->message,
            ]);

            // Handle file uploads
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                    $file->storeAs('attachments/complaints', $filename, 'upload_attachments');

                    ComplaintAttachment::create([
                        'filename'          => $filename,
                        'original_filename' => $file->getClientOriginalName(),
                        'file_type'         => $file->getClientOriginalExtension(),
                        'file_size'         => $file->getSize(),
                        'mime_type'         => $file->getMimeType(),
                        'complaint_id'      => null,
                        'complaint_reply_id' => $reply->id,
                    ]);
                }
            }

            DB::commit();

            $reply->load('attachments');
            $u = $reply->user;
            $reply->user_name = $u ? ($u->name ?? $u->Name_Father ?? 'Unknown') : 'Unknown';
            $reply->user_role = $this->resolveRole($reply->user_type);

            // Notify the other party
            if ($role === 'admin') {
                $this->notifyComplainant('complaint_reply', $complaint);
            } else {
                $this->notifyAdmins('complaint_reply', $complaint);
            }

            return response()->json([
                'success' => true,
                'message' => 'Reply added successfully',
                'data'    => $reply,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error adding reply',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download an attachment file.
     */
    public function downloadAttachment($id)
    {
        try {
            $attachment = ComplaintAttachment::findOrFail($id);
            $filePath = public_path('attachments/complaints/' . $attachment->filename);

            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found',
                ], 404);
            }

            return response()->download($filePath, $attachment->original_filename);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error downloading file',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get unread complaints count for admin badge.
     */
    public function unreadCount()
    {
        try {
            [$user, $role] = $this->getCurrentUserInfo();
            if ($role !== 'admin') {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $count = Complaint::where('status', 'pending')->count();

            return response()->json([
                'success' => true,
                'message' => 'Unread count retrieved',
                'data'    => ['count' => $count],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving count',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // --- Private helpers ---

    private function resolveRole($userType)
    {
        if (strpos($userType, 'Student') !== false) return 'student';
        if (strpos($userType, 'Teacher') !== false) return 'teacher';
        if (strpos($userType, 'Parent') !== false) return 'parent';
        return 'admin';
    }

    private function notifyAdmins($type, $complaint)
    {
        try {
            $title = $type === 'complaint_new'
                ? 'شكوى جديدة'
                : 'رد جديد على شكوى';
            $body = 'شكوى: ' . $complaint->title;

            $notification = Notification::create([
                'title'       => $title,
                'body'        => $body,
                'type'        => 'role',
                'sender_id'   => $complaint->complainant_id,
                'sender_type' => $complaint->complainant_type,
                'target_role' => 'admin',
                'data'        => [
                    'type'          => $type,
                    'complaint_id'  => $complaint->id,
                ],
            ]);

            $now = now();
            $adminIds = User::pluck('id');
            $recipients = [];
            foreach ($adminIds as $adminId) {
                $recipients[] = [
                    'notification_id' => $notification->id,
                    'recipient_id'    => $adminId,
                    'recipient_type'  => 'App\User',
                    'is_read'         => false,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }
            if (!empty($recipients)) {
                NotificationRecipient::insert($recipients);
            }

            $fcm = new \App\Services\FcmNotificationService();
            $fcm->sendToRole('admin', $title, $body, [
                'type'         => $type,
                'complaint_id' => $complaint->id,
            ]);
        } catch (\Exception $e) {
            \Log::warning('Complaint notification failed: ' . $e->getMessage());
        }
    }

    private function notifyComplainant($type, $complaint)
    {
        try {
            $statusLabels = [
                'pending'     => 'قيد الانتظار',
                'in_progress' => 'قيد المعالجة',
                'resolved'    => 'تم الحل',
                'rejected'    => 'مرفوض',
            ];

            $title = $type === 'complaint_status'
                ? 'تحديث حالة الشكوى'
                : 'رد جديد على شكواك';
            $body = $type === 'complaint_status'
                ? 'تم تحديث حالة شكوى "' . $complaint->title . '" إلى: ' . ($statusLabels[$complaint->status] ?? $complaint->status)
                : 'هناك رد جديد على شكواك: ' . $complaint->title;

            $notification = Notification::create([
                'title'       => $title,
                'body'        => $body,
                'type'        => 'targeted',
                'sender_id'   => auth()->id(),
                'sender_type' => get_class(auth()->user()),
                'data'        => [
                    'type'         => $type,
                    'complaint_id' => $complaint->id,
                ],
            ]);

            $now = now();
            $recipients = [];

            // Add complainant
            $recipients[] = [
                'notification_id' => $notification->id,
                'recipient_id'    => $complaint->complainant_id,
                'recipient_type'  => $complaint->complainant_type,
                'is_read'         => false,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];

            NotificationRecipient::insert($recipients);

            $fcm = new \App\Services\FcmNotificationService();
            $fcm->sendToUsers([
                ['id' => $complaint->complainant_id, 'type' => $complaint->complainant_type],
            ], $title, $body, [
                'type'         => $type,
                'complaint_id' => $complaint->id,
            ]);
        } catch (\Exception $e) {
            \Log::warning('Complainant notification failed: ' . $e->getMessage());
        }
    }
}
