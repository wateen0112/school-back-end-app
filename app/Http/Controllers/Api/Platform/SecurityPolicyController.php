<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\SecurityPolicy;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SecurityPolicyController extends Controller
{
    public function index()
    {
        $policies = SecurityPolicy::orderByDesc('id')->get();

        return response()->json(['success' => true, 'message' => 'Security policies retrieved successfully', 'data' => $policies], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:security_policies,code',
            'description' => 'nullable|string',
            'config' => 'nullable|array',
            'is_enabled' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()], 422);
        }

        $policy = SecurityPolicy::create([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'config' => $request->config ?? [],
            'is_enabled' => $request->has('is_enabled') ? $request->boolean('is_enabled') : false,
        ]);

        AuditLogger::log('security_policy.created', 'SecurityPolicy', $policy->id, 'إنشاء سياسة أمان');

        return response()->json(['success' => true, 'message' => 'Security policy created successfully', 'data' => $policy], 201);
    }

    public function update(Request $request, $id)
    {
        $policy = SecurityPolicy::find($id);

        if (! $policy) {
            return response()->json(['success' => false, 'message' => 'Security policy not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:50|unique:security_policies,code,'.$policy->id,
            'description' => 'nullable|string',
            'config' => 'nullable|array',
            'is_enabled' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()], 422);
        }

        $policy->update($request->only(['name', 'code', 'description', 'config', 'is_enabled']));

        AuditLogger::log('security_policy.updated', 'SecurityPolicy', $policy->id, 'تعديل سياسة الأمان');

        return response()->json(['success' => true, 'message' => 'Security policy updated successfully', 'data' => $policy], 200);
    }

    public function toggle(Request $request, $id)
    {
        $policy = SecurityPolicy::find($id);

        if (! $policy) {
            return response()->json(['success' => false, 'message' => 'Security policy not found'], 404);
        }

        $policy->update(['is_enabled' => ! $policy->is_enabled]);

        AuditLogger::log('security_policy.toggled', 'SecurityPolicy', $policy->id, 'تبديل حالة سياسة الأمان', [
            'is_enabled' => $policy->is_enabled,
        ]);

        return response()->json(['success' => true, 'message' => 'Security policy toggled successfully', 'data' => $policy], 200);
    }

    public function destroy(Request $request, $id)
    {
        $policy = SecurityPolicy::find($id);

        if (! $policy) {
            return response()->json(['success' => false, 'message' => 'Security policy not found'], 404);
        }

        $policy->delete();

        AuditLogger::log('security_policy.deleted', 'SecurityPolicy', $id, 'حذف سياسة الأمان');

        return response()->json(['success' => true, 'message' => 'Security policy deleted successfully'], 200);
    }
}