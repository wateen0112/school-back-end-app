<?php

namespace App\Http\Controllers\Api\Subjects;

use App\Http\Controllers\Controller;
use App\Models\SubjectGradeSetting;
use Illuminate\Http\Request;

/**
 * إعدادات المادة لكل مرحلة/صف: العلامة العظمى وعلامة الرسوب
 * (مرتبطة بعلاقة مادة-صف).
 */
class SubjectGradeSettingController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = SubjectGradeSetting::with(['subject', 'grade', 'classroom']);

            if ($request->filled('subject_id')) {
                $query->where('subject_id', $request->subject_id);
            }
            if ($request->filled('classroom_id')) {
                $query->where('classroom_id', $request->classroom_id);
            }
            if ($request->filled('grade_id')) {
                $query->where('grade_id', $request->grade_id);
            }

            $settings = $query->get();

            return response()->json([
                'success' => true,
                'message' => 'Subject grade settings retrieved successfully',
                'data' => $settings,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving subject grade settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'subject_id' => 'required|exists:subjects,id',
                'classroom_id' => 'required|exists:Classrooms,id',
                'grade_id' => 'nullable|exists:grades,id',
                'max_mark' => 'required|numeric|min:0',
                'fail_mark' => 'required|numeric|min:0|lte:max_mark',
            ]);

            $classroom = \App\Models\Classroom::find($validated['classroom_id']);

            $setting = SubjectGradeSetting::updateOrCreate(
                [
                    'subject_id' => $validated['subject_id'],
                    'classroom_id' => $validated['classroom_id'],
                ],
                [
                    'grade_id' => $validated['grade_id'] ?? optional($classroom)->Grade_id,
                    'max_mark' => $validated['max_mark'],
                    'fail_mark' => $validated['fail_mark'],
                ]
            );

            $setting->load(['subject', 'grade', 'classroom']);

            return response()->json([
                'success' => true,
                'message' => 'Subject grade setting saved successfully',
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
                'message' => 'Error saving subject grade setting',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $setting = SubjectGradeSetting::findOrFail($id);
            $setting->delete();

            return response()->json([
                'success' => true,
                'message' => 'Subject grade setting deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting subject grade setting',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
