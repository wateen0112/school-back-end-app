<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class IntegrationController extends Controller
{
    public function index()
    {
        $integrations = Integration::orderByDesc('id')->get();

        return response()->json(['success' => true, 'message' => 'Integrations retrieved successfully', 'data' => $integrations], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:integrations,code',
            'description' => 'nullable|string',
            'config' => 'nullable|array',
            'is_enabled' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()], 422);
        }

        $integration = Integration::create([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'config' => $request->config ?? [],
            'is_enabled' => $request->has('is_enabled') ? $request->boolean('is_enabled') : false,
        ]);

        AuditLogger::log('integration.created', 'Integration', $integration->id, 'إضافة تكامل جديد');

        return response()->json(['success' => true, 'message' => 'Integration created successfully', 'data' => $integration], 201);
    }

    public function update(Request $request, $id)
    {
        $integration = Integration::find($id);

        if (! $integration) {
            return response()->json(['success' => false, 'message' => 'Integration not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:50|unique:integrations,code,'.$integration->id,
            'description' => 'nullable|string',
            'config' => 'nullable|array',
            'is_enabled' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()], 422);
        }

        $integration->update($request->only(['name', 'code', 'description', 'config', 'is_enabled']));

        AuditLogger::log('integration.updated', 'Integration', $integration->id, 'تعديل التكامل');

        return response()->json(['success' => true, 'message' => 'Integration updated successfully', 'data' => $integration], 200);
    }

    public function toggle(Request $request, $id)
    {
        $integration = Integration::find($id);

        if (! $integration) {
            return response()->json(['success' => false, 'message' => 'Integration not found'], 404);
        }

        $integration->update(['is_enabled' => ! $integration->is_enabled]);

        AuditLogger::log('integration.toggled', 'Integration', $integration->id, 'تبديل حالة التكامل', [
            'is_enabled' => $integration->is_enabled,
        ]);

        return response()->json(['success' => true, 'message' => 'Integration toggled successfully', 'data' => $integration], 200);
    }

    public function destroy(Request $request, $id)
    {
        $integration = Integration::find($id);

        if (! $integration) {
            return response()->json(['success' => false, 'message' => 'Integration not found'], 404);
        }

        $integration->delete();

        AuditLogger::log('integration.deleted', 'Integration', $id, 'حذف التكامل');

        return response()->json(['success' => true, 'message' => 'Integration deleted successfully'], 200);
    }
}