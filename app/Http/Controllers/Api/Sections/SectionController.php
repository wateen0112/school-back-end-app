<?php

namespace App\Http\Controllers\Api\Sections;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSections;
use App\Models\Section;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SectionController extends Controller
{
    private $relations = ['Grades', 'My_classs.grade', 'teachers'];

    public function index()
    {
        try {
            $sections = Section::with($this->relations)->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Sections retrieved successfully',
                'data' => $sections
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving sections',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(StoreSections $request)
    {
        try {
            $section = new Section();
            $section->Name_Section = ['en' => $request->Name_Section_En, 'ar' => $request->Name_Section_Ar];
            $section->Status = 1;
            $section->Grade_id = $request->Grade_id;
            $section->Class_id = $request->Class_id;
            $section->save();

            if ($request->has('teacher_id')) {
                $section->teachers()->attach($request->teacher_id);
            }

            return response()->json([
                'success' => true,
                'message' => 'Section created successfully',
                'data' => $section->load($this->relations)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating section',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $section = Section::with($this->relations)->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Section retrieved successfully',
                'data' => $section
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Section not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(StoreSections $request, $id = null)
    {
        try {
            $section = Section::findOrFail($request->input('id', $id));
            $section->Name_Section = ['en' => $request->Name_Section_En, 'ar' => $request->Name_Section_Ar];
            $section->Status = $request->has('Status') ? 1 : 2;
            $section->Grade_id = $request->Grade_id;
            $section->Class_id = $request->Class_id;
            $section->save();

            $section->teachers()->sync($request->input('teacher_id', []));

            return response()->json([
                'success' => true,
                'message' => 'Section updated successfully',
                'data' => $section->load($this->relations)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating section',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id = null)
    {
        try {
            $section = Section::findOrFail($request->input('id', $id));
            $section->teachers()->detach();
            $section->delete();

            return response()->json([
                'success' => true,
                'message' => 'Section deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting section',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getclasses($id)
    {
        try {
            $classrooms = Classroom::where('Grade_id', $id)->pluck('Name_Class', 'id');
            
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
}
