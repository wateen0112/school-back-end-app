<?php

namespace App\Http\Controllers\Api\Teachers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeachers;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TeacherController extends Controller
{
    public function index()
    {
        try {
            $teachers = Teacher::with(['specializations', 'genders'])->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Teachers retrieved successfully',
                'data' => $teachers
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving teachers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(StoreTeachers $request)
    {
        try {
            $teacher = new Teacher();
            $teacher->Name = ['en' => $request->Name_en, 'ar' => $request->Name_ar];
            $teacher->email = $request->Email;
            $teacher->password = Hash::make($request->Password);
            $teacher->Specialization_id = $request->Specialization_id;
            $teacher->Gender_id = $request->Gender_id;
            $teacher->Joining_Date = $request->Joining_Date;
            $teacher->Address = $request->Address;
            $teacher->save();

            return response()->json([
                'success' => true,
                'message' => 'Teacher created successfully',
                'data' => $teacher->load(['specializations', 'genders'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $teacher = Teacher::with(['specializations', 'genders'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Teacher retrieved successfully',
                'data' => $teacher
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(StoreTeachers $request, $id = null)
    {
        try {
            $teacher = Teacher::findOrFail($request->input('id', $id));
            $teacher->Name = ['en' => $request->Name_en, 'ar' => $request->Name_ar];
            $teacher->email = $request->Email;
            $teacher->Specialization_id = $request->Specialization_id;
            $teacher->Gender_id = $request->Gender_id;
            $teacher->Joining_Date = $request->Joining_Date;
            $teacher->Address = $request->Address;
            $teacher->password = Hash::make($request->Password);
            $teacher->save();

            return response()->json([
                'success' => true,
                'message' => 'Teacher updated successfully',
                'data' => $teacher->load(['specializations', 'genders'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating teacher',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id = null)
    {
        try {
            $teacher = Teacher::findOrFail($request->input('id', $id));
            $teacher->delete();

            return response()->json([
                'success' => true,
                'message' => 'Teacher deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting teacher',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
