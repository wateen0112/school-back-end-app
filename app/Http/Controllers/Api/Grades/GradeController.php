<?php

namespace App\Http\Controllers\Api\Grades;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGrades;
use App\Models\Classroom;
use App\Models\Grade;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function index()
    {
        try {
            $grades = Grade::with('classrooms')->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Grades retrieved successfully',
                'data' => $grades
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving grades',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(StoreGrades $request)
    {
        try {
            $grade = new Grade();
            $grade->Name = ['en' => $request->Name_en, 'ar' => $request->Name];
            $grade->Notes = $request->Notes;
            $grade->save();

            return response()->json([
                'success' => true,
                'message' => 'Grade created successfully',
                'data' => $grade
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating grade',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $grade = Grade::with('classrooms.sections')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Grade retrieved successfully',
                'data' => $grade
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Grade not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(StoreGrades $request, $id = null)
    {
        try {
            $grade = Grade::findOrFail($request->input('id', $id));
            $grade->Name = ['en' => $request->Name_en, 'ar' => $request->Name];
            $grade->Notes = $request->Notes;
            $grade->save();

            return response()->json([
                'success' => true,
                'message' => 'Grade updated successfully',
                'data' => $grade
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating grade',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id = null)
    {
        try {
            $id = $request->input('id', $id);
            $classroomCount = Classroom::where('Grade_id', $id)->count();

            if ($classroomCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete grade. It has associated classrooms.',
                    'classrooms_count' => $classroomCount
                ], 400);
            }

            $grade = Grade::findOrFail($id);
            $grade->delete();

            return response()->json([
                'success' => true,
                'message' => 'Grade deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting grade',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
