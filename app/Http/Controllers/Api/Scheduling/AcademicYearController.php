<?php

namespace App\Http\Controllers\Api\Scheduling;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Services\Scheduling\AnnualCalendarService;
use Illuminate\Http\Request;
use Illuminate\Validation\Validator;
use Illuminate\Support\Facades\Validator as ValidatorFacade;

class AcademicYearController extends Controller
{
    public function __construct(private AnnualCalendarService $calendar)
    {
    }

    public function index(Request $request)
    {
        try {
            $schoolId = $this->resolveSchoolId($request);

            $years = AcademicYear::query()
                ->forSchool($schoolId)
                ->when($request->has('status') && $request->filled('status'), fn ($q) => $q->where('status', $request->status))
                ->withCount(['weeks', 'periods'])
                ->orderByDesc('start_date')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Academic years retrieved successfully',
                'data'    => $years,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving academic years',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, AcademicYear $academicYear)
    {
        $guard = $this->authorizeSchool($request, $academicYear);
        if ($guard !== null) {
            return $guard;
        }

        $academicYear->load('weeks')->load('periods');

        return response()->json([
            'success' => true,
            'message' => 'Academic year retrieved successfully',
            'data'    => $academicYear,
        ], 200);
    }

    public function calendar(Request $request, AcademicYear $academicYear)
    {
        $guard = $this->authorizeSchool($request, $academicYear);
        if ($guard !== null) {
            return $guard;
        }

        try {
            return response()->json([
                'success' => true,
                'message' => 'Annual calendar generated',
                'data'    => $this->calendar->calendar($academicYear),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating annual calendar',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function weeks(Request $request, AcademicYear $academicYear)
    {
        $guard = $this->authorizeSchool($request, $academicYear);
        if ($guard !== null) {
            return $guard;
        }

        return response()->json([
            'success' => true,
            'message' => 'Academic weeks retrieved successfully',
            'data'    => $academicYear->weeks()->orderBy('week_number')->get(),
        ], 200);
    }

    public function store(Request $request)
    {
        try {
            $schoolId = $this->resolveSchoolId($request);
            $validated = $this->validateYear($request);

            $year = $this->createYear($schoolId, $validated);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء السنة الدراسية بنجاح',
                'data'    => $year->fresh(['weeks', 'periods']),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating academic year',
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
            $validated = $this->validateYear($request, true);
            $data = $this->normalizePayload($validated);

            $academicYear->update($data);

            if (array_intersect(['start_date', 'end_date', 'working_days',
                'start_time', 'end_time', 'period_duration_min', 'periods_count', 'breaks',
            ], array_keys($data))) {
                $this->calendar->syncWeeks($academicYear);
                $this->calendar->syncPeriods($academicYear);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث السنة الدراسية بنجاح',
                'data'    => $academicYear->fresh(['weeks', 'periods']),
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
                'message' => 'Error updating academic year',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, AcademicYear $academicYear)
    {
        $guard = $this->authorizeSchool($request, $academicYear);
        if ($guard !== null) {
            return $guard;
        }

        try {
            $academicYear->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف السنة الدراسية بنجاح',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting academic year',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function setActive(Request $request, AcademicYear $academicYear)
    {
        $guard = $this->authorizeSchool($request, $academicYear);
        if ($guard !== null) {
            return $guard;
        }

        try {
            $schoolId = $this->resolveSchoolId($request);
            AcademicYear::query()
                ->forSchool($schoolId)
                ->where('id', '!=', $academicYear->id)
                ->update(['status' => AcademicYear::STATUS_DRAFT]);

            $academicYear->update(['status' => AcademicYear::STATUS_ACTIVE]);

            return response()->json([
                'success' => true,
                'message' => 'تم تفعيل السنة الدراسية بنجاح',
                'data'    => $academicYear->fresh(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error activating academic year',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private function validateYear(Request $request, bool $partial = false): array
    {
        $rules = [
            'name_ar'             => ['required', 'string', 'max:255'],
            'name_en'             => ['nullable', 'string', 'max:255'],
            'start_date'          => ['required', 'date'],
            'end_date'            => ['required', 'date', 'after_or_equal:start_date'],
            'working_days'        => ['sometimes', 'array'],
            'working_days.*'      => ['integer', 'between:0,6'],
            'start_time'          => ['nullable', 'date_format:H:i'],
            'end_time'            => ['nullable', 'date_format:H:i', 'after:start_time'],
            'period_duration_min' => ['sometimes', 'integer', 'between:15,180'],
            'periods_count'       => ['sometimes', 'integer', 'between:1,20'],
            'breaks'              => ['sometimes', 'array'],
            'breaks.*.start'      => ['required_with:breaks', 'date_format:H:i'],
            'breaks.*.end'        => ['required_with:breaks', 'date_format:H:i'],
            'breaks.*.name'       => ['nullable', 'string', 'max:100'],
            'status'              => ['sometimes', 'in:draft,active,archived'],
        ];

        $validator = ValidatorFacade::make($request->all(), $partial ? $this->sometimes($rules) : $rules);

        if ($validator->fails()) {
            throw $this->validationException($validator);
        }

        return $partial ? $validator->validated() : $validator->validated();
    }

    private function createYear(?int $schoolId, array $validated): AcademicYear
    {
        $data = $this->normalizePayload($validated);
        $data['school_id'] = $schoolId;

        $year = AcademicYear::create($data);
        $this->calendar->syncWeeks($year);
        $this->calendar->syncPeriods($year);

        return $year;
    }

    private function normalizePayload(array $validated): array
    {
        $data = $validated;

        if (array_key_exists('name_ar', $validated)) {
            $ar = $validated['name_ar'] ?? '';
            $en = $validated['name_en'] ?? $ar;
            $data['name'] = ['ar' => $ar, 'en' => $en];
        }
        unset($data['name_ar'], $data['name_en']);

        $data['working_days']  = $data['working_days'] ?? [0, 1, 2, 3, 4];
        $data['breaks']        = $data['breaks'] ?? [];
        $data['period_duration_min'] = (int) ($data['period_duration_min'] ?? 45);
        $data['periods_count'] = (int) ($data['periods_count'] ?? 8);

        foreach (['start_time', 'end_time'] as $time) {
            if (! empty($data[$time]) && strlen($data[$time]) === 5) {
                $data[$time] = $data[$time] . ':00';
            }
        }

        return $data;
    }

    private function sometimes(array $rules): array
    {
        return collect($rules)->mapWithKeys(fn ($value, $key) => [
            $key => array_merge(['sometimes'], is_array($value) ? $value : [$value]),
        ])->all();
    }

    private function validationException(Validator $validator): \Illuminate\Validation\ValidationException
    {
        $exception = new \Illuminate\Validation\ValidationException($validator);
        return $exception;
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