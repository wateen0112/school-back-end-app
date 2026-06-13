<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    public function index()
    {
        try {
            $settings = Setting::all();
            $mapped = $settings->pluck('value', 'key');
            
            return response()->json([
                'success' => true,
                'message' => 'Settings retrieved successfully',
                'data' => $settings,
                'settings' => $mapped,
                'logo_url' => $mapped->get('logo') ? asset('attachments/logo/' . $mapped->get('logo')) : null,
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateSchool(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'school_name' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            if ($request->filled('school_name')) {
                Setting::updateOrCreate(
                    ['key' => 'school_name'],
                    ['value' => $request->school_name]
                );
            }

            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                $logoName = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                $logoPath = public_path('attachments/logo');
                if (!is_dir($logoPath)) {
                    mkdir($logoPath, 0755, true);
                }
                $file->move($logoPath, $logoName);

                Setting::updateOrCreate(
                    ['key' => 'logo'],
                    ['value' => $logoName]
                );
            }

            $settings = Setting::all();
            $mapped = $settings->pluck('value', 'key');

            return response()->json([
                'success' => true,
                'message' => 'School settings updated successfully',
                'data' => $settings,
                'settings' => $mapped,
                'logo_url' => $mapped->get('logo') ? asset('attachments/logo/' . $mapped->get('logo')) : null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating school settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:255|unique:settings,key',
            'value' => 'required|string',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $setting = new Setting();
            $setting->key = $request->key;
            $setting->value = $request->value;
            $setting->description = $request->description;
            $setting->save();

            return response()->json([
                'success' => true,
                'message' => 'Setting created successfully',
                'data' => $setting
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $setting = Setting::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Setting retrieved successfully',
                'data' => $setting
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:255|unique:settings,key,' . $id,
            'value' => 'required|string',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $setting = Setting::findOrFail($id);
            $setting->key = $request->key;
            $setting->value = $request->value;
            $setting->description = $request->description;
            $setting->save();

            return response()->json([
                'success' => true,
                'message' => 'Setting updated successfully',
                'data' => $setting
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $setting = Setting::findOrFail($id);
            $setting->delete();

            return response()->json([
                'success' => true,
                'message' => 'Setting deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
