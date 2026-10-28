<?php

namespace App\Http\Controllers\Api\Grades;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\GradePassFailEvaluationService;
use Illuminate\Http\Request;

/**
 * تقييم نجاح/رسوب طالب وفق إعدادات صفه الخاصة.
 */
class GradePassFailEvaluationController extends Controller
{
    protected $service;

    public function __construct(GradePassFailEvaluationService $service)
    {
        $this->service = $service;
    }

    public function show(Request $request, $studentId)
    {
        try {
            $student = Student::findOrFail($studentId);

            $result = $this->service->evaluate(
                $student,
                $request->input('classroom_id') ?? $request->input('grade_id')
            );

            return response()->json([
                'success' => true,
                'message' => 'Pass/fail evaluation retrieved successfully',
                'data' => $result,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error evaluating pass/fail',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
