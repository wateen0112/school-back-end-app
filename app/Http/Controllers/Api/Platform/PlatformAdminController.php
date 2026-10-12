<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class PlatformAdminController extends Controller
{
    public function index()
    {
        $admins = User::where('is_super_admin', true)
            ->orderByDesc('id')->get(['id', 'name', 'email', 'created_at']);

        return response()->json(['success' => true, 'message' => 'Platform admins retrieved successfully', 'data' => $admins], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()], 422);
        }

        $admin = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'school_id' => null,
            'is_super_admin' => true,
        ]);

        AuditLogger::log('platform_admin.created', 'User', $admin->id, 'إضافة مشرف نظام جديد');

        return response()->json(['success' => true, 'message' => 'Platform admin created successfully', 'data' => $admin], 201);
    }

    public function update(Request $request, $id)
    {
        $admin = User::where('is_super_admin', true)->find($id);

        if (! $admin) {
            return response()->json(['success' => false, 'message' => 'Platform admin not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email,'.$admin->id,
            'password' => 'nullable|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()], 422);
        }

        $data = $request->only(['name', 'email']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $admin->update($data);

        AuditLogger::log('platform_admin.updated', 'User', $admin->id, 'تعديل بيانات مشرف النظام');

        return response()->json(['success' => true, 'message' => 'Platform admin updated successfully', 'data' => $admin], 200);
    }

    /**
     * Demote / remove a platform admin. The current super admin cannot
     * demote themselves (keeps at least one platform admin alive).
     */
    public function destroy(Request $request, $id)
    {
        $admin = User::where('is_super_admin', true)->find($id);

        if (! $admin) {
            return response()->json(['success' => false, 'message' => 'Platform admin not found'], 404);
        }

        if ($request->user()->id === $admin->id) {
            return response()->json(['success' => false, 'message' => 'لا يمكن إزالة حسابك الحالي'], 409);
        }

        $admin->update(['is_super_admin' => false, 'school_id' => null]);

        AuditLogger::log('platform_admin.demoted', 'User', $admin->id, 'إزالة مشرف النظام');

        return response()->json(['success' => true, 'message' => 'Platform admin removed successfully'], 200);
    }
}
