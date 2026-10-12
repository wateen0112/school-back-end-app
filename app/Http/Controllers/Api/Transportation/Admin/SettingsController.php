<?php

namespace App\Http\Controllers\Api\Transportation\Admin;

use App\Http\Controllers\Api\Transportation\TransportationController;
use App\Models\Transportation\TransportationSetting;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingsController extends TransportationController
{
    public function show(Request $request)
    {
        $schoolId = $this->schoolId($request);
        if (! $schoolId) {
            return $this->fail('A school context is required', 400);
        }

        return $this->ok(TransportationSetting::forSchool($schoolId), 'Transportation settings retrieved successfully');
    }

    public function update(Request $request)
    {
        $schoolId = $this->schoolId($request);
        if (! $schoolId) {
            return $this->fail('A school context is required', 400);
        }

        $validator = Validator::make($request->all(), [
            'geofence_radius_m' => 'nullable|integer|min:50|max:2000',
            'gps_interval_s' => 'nullable|integer|min:3|max:120',
            'parent_live_tracking' => 'nullable|boolean',
            'approaching_notifications' => 'nullable|boolean',
            'notify_parents_on_emergency' => 'nullable|boolean',
            'morning_default_start' => 'nullable|date_format:H:i',
            'afternoon_default_start' => 'nullable|date_format:H:i',
            'working_days' => 'nullable|array',
            'working_days.*' => 'integer|min:1|max:7',
            'map_provider' => 'nullable|in:osm,google,mapbox',
        ]);
        if ($validator->fails()) {
            return $this->fail('Validation error', 422, $validator->errors()->first());
        }

        try {
            $settings = TransportationSetting::forSchool($schoolId);
            $settings->update($validator->validated());

            AuditLogger::log('transportation.settings.updated', TransportationSetting::class, $settings->id, 'Transportation settings changed');

            return $this->ok($settings, 'Transportation settings updated successfully');
        } catch (\Exception $e) {
            return $this->fail('Error updating settings', 500, $e->getMessage());
        }
    }
}
