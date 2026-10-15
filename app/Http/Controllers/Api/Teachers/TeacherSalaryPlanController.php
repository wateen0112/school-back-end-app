<?php

namespace App\Http\Controllers\Api\Teachers;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Services\Teachers\TeacherSalaryPlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Read/update a teacher's salary contract (type, hour rate, custom amount
 * and its non-fixed installments). Used by the accounting & HR screens.
 */
class TeacherSalaryPlanController extends Controller
{
    protected TeacherSalaryPlanService $plans;

    public function __construct()
    {
        $this->plans = new TeacherSalaryPlanService();
    }

    public function show($teacherId)
    {
        try {
            $teacher = Teacher::with(['customInstallments', 'workHours'])->findOrFail($teacherId);

            return response()->json([
                'success' => true,
                'message' => 'Teacher salary plan retrieved successfully',
                'data' => $this->serialize($teacher),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher not found',
            ], 404);
        }
    }

    public function update(Request $request, $teacherId)
    {
        try {
            $teacher = Teacher::findOrFail($teacherId);

            $teacher = $this->plans->apply($teacher, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Teacher salary plan updated successfully',
                'data' => $this->serialize($teacher),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating teacher salary plan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function serialize(Teacher $teacher): array
    {
        $type = $teacher->salary_type ?: 'monthly';

        return [
            'teacher_id' => $teacher->id,
            'teacher_name' => $teacher->Name,
            'salary_type' => $type,
            'salary' => $teacher->salary,
            'hour_rate' => $teacher->hour_rate,
            'custom_salary_amount' => $teacher->custom_salary_amount,
            'custom_remaining' => $type === 'custom'
                ? (float) $teacher->customInstallments()->pending()->sum('amount')
                : null,
            'custom_installments' => $teacher->customInstallments->map(fn ($i) => [
                'id' => $i->id,
                'title' => $i->title,
                'amount' => (float) $i->amount,
                'due_date' => $i->due_date?->toDateString(),
                'status' => $i->status,
                'notes' => $i->notes,
            ])->values(),
            'worked_hours_total' => $type === 'hourly'
                ? (float) DB::table('teacher_work_hours')->where('teacher_id', $teacher->id)->sum('hours')
                : null,
        ];
    }
}
