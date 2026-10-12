<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $query = Announcement::orderByDesc('id');

        if ($request->query('audience')) {
            $query->where('audience', $request->query('audience'));
        }

        $announcements = $query->get()->map(function ($item) {
            $data = $item->toArray();
            $data['title'] = $item->getTranslations('title');
            $data['body'] = $item->getTranslations('body');

            return $data;
        });

        return response()->json(['success' => true, 'message' => 'Announcements retrieved successfully', 'data' => $announcements], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'body' => 'required|string',
            'body_en' => 'nullable|string',
            'audience' => 'nullable|string|in:all,admins,teachers,parents,students',
            'priority' => 'nullable|string|in:low,medium,high',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()], 422);
        }

        $announcement = Announcement::create([
            'title' => ['ar' => $request->title, 'en' => $request->title_en ?: $request->title],
            'body' => ['ar' => $request->body, 'en' => $request->body_en ?: $request->body],
            'audience' => $request->audience ?: 'all',
            'priority' => $request->priority ?: 'low',
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
            'created_by' => $request->user()->id ?? null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        AuditLogger::log('announcement.created', 'Announcement', $announcement->id, 'إنشاء إعلان');

        return response()->json(['success' => true, 'message' => 'Announcement created successfully', 'data' => $this->format($announcement)], 201);
    }

    public function update(Request $request, $id)
    {
        $announcement = Announcement::find($id);

        if (! $announcement) {
            return response()->json(['success' => false, 'message' => 'Announcement not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'body' => 'nullable|string',
            'body_en' => 'nullable|string',
            'audience' => 'nullable|string|in:all,admins,teachers,parents,students',
            'priority' => 'nullable|string|in:low,medium,high',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()], 422);
        }

        if ($request->filled('title') || $request->filled('title_en')) {
            $t = $announcement->getTranslations('title');
            $t['ar'] = $request->input('title', $t['ar'] ?? '');
            $t['en'] = $request->input('title_en', $t['en'] ?? $t['ar'] ?? '');
            $announcement->title = $t;
        }

        if ($request->filled('body') || $request->filled('body_en')) {
            $b = $announcement->getTranslations('body');
            $b['ar'] = $request->input('body', $b['ar'] ?? '');
            $b['en'] = $request->input('body_en', $b['en'] ?? $b['ar'] ?? '');
            $announcement->body = $b;
        }

        $announcement->fill($request->only(['audience', 'priority', 'starts_at', 'ends_at', 'is_active']));
        $announcement->save();

        AuditLogger::log('announcement.updated', 'Announcement', $announcement->id, 'تعديل الإعلان');

        return response()->json(['success' => true, 'message' => 'Announcement updated successfully', 'data' => $this->format($announcement)], 200);
    }

    /**
     * Publish / unpublish an announcement.
     */
    public function broadcast(Request $request, $id)
    {
        $announcement = Announcement::find($id);

        if (! $announcement) {
            return response()->json(['success' => false, 'message' => 'Announcement not found'], 404);
        }

        $announcement->update(['is_active' => true]);

        AuditLogger::log('announcement.broadcast', 'Announcement', $announcement->id, 'نشر إعلان للمستخدمين');

        return response()->json(['success' => true, 'message' => 'Announcement published successfully', 'data' => $this->format($announcement)], 200);
    }

    public function destroy(Request $request, $id)
    {
        $announcement = Announcement::find($id);

        if (! $announcement) {
            return response()->json(['success' => false, 'message' => 'Announcement not found'], 404);
        }

        $announcement->delete();

        AuditLogger::log('announcement.deleted', 'Announcement', $id, 'حذف الإعلان');

        return response()->json(['success' => true, 'message' => 'Announcement deleted successfully'], 200);
    }

    private function format(Announcement $announcement)
    {
        $data = $announcement->toArray();
        $data['title'] = $announcement->getTranslations('title');
        $data['body'] = $announcement->getTranslations('body');

        return $data;
    }
}