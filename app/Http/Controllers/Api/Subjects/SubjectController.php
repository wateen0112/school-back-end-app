<?php

namespace App\Http\Controllers\Api\Subjects;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubjectController extends Controller
{
    public function index()
    {
        try {
            $subjects = Subject::with(['grade', 'teacher'])->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Subjects retrieved successfully',
                'data' => $subjects
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving subjects',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'Name_ar' => 'required|string|max:255',
            'Name_en' => 'required|string|max:255',
            'Grade_id' => 'required',
            'Class_id' => 'required',
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
            $subject = new Subject();
            $subject->Name = ['en' => $request->Name_en, 'ar' => $request->Name_ar];
            $subject->Grade_id = $request->Grade_id;
            $subject->Classroom_id = $request->Class_id;
            $subject->teacher_id = $request->teacher_id;
            $subject->save();

            return response()->json([
                'success' => true,
                'message' => 'Subject created successfully',
                'data' => $subject->load(['grade', 'teacher'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating subject',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $subject = Subject::with(['grade', 'teacher', 'classroom'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Subject retrieved successfully',
                'data' => $subject
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Subject not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id = null)
    {
        $validator = Validator::make($request->all(), [
            'Name_ar' => 'required|string|max:255',
            'Name_en' => 'required|string|max:255',
            'Grade_id' => 'required',
            'Class_id' => 'required',
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
            $subject = Subject::findOrFail($request->input('id', $id));
            $subject->Name = ['en' => $request->Name_en, 'ar' => $request->Name_ar];
            $subject->Grade_id = $request->Grade_id;
            $subject->Classroom_id = $request->Class_id;
            $subject->teacher_id = $request->teacher_id;
            $subject->save();

            return response()->json([
                'success' => true,
                'message' => 'Subject updated successfully',
                'data' => $subject->load(['grade', 'teacher'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating subject',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id = null)
    {
        try {
            $subject = Subject::findOrFail($request->input('id', $id));
            $subject->delete();

            return response()->json([
                'success' => true,
                'message' => 'Subject deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting subject',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
