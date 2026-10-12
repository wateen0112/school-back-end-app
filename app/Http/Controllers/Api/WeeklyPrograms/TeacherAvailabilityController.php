<?php

namespace App\Http\Controllers\Api\WeeklyPrograms;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\TeacherWeeklySchedule;
use App\Models\TeacherPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeacherAvailabilityController extends Controller
{
    /**
     * List teacher weekly schedules with optional filters.
     */
    public function index(Request $request)
    {
        try {
            $query = TeacherWeeklySchedule::with('teacher');

            if ($request->filled('teacher_id')) {
                $query->where('teacher_id', $request->teacher_id);
            }
            if ($request->has('day_of_week') && $request->filled('day_of_week')) {
                $query->where('day_of_week', $request->day_of_week);
            }

            $schedules = $query->orderBy('teacher_id')->orderBy('day_of_week')->get();

            return response()->json([
                'success' => true,
                'message' => 'Teacher schedules retrieved successfully',
                'data'    => $schedules,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving teacher schedules',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create or update the weekly schedule of a teacher for one day.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'teacher_id'   => 'required|exists:teachers,id',
                'day_of_week'  => 'required|integer|between:0,6',
                'start_time'   => 'required|date_format:H:i',
                'end_time'     => 'required|date_format:H:i|after:start_time',
                'is_available' => 'sometimes|boolean',
                'is_preferred' => 'sometimes|boolean',
                'note'         => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $schedule = TeacherWeeklySchedule::updateOrCreate(
                [
                    'teacher_id'  => $request->teacher_id,
                    'day_of_week' => (int) $request->day_of_week,
                ],
                [
                    'start_time'   => $request->start_time . ':00',
                    'end_time'     => $request->end_time . ':00',
                    'is_available' => $request->boolean('is_available', true),
                    'is_preferred' => $request->boolean('is_preferred', false),
                    'note'         => $request->note,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ الجدول الأسبوعي للمعلم بنجاح',
                'data'    => $schedule->load('teacher'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving teacher schedule',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk save schedules for one teacher.
     */
    public function bulkStore(Request $request)
    {
        try {
            // Normalize day times to H:i (drop seconds) so re-saved rows from
            // TIME columns ("10:00:00") never fail the H:i format rule.
            $normalized = $request->all();
            if (!empty($normalized['days']) && is_array($normalized['days'])) {
                foreach ($normalized['days'] as $i => $day) {
                    if (isset($day['start_time'])) $normalized['days'][$i]['start_time'] = substr($day['start_time'], 0, 5);
                    if (isset($day['end_time']))   $normalized['days'][$i]['end_time']   = substr($day['end_time'], 0, 5);
                }
            }
            $request->merge($normalized);

            $validator = Validator::make($request->all(), [
                'teacher_id' => 'required|exists:teachers,id',
                'days'       => 'required|array|min:1|max:7',
                'days.*.day_of_week'  => 'required|integer|between:0,6',
                'days.*.start_time'   => 'required|date_format:H:i',
                'days.*.end_time'     => 'required|date_format:H:i|after:days.*.start_time',
                'days.*.is_available' => 'sometimes|boolean',
                'days.*.is_preferred' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $saved = [];
            foreach ($request->days as $day) {
                $saved[] = TeacherWeeklySchedule::updateOrCreate(
                    [
                        'teacher_id'  => $request->teacher_id,
                        'day_of_week' => (int) $day['day_of_week'],
                    ],
                    [
                        'start_time'   => $day['start_time'] . ':00',
                        'end_time'     => $day['end_time'] . ':00',
                        'is_available' => !empty($day['is_available']),
                        'is_preferred' => !empty($day['is_preferred']),
                        'note'         => $day['note'] ?? null,
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ جدول المعلم بنجاح',
                'data'    => \Illuminate\Database\Eloquent\Collection::make($saved)->load('teacher'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving teacher schedules',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete one schedule row.
     */
    public function destroy($id)
    {
        try {
            TeacherWeeklySchedule::findOrFail($id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الجدول بنجاح',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting teacher schedule',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List teacher preferences.
     */
    public function preferences(Request $request)
    {
        try {
            $query = TeacherPreference::with('teacher');

            if ($request->filled('teacher_id')) {
                $query->where('teacher_id', $request->teacher_id);
            }

            return response()->json([
                'success' => true,
                'message' => 'Teacher preferences retrieved successfully',
                'data'    => $query->get(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving teacher preferences',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create or update one teacher's preferences.
     */
    public function savePreferences(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'teacher_id'             => 'required|exists:teachers,id',
                'preferred_consecutive'  => 'nullable|integer|min:1|max:8',
                'max_consecutive'        => 'nullable|integer|min:1|max:8',
                'preferred_start_time'   => 'nullable|date_format:H:i',
                'preferred_end_time'     => 'nullable|date_format:H:i',
                'avoid_days'             => 'sometimes|array',
                'avoid_days.*'           => 'integer|between:0,6',
                'notes'                  => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $preference = TeacherPreference::updateOrCreate(
                ['teacher_id' => $request->teacher_id],
                [
                    'preferred_consecutive' => $request->input('preferred_consecutive'),
                    'max_consecutive'       => $request->input('max_consecutive'),
                    'preferred_start_time'  => $request->input('preferred_start_time')
                        ? $request->preferred_start_time . ':00' : null,
                    'preferred_end_time'    => $request->input('preferred_end_time')
                        ? $request->preferred_end_time . ':00' : null,
                    'avoid_days'            => $request->input('avoid_days', []),
                    'notes'                 => $request->notes,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ تفضيلات المعلم بنجاح',
                'data'    => $preference->load('teacher'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving teacher preferences',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}