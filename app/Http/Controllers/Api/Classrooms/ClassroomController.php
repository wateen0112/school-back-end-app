<?php

namespace App\Http\Controllers\Api\Classrooms;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClassroom;
use App\Models\Classroom;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClassroomController extends Controller
{
    public function index()
    {
        try {
            $classrooms = Classroom::with('grade')->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Classrooms retrieved successfully',
                'data' => $classrooms
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving classrooms',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(StoreClassroom $request)
    {
        try {
            $classrooms = collect($request->List_Classes)->map(function ($item) {
                $classroom = new Classroom();
                $classroom->Name_Class = ['en' => $item['Name_class_en'], 'ar' => $item['Name']];
                $classroom->Grade_id = $item['Grade_id'];
                $classroom->save();

                return $classroom->load('grade');
            });

            return response()->json([
                'success' => true,
                'message' => 'Classrooms created successfully',
                'data' => $classrooms
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating classroom',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $classroom = Classroom::with(['grade', 'sections'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Classroom retrieved successfully',
                'data' => $classroom
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Classroom not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id = null)
    {
        $validator = Validator::make($request->all(), [
            'Name' => 'required|string|max:255',
            'Name_en' => 'required|string|max:255',
            'Grade_id' => 'required|exists:grades,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $classroom = Classroom::findOrFail($request->input('id', $id));
            $classroom->Name_Class = ['en' => $request->Name_en, 'ar' => $request->Name];
            $classroom->Grade_id = $request->Grade_id;
            $classroom->save();

            return response()->json([
                'success' => true,
                'message' => 'Classroom updated successfully',
                'data' => $classroom->load('grade')
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating classroom',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id = null)
    {
        try {
            $classroom = Classroom::findOrFail($request->input('id', $id));
            $classroom->delete();

            return response()->json([
                'success' => true,
                'message' => 'Classroom deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting classroom',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function delete_all(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'delete_all_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $ids = array_filter(explode(',', $request->delete_all_id));
            $deletedCount = Classroom::whereIn('id', $ids)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Classrooms deleted successfully',
                'deleted_count' => $deletedCount
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting classrooms',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function Filter_Classes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'Grade_id' => 'required|exists:grades,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $classrooms = Classroom::where('Grade_id', $request->Grade_id)
                ->with('grade')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Filtered classrooms retrieved successfully',
                'data' => $classrooms
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error filtering classrooms',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
