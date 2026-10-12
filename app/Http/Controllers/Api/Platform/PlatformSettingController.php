<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlatformSettingController extends Controller
{
    public function index()
    {
        $settings = PlatformSetting::orderBy('group')->orderBy('key')
            ->get(['key', 'value', 'group', 'type', 'is_public', 'created_at', 'updated_at']);

        return response()->json([
            'success' => true,
            'message' => 'Platform settings retrieved successfully',
            'data' => $settings->groupBy('group'),
        ], 200);
    }

    /**
     * Public mirror (auth required, any role) used by the dashboard
     * feature-flags hook — only is_public settings are exposed.
     */
    public function publicSettings()
    {
        $settings = PlatformSetting::where('is_public', true)
            ->get(['key', 'type', 'value'])
            ->mapWithKeys(fn ($setting) => [$setting->key => $setting->typed_value]);

        return response()->json(['success' => true, 'message' => 'Public settings retrieved successfully', 'data' => $settings], 200);
    }

    /**
     * Upsert settings in bulk: payload is a map of key => value.
     */
    public function store(Request $request)
    {
        $payload = $request->input('settings', $request->all());

        if (! is_array($payload) || empty($payload)) {
            return response()->json(['success' => false, 'message' => 'لا توجد إعدادات للتحديث'], 400);
        }

        $keys = collect($payload)->keys();

        $validator = Validator::make(['keys' => $keys->toArray()], [
            'keys.*' => 'string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()], 422);
        }

        foreach ($payload as $key => $value) {
            $setting = PlatformSetting::where('key', $key)->first();

            if (! $setting) {
                continue; // only existing settings can be updated through here
            }

            if (is_bool($value)) {
                $stored = $value ? '1' : '0';
            } elseif (is_null($value)) {
                $stored = '';
            } else {
                $stored = (string) $value;
            }

            $setting->update(['value' => $stored]);
        }

        AuditLogger::log('platform_settings.updated', 'PlatformSetting', null, 'تحديث إعدادات المنصة', ['keys' => $keys->all()]);

        return response()->json(['success' => true, 'message' => 'Settings updated successfully'], 200);
    }
}
