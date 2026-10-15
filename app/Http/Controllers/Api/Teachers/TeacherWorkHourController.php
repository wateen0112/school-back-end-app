<?php

namespace App\Http\Controllers\Api\Teachers;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\TeacherWorkHour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * CRUD for logged work hours of hourly-contract teachers.
 * Hours are consumed by payroll generation (Σ hours × hour_rate).
 */
class TeacherWorkHourController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'teacher_id' => 'nullable|integer|exists:teachers,id',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = TeacherWorkHour::with('teacher:id,Name')->orderByDesc('work_date');

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }
        if ($request->filled('from')) {
            $query->whereDate('work_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('work_date', '<=', $request->to);
        }

        $hours = $query->get()->map(fn ($h) => [
            'id' => $h->id,
            'teacher_id' => $h->teacher_id,
            'teacher_name' => $h->teacher?->Name,
            'work_date' => $h->work_date?->toDateString(),
            'start_time' => $h->start_time,
            'end_time' => $h->end_time,
            'hours' => (float) $h->hours,
            'notes' => $h->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => trans('messages.work_hours_retrieved'),
            'data' => [
                'entries' => $hours,
                'total_hours' => (float) $hours->sum('hours'),
            ],
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'teacher_id' => 'required|integer|exists:teachers,id',
            'work_date' => 'required|date',
            // Either an explicit hour count, or a from/to time window
            // ("08:00" / "13:30") from which the hours are computed.
            'hours' => 'required_without:start_time|nullable|numeric|min:0.01|max:24',
            'start_time' => 'required_with:end_time|nullable|date_format:H:i',
            'end_time' => 'required_with:start_time|nullable|date_format:H:i|after:start_time',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $teacher = Teacher::findOrFail($request->teacher_id);
        if (($teacher->salary_type ?: 'monthly') !== 'hourly') {
            return response()->json([
                'success' => false,
                'message' => trans('messages.work_hours_only_hourly'),
            ], 422);
        }

        $hours = $request->filled('hours')
            ? (float) $request->hours
            : $this->hoursBetween($request->start_time, $request->end_time);

        if ($hours <= 0 || $hours > 24) {
            return response()->json([
                'success' => false,
                'message' => trans('messages.work_hours_invalid_window'),
                'errors' => ['end_time' => ['Invalid time window']],
            ], 422);
        }

        // Same teacher + same day: reject time windows that overlap any
        // previously recorded entry (legacy entries without a window are
        // ignored — there is nothing to conflict against).
        if ($request->filled('start_time')) {
            $overlap = TeacherWorkHour::where('teacher_id', $request->teacher_id)
                ->whereDate('work_date', $request->work_date)
                ->whereNotNull('start_time')
                ->whereNotNull('end_time')
                ->where('start_time', '<', $request->end_time.':00')
                ->where('end_time', '>', $request->start_time.':00')
                ->first();

            if ($overlap) {
                $conflict = trans('messages.work_hours_conflict', [
                    'start' => $overlap->start_time,
                    'end' => $overlap->end_time,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $conflict,
                    'errors' => ['start_time' => [$conflict]],
                ], 422);
            }
        }

        // Keep the from/to window visible next to any free-form note.
        $notes = $request->notes;
        if ($request->filled('start_time')) {
            $window = $request->start_time.' → '.$request->end_time;
            $notes = $notes ? $window.' — '.$notes : $window;
        }

        $entry = TeacherWorkHour::create([
            'teacher_id' => $request->teacher_id,
            'work_date' => $request->work_date,
            'start_time' => $request->filled('start_time') ? $request->start_time.':00' : null,
            'end_time' => $request->filled('end_time') ? $request->end_time.':00' : null,
            'hours' => round($hours, 2),
            'notes' => $notes,
            'created_by' => $request->user()?->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => trans('messages.work_hours_stored'),
            'data' => [
                'id' => $entry->id,
                'teacher_id' => $entry->teacher_id,
                'work_date' => $entry->work_date?->toDateString(),
                'start_time' => $entry->start_time,
                'end_time' => $entry->end_time,
                'hours' => (float) $entry->hours,
                'notes' => $entry->notes,
            ],
        ], 201);
    }

    /** Decimal hours between two "H:i" times (same day). */
    private function hoursBetween(string $start, string $end): float
    {
        [$sh, $sm] = array_map('intval', explode(':', $start));
        [$eh, $em] = array_map('intval', explode(':', $end));

        return (($eh * 60 + $em) - ($sh * 60 + $sm)) / 60;
    }

    public function destroy($id)
    {
        try {
            TeacherWorkHour::findOrFail($id)->delete();

            return response()->json([
                'success' => true,
                'message' => trans('messages.work_hours_deleted'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => trans('messages.work_hours_not_found'),
            ], 404);
        }
    }
}
