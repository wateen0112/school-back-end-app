<?php

namespace App\Http\Controllers\Api\WeeklyPrograms;

use App\Http\Controllers\Controller;
use App\Models\SectionSubjectTeacher;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SectionSubjectTeacherController extends Controller
{
    /**
     * List assignments with optional filters.
     */
    public function index(Request $request)
    {
        try {
            $query = SectionSubjectTeacher::with(['section', 'subject', 'teacher']);

            if ($request->filled('section_id')) {
                $query->where('section_id', $request->section_id);
            }
            if ($request->filled('subject_id')) {
                $query->where('subject_id', $request->subject_id);
            }
            if ($request->filled('teacher_id')) {
                $query->where('teacher_id', $request->teacher_id);
            }
            if ($request->filled('grade_id')) {
                $sectionIds = Section::where('Grade_id', $request->grade_id)->pluck('id');
                $query->whereIn('section_id', $sectionIds);
            }
            if ($request->filled('classroom_id')) {
                $sectionIds = Section::where('Class_id', $request->classroom_id)->pluck('id');
                $query->whereIn('section_id', $sectionIds);
            }

            return response()->json([
                'success' => true,
                'message' => 'Assignments retrieved successfully',
                'data'    => $query->get(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving assignments',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store or update an assignment.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'section_id' => 'required|exists:sections,id',
                'subject_id' => 'required|exists:subjects,id',
                'teacher_id' => 'nullable|exists:teachers,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $assignment = SectionSubjectTeacher::updateOrCreate(
                [
                    'section_id' => $request->section_id,
                    'subject_id' => $request->subject_id,
                ],
                [
                    'teacher_id' => $request->teacher_id,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ تعيين المعلم بنجاح',
                'data'    => $assignment->load(['section', 'subject', 'teacher']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving assignment',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show one assignment.
     */
    public function show($id)
    {
        try {
            $assignment = SectionSubjectTeacher::with(['section', 'subject', 'teacher'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Assignment retrieved successfully',
                'data'    => $assignment,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found',
                'error'   => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Delete an assignment.
     */
    public function destroy($id)
    {
        try {
            SectionSubjectTeacher::findOrFail($id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف التعيين بنجاح',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting assignment',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
