<?php

namespace App\Http\Controllers\Api\Scheduling;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\SchedulePeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SchedulePeriodController extends Controller
{
    public function index(Request $request, ?AcademicYear $academicYear = null)
    {
        try {
            $schoolId = $this->resolveSchoolId($request);

            $periods = SchedulePeriod::query()
                ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
                ->when($request->has('academic_year_id') && $request->filled('academic_year_id'), fn ($q) => $q->where('academic_year_id', $request->academic_year_id))
                ->when(! $academicYear && (! $request->has('academic_year_id') || ! $request->filled('academic_year_id')), function ($q) use ($schoolId) {
                    $q->whereHas('academicYear', fn ($w) => $w->forSchool($schoolId)->active());
                })
                ->orderBy('academic_year_id')
                ->orderBy('period_number')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Schedule periods retrieved successfully',
                'data'    => $periods,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving schedule periods',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, AcademicYear $academicYear)
    {
        $guard = $this->authorizeSchool($request, $academicYear);
        if ($guard !== null) {
            return $guard;
        }

        try {
            $validator = Validator::make($request->all(), [
                'periods'                 => ['required', 'array', 'min:1'],
                'periods.*.period_number' => ['required', 'integer', 'distinct', 'between:1,30'],
                'periods.*.start_time'    => ['required', 'date_format:H:i'],
                'periods.*.end_time'      => ['required', 'date_format:H:i', 'after:periods.*.start_time'],
                'periods.*.type'          => ['required', 'in:' . SchedulePeriod::TYPE_LESSON . ',' . SchedulePeriod::TYPE_BREAK],
                'periods.*.note'          => ['nullable', 'string', 'max:100'],
            ]);

            if ($validator->fails()) {
                throw new \Illuminate\Validation\ValidationException($validator);
            }

            SchedulePeriod::where('academic_year_id', $academicYear->id)->delete();

            $rows = collect($request->periods)->map(function ($period) use ($academicYear) {
                return SchedulePeriod::create([
                    'academic_year_id' => $academicYear->id,
                    'period_number'    => $period['period_number'],
                    'start_time'       => $this->toSeconds($period['start_time']),
                    'end_time'         => $this->toSeconds($period['end_time']),
                    'type'             => $period['type'],
                    'note'             => $period['note'] ?? null,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الفترات الدراسية بنجاح',
                'data'    => $rows,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating schedule periods',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private function toSeconds(string $time): string
    {
        return strlen($time) === 5 ? $time . ':00' : $time;
    }

    private function resolveSchoolId(Request $request): ?int
    {
        $user = auth()->user();
        if ($user && isset($user->school_id)) {
            return (int) $user->school_id;
        }

        return $request->has('school_id') && $request->filled('school_id')
            ? (int) $request->school_id
            : null;
    }

    private function authorizeSchool(Request $request, AcademicYear $year): ?\Illuminate\Http\JsonResponse
    {
        $schoolId = $this->resolveSchoolId($request);
        if ($schoolId !== null && $year->school_id !== null && (int) $year->school_id !== $schoolId) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: academic year does not belong to this school',
            ], 403);
        }

        return null;
    }
}