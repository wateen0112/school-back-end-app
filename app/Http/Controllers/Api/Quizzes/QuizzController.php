<?php

namespace App\Http\Controllers\Api\Quizzes;

use App\Http\Controllers\Controller;
use App\Models\Quizze;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QuizzController extends Controller
{
    public function index()
    {
        try {
            $quizzes = Quizze::with(['subject', 'teacher', 'grade', 'classroom', 'section'])->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Quizzes retrieved successfully',
                'data' => $quizzes
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving quizzes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'Name_ar' => 'required|string|max:255',
            'Name_en' => 'required|string|max:255',
            'subject_id' => 'required|exists:subjects,id',
            'Grade_id' => 'required',
            'Classroom_id' => 'required',
            'section_id' => 'required|exists:sections,id',
            'teacher_id' => 'required|exists:teachers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $quiz = new Quizze();
            $quiz->name = ['en' => $request->Name_en, 'ar' => $request->Name_ar];
            $quiz->subject_id = $request->subject_id;
            $quiz->grade_id = $request->Grade_id;
            $quiz->classroom_id = $request->Classroom_id;
            $quiz->section_id = $request->section_id;
            $quiz->teacher_id = $request->teacher_id;
            $quiz->save();

            return response()->json([
                'success' => true,
                'message' => 'Quiz created successfully',
                'data' => $quiz->load(['subject', 'teacher', 'grade', 'classroom', 'section'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating quiz',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $quiz = Quizze::with(['subject', 'teacher', 'grade', 'classroom', 'section', 'questions'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Quiz retrieved successfully',
                'data' => $quiz
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id = null)
    {
        $validator = Validator::make($request->all(), [
            'Name_ar' => 'required|string|max:255',
            'Name_en' => 'required|string|max:255',
            'subject_id' => 'required|exists:subjects,id',
            'Grade_id' => 'required',
            'Classroom_id' => 'required',
            'section_id' => 'required|exists:sections,id',
            'teacher_id' => 'required|exists:teachers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $quiz = Quizze::findOrFail($request->input('id', $id));
            $quiz->name = ['en' => $request->Name_en, 'ar' => $request->Name_ar];
            $quiz->subject_id = $request->subject_id;
            $quiz->grade_id = $request->Grade_id;
            $quiz->classroom_id = $request->Classroom_id;
            $quiz->section_id = $request->section_id;
            $quiz->teacher_id = $request->teacher_id;
            $quiz->save();

            return response()->json([
                'success' => true,
                'message' => 'Quiz updated successfully',
                'data' => $quiz->load(['subject', 'teacher', 'grade', 'classroom', 'section'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating quiz',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id = null)
    {
        try {
            $quiz = Quizze::findOrFail($request->input('id', $id));
            $quiz->delete();

            return response()->json([
                'success' => true,
                'message' => 'Quiz deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting quiz',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
