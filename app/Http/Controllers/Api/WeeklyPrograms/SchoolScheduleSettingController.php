<?php

namespace App\Http\Controllers\Api\WeeklyPrograms;

use App\Http\Controllers\Controller;
use App\Models\SchoolScheduleSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SchoolScheduleSettingController extends Controller
{
    /**
     * Get the school schedule settings (working days, hours, breaks).
     */
    public function show(Request $request)
    {
        try {
            $schoolId = $this->resolveSchoolId($request);
            $settings = SchoolScheduleSetting::where('school_id', $schoolId)->first();

            if (!$settings) {
                $settings = [
                    'school_id'  => $schoolId,
                    'days'       => [0, 1, 2, 3, 4],
                    'start_time' => '08:00:00',
                    'end_time'   => '15:05:00',
                    'breaks'     => [],
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'School schedule settings retrieved successfully',
                'data'    => $settings,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving school schedule settings',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create or update the school schedule settings.
     */
    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'days'        => 'sometimes|array',
                'days.*'      => 'integer|between:0,6',
                'start_time'  => 'nullable|date_format:H:i',
                'end_time'    => 'nullable|date_format:H:i|after:start_time',
                'breaks'      => 'sometimes|array',
                'breaks.*.start' => 'required_with:breaks|date_format:H:i',
                'breaks.*.end'   => 'required_with:breaks|date_format:H:i',
                'breaks.*.name'  => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $schoolId = $this->resolveSchoolId($request);

            $settings = SchoolScheduleSetting::updateOrCreate(
                ['school_id' => $schoolId],
                [
                    'days'       => $request->input('days', [0, 1, 2, 3, 4]),
                    'start_time' => $request->input('start_time')
                        ? $request->start_time . ':00' : null,
                    'end_time'   => $request->input('end_time')
                        ? $request->end_time . ':00' : null,
                    'breaks'     => $request->input('breaks', []),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ إعدادات الجدول الدراسي بنجاح',
                'data'    => $settings,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving school schedule settings',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private function resolveSchoolId(Request $request): ?int
    {
        // Admin accounts carry school_id; role models may not.
        $user = auth()->user();
        if ($user && isset($user->school_id)) {
            return (int) $user->school_id;
        }
        return $request->has('school_id') && $request->filled('school_id')
            ? (int) $request->school_id
            : null;
    }
}