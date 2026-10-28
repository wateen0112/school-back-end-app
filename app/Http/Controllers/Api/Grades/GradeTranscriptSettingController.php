<?php

namespace App\Http\Controllers\Api\Grades;

use App\Http\Controllers\Controller;
use App\Models\GradeTranscriptSetting;
use Illuminate\Http\Request;

/**
 * إعدادات الجلاء المدرسي وحساب المحصلة لكل صف على حدة.
 */
class GradeTranscriptSettingController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = GradeTranscriptSetting::with(['grade', 'classroom']);

            if ($request->filled('classroom_id')) {
                $query->where('classroom_id', $request->classroom_id);
            }
            if ($request->filled('grade_id')) {
                $query->where('grade_id', $request->grade_id);
            }

            return response()->json([
                'success' => true,
                'message' => 'Grade transcript settings retrieved successfully',
                'data' => $query->get(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving grade transcript settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'classroom_id' => 'required|exists:Classrooms,id',
                'grade_id' => 'nullable|exists:Grades,id',
                'calculation_mode' => 'nullable|in:direct,coursework_exam',
                'coursework_max' => 'nullable|numeric|min:0|max:100',
                'exam_max' => 'nullable|numeric|min:0|max:100',
                'oral_max' => 'nullable|numeric|min:0',
                'homework_max' => 'nullable|numeric|min:0',
                'quizzes_max' => 'nullable|numeric|min:0',
                'exam_types' => 'nullable|array',
                'exam_types.*' => 'in:written_review_1,written_review_2,oral,coursework,final_exam',
                'pass_percentage' => 'nullable|numeric|min:0|max:100',
                'show_grade_level' => 'nullable|boolean',
                'is_active' => 'nullable|boolean',
            ]);

            $classroom = \App\Models\Classroom::find($validated['classroom_id']);

            $setting = GradeTranscriptSetting::updateOrCreate(
                ['classroom_id' => $validated['classroom_id']],
                array_merge([
                    'grade_id' => $validated['grade_id'] ?? optional($classroom)->Grade_id,
                    'calculation_mode' => GradeTranscriptSetting::MODE_COURSEWORK_EXAM,
                    'coursework_max' => 60,
                    'exam_max' => 40,
                    'oral_max' => 20,
                    'homework_max' => 20,
                    'quizzes_max' => 20,
                    'exam_types' => GradeTranscriptSetting::DEFAULT_EXAM_TYPES,
                    'pass_percentage' => 50,
                    'show_grade_level' => true,
                    'is_active' => true,
                ], array_filter($validated, fn ($v) => $v !== null))
            );

            $setting->load(['grade', 'classroom']);

            return response()->json([
                'success' => true,
                'message' => 'Grade transcript setting saved successfully',
                'data' => $setting,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving grade transcript setting',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            GradeTranscriptSetting::findOrFail($id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Grade transcript setting deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting grade transcript setting',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
