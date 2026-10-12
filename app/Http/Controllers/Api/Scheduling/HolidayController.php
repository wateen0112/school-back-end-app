<?php

namespace App\Http\Controllers\Api\Scheduling;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        try {
            $schoolId = $this->resolveSchoolId($request);

            $holidays = Holiday::query()
                ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
                ->when($request->has('academic_year_id') && $request->filled('academic_year_id'), function ($q) use ($request) {
                    $q->where(fn ($w) => $w->where('academic_year_id', $request->academic_year_id)->orWhereNull('academic_year_id'));
                })
                ->when($request->has('type') && $request->filled('type'), fn ($q) => $q->where('type', $request->type))
                ->with('academicYear:id,name')
                ->orderBy('date')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Holidays retrieved successfully',
                'data'    => $holidays,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving holidays',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $this->validateHoliday($request);
            $schoolId = $this->resolveSchoolId($request);

            $holiday = Holiday::create([
                'school_id'        => $schoolId,
                'academic_year_id' => $request->input('academic_year_id') ?: null,
                'name'             => $this->translatedName($request),
                'date'             => $request->date,
                'type'             => $request->input('type', Holiday::TYPE_HOLIDAY),
                'affects_schedule' => $request->boolean('affects_schedule', true),
                'notes'            => $request->input('notes'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الإجازة بنجاح',
                'data'    => $holiday,
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
                'message' => 'Error creating holiday',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, Holiday $holiday)
    {
        $guard = $this->authorizeSchool($request, $holiday);
        if ($guard !== null) {
            return $guard;
        }

        try {
            $this->validateHoliday($request);

            $holiday->update([
                'academic_year_id' => $request->input('academic_year_id') ?: null,
                'name'             => $this->translatedName($request),
                'date'             => $request->date,
                'type'             => $request->input('type', Holiday::TYPE_HOLIDAY),
                'affects_schedule' => $request->boolean('affects_schedule', true),
                'notes'            => $request->input('notes'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الإجازة بنجاح',
                'data'    => $holiday->fresh(),
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
                'message' => 'Error updating holiday',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, Holiday $holiday)
    {
        $guard = $this->authorizeSchool($request, $holiday);
        if ($guard !== null) {
            return $guard;
        }

        try {
            $holiday->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الإجازة بنجاح',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting holiday',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private function validateHoliday(Request $request): void
    {
        $validator = Validator::make($request->all(), [
            'name_ar'           => ['required', 'string', 'max:255'],
            'name_en'           => ['nullable', 'string', 'max:255'],
            'academic_year_id'  => ['nullable', 'integer', 'exists:academic_years,id'],
            'date'              => ['required', 'date'],
            'type'              => ['required', 'in:' . implode(',', Holiday::types())],
            'affects_schedule'  => ['sometimes', 'boolean'],
            'notes'             => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }

    private function translatedName(Request $request): array
    {
        $ar = $request->input('name_ar', '');
        $en = $request->input('name_en') ?: $ar;

        return ['ar' => $ar, 'en' => $en];
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

    private function authorizeSchool(Request $request, Holiday $holiday): ?\Illuminate\Http\JsonResponse
    {
        $schoolId = $this->resolveSchoolId($request);
        if ($schoolId !== null && $holiday->school_id !== null && (int) $holiday->school_id !== $schoolId) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: holiday does not belong to this school',
            ], 403);
        }

        return null;
    }
}