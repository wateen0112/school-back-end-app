<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GraduatedController extends Controller
{
    public function index()
    {
        try {
            $graduatedStudents = Student::onlyTrashed()->with(['grade', 'classroom', 'section', 'myparent'])->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Graduated students retrieved successfully',
                'data' => $graduatedStudents
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving graduated students',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'Grade_id' => 'required',
            'Classroom_id' => 'required',
            'section_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $students = Student::where('Grade_id', $request->Grade_id)
                ->where('Classroom_id', $request->Classroom_id)
                ->where('section_id', $request->section_id)
                ->get();

            if ($students->count() < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'No students found for graduation.'
                ], 404);
            }

            foreach ($students as $student) {
                $student->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Students graduated successfully',
                'data' => [
                    'graduated_count' => $students->count(),
                    'students' => $students
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error graduating students',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $graduatedStudent = Student::onlyTrashed()->with(['grade', 'classroom', 'section', 'myparent'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Graduated student retrieved successfully',
                'data' => $graduatedStudent
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Graduated student not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id = null)
    {
        try {
            $graduatedStudent = Student::onlyTrashed()->where('id', $request->input('id', $id))->firstOrFail();
            $graduatedStudent->restore();

            return response()->json([
                'success' => true,
                'message' => 'Graduated student restored successfully',
                'data' => $graduatedStudent->load(['grade', 'classroom', 'section', 'myparent'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating graduated student',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id = null)
    {
        try {
            $graduatedStudent = Student::onlyTrashed()->where('id', $request->input('id', $id))->firstOrFail();
            $graduatedStudent->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'Graduated student deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting graduated student',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function restore($id)
    {
        try {
            $student = Student::withTrashed()->findOrFail($id);
            $student->restore();
            
            if ($student->status === 'graduated') {
                $student->status = 'active';
                $student->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Student restored successfully',
                'data' => $student->load(['grade', 'classroom', 'section', 'parent'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error restoring student',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
