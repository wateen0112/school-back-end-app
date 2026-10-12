<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\PermissionTemplate;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PermissionTemplateController extends Controller
{
    public function index()
    {
        $templates = PermissionTemplate::orderByDesc('id')->get()->map(fn ($t) => $this->format($t));

        return response()->json(['success' => true, 'message' => 'Permission templates retrieved successfully', 'data' => $templates], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()], 422);
        }

        $template = PermissionTemplate::create([
            'name' => ['ar' => $request->name, 'en' => $request->name_en ?: $request->name],
            'description' => $request->description,
            'permissions' => $request->permissions ?? [],
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        AuditLogger::log('permission_template.created', 'PermissionTemplate', $template->id, 'إنشاء نموذج صلاحيات');

        return response()->json(['success' => true, 'message' => 'Permission template created successfully', 'data' => $this->format($template)], 201);
    }

    public function update(Request $request, $id)
    {
        $template = PermissionTemplate::find($id);

        if (! $template) {
            return response()->json(['success' => false, 'message' => 'Permission template not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()], 422);
        }

        if ($request->filled('name') || $request->filled('name_en')) {
            $translations = $template->getTranslations('name');
            $translations['ar'] = $request->input('name', $translations['ar'] ?? '');
            $translations['en'] = $request->input('name_en', $translations['en'] ?? $translations['ar'] ?? '');
            $template->name = $translations;
        }

        $template->fill($request->only(['description', 'permissions', 'is_active']));
        $template->save();

        AuditLogger::log('permission_template.updated', 'PermissionTemplate', $template->id, 'تعديل نموذج الصلاحيات');

        return response()->json(['success' => true, 'message' => 'Permission template updated successfully', 'data' => $this->format($template)], 200);
    }

    public function destroy(Request $request, $id)
    {
        $template = PermissionTemplate::find($id);

        if (! $template) {
            return response()->json(['success' => false, 'message' => 'Permission template not found'], 404);
        }

        $template->delete();

        AuditLogger::log('permission_template.deleted', 'PermissionTemplate', $id, 'حذف نموذج الصلاحيات');

        return response()->json(['success' => true, 'message' => 'Permission template deleted successfully'], 200);
    }

    private function format(PermissionTemplate $template)
    {
        $data = $template->toArray();
        $data['name'] = $template->getTranslations('name');

        return $data;
    }
}