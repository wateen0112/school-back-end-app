<?php

namespace App\Http\Controllers\Api\Teachers;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\TeacherWeeklySchedule;
use App\Models\TeacherPreference;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Teacher self-service availability & preferences, always scoped to the
 * authenticated teacher. The request body never carries a teacher_id.
 */
class MyAvailabilityController extends Controller
{
    /**
     * The authenticated teacher's weekly availability (all 7 days) plus
     * their global preference row.
     */
    public function index(Request $request)
    {
        $teacher = $this->teacher($request);

        try {
            $service = new DashboardService();

            $rows = TeacherWeeklySchedule::where('teacher_id', $teacher->id)
                ->get()
                ->keyBy('day_of_week');

            $availability = [];
            for ($d = 0; $d <= 6; $d++) {
                $row = $rows->get($d);
                $availability[] = [
                    'day_of_week'  => $d,
                    'day_name'     => $service->dayName($d),
                    'start_time'   => $row ? substr((string) $row->start_time, 0, 5) : null,
                    'end_time'     => $row ? substr((string) $row->end_time, 0, 5) : null,
                    'is_available' => (bool) ($row?->is_available),
                    'is_preferred' => (bool) ($row?->is_preferred),
                ];
            }

            $preference = $teacher->preference;

            return response()->json([
                'success' => true,
                'message' => 'Teacher availability retrieved successfully',
                'data'    => [
                    'teacher_id'   => (int) $teacher->id,
                    'availability' => $availability,
                    'preference'   => $preference ? [
                        'preferred_consecutive' => $preference->preferred_consecutive,
                        'max_consecutive'       => $preference->max_consecutive,
                        'max_periods_per_day'   => $preference->max_periods_per_day,
                        'preferred_start_time'  => $preference->preferred_start_time
                            ? substr($preference->preferred_start_time, 0, 5) : null,
                        'preferred_end_time'    => $preference->preferred_end_time
                            ? substr($preference->preferred_end_time, 0, 5) : null,
                        'avoid_days'            => $preference->avoid_days ?: [],
                        'notes'                 => $preference->notes,
                    ] : null,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving teacher availability',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk upsert per-day availability for the authenticated teacher.
     * A day sent with is_available=false clears its schedule row.
     */
    public function syncSchedules(Request $request)
    {
        $teacher = $this->teacher($request);

        try {
            $normalized = $request->all();
            if (! empty($normalized['days']) && is_array($normalized['days'])) {
                foreach ($normalized['days'] as $i => $day) {
                    if (isset($day['start_time'])) {
                        $normalized['days'][$i]['start_time'] = substr((string) $day['start_time'], 0, 5);
                    }
                    if (isset($day['end_time'])) {
                        $normalized['days'][$i]['end_time'] = substr((string) $day['end_time'], 0, 5);
                    }
                }
            }
            $request->merge($normalized);

            $validator = Validator::make($request->all(), [
                'days'                   => 'required|array|min:0|max:7',
                'days.*.day_of_week'     => 'required|integer|between:0,6',
                'days.*.start_time'      => 'required_if:days.*.is_available,true,1|nullable|date_format:H:i',
                'days.*.end_time'        => 'required_if:days.*.is_available,true,1|nullable|date_format:H:i|after:days.*.start_time',
                'days.*.is_available'    => 'sometimes|boolean',
                'days.*.is_preferred'    => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            foreach ($request->days as $day) {
                $dayOfWeek = (int) $day['day_of_week'];
                $isAvailable = ! empty($day['is_available']);

                if (! $isAvailable) {
                    TeacherWeeklySchedule::where('teacher_id', $teacher->id)
                        ->where('day_of_week', $dayOfWeek)
                        ->delete();
                    continue;
                }

                TeacherWeeklySchedule::updateOrCreate(
                    ['teacher_id' => $teacher->id, 'day_of_week' => $dayOfWeek],
                    [
                        'start_time'   => $day['start_time'] . ':00',
                        'end_time'     => $day['end_time'] . ':00',
                        'is_available' => true,
                        'is_preferred' => ! empty($day['is_preferred']),
                        'note'         => $day['note'] ?? null,
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ أوقات التفضيل بنجاح',
                'data'    => $this->availabilityPayload($teacher),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving teacher schedules',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upsert global preferences for the authenticated teacher.
     */
    public function updatePreferences(Request $request)
    {
        $teacher = $this->teacher($request);

        try {
            $validator = Validator::make($request->all(), [
                'preferred_consecutive' => 'nullable|integer|min:1|max:8',
                'max_consecutive'       => 'nullable|integer|min:1|max:8',
                'preferred_start_time'  => 'nullable|date_format:H:i',
                'preferred_end_time'    => 'nullable|date_format:H:i',
                'avoid_days'            => 'sometimes|array',
                'avoid_days.*'          => 'integer|between:0,6',
                'notes'                 => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $start = $request->input('preferred_start_time');
            $end = $request->input('preferred_end_time');
            $avoid = $request->input('avoid_days', []);

            $preference = TeacherPreference::updateOrCreate(
                ['teacher_id' => $teacher->id],
                [
                    'preferred_consecutive' => $request->input('preferred_consecutive'),
                    'max_consecutive'       => $request->input('max_consecutive'),
                    'preferred_start_time'  => $start ? $start . ':00' : null,
                    'preferred_end_time'    => $end ? $end . ':00' : null,
                    'avoid_days'            => $avoid,
                    'notes'                 => $request->input('notes'),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ التفضيلات بنجاح',
                'data'    => [
                    'preferred_consecutive' => $preference->preferred_consecutive,
                    'max_consecutive'       => $preference->max_consecutive,
                    'max_periods_per_day'   => $preference->max_periods_per_day,
                    'preferred_start_time'  => $preference->preferred_start_time
                        ? substr($preference->preferred_start_time, 0, 5) : null,
                    'preferred_end_time'    => $preference->preferred_end_time
                        ? substr($preference->preferred_end_time, 0, 5) : null,
                    'avoid_days'            => $preference->avoid_days ?: [],
                    'notes'                 => $preference->notes,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving teacher preferences',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private function teacher(Request $request): Teacher
    {
        $user = $request->user();

        if (! $user instanceof Teacher) {
            abort(403, 'Only teachers can manage their availability.');
        }

        return $user;
    }

    private function availabilityPayload(Teacher $teacher): array
    {
        $service = new DashboardService();
        $rows = TeacherWeeklySchedule::where('teacher_id', $teacher->id)
            ->get()
            ->keyBy('day_of_week');

        $availability = [];
        for ($d = 0; $d <= 6; $d++) {
            $row = $rows->get($d);
            $availability[] = [
                'day_of_week'  => $d,
                'day_name'     => $service->dayName($d),
                'start_time'   => $row ? substr((string) $row->start_time, 0, 5) : null,
                'end_time'     => $row ? substr((string) $row->end_time, 0, 5) : null,
                'is_available' => (bool) ($row?->is_available),
                'is_preferred' => (bool) ($row?->is_preferred),
            ];
        }

        return $availability;
    }
}