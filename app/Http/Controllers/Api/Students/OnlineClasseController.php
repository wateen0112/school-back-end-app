<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\OnlineClasse;
use App\Models\Grade;
use App\Models\Classroom;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OnlineClasseController extends Controller
{
    public function index()
    {
        try {
            $onlineClasses = OnlineClasse::with(['grade', 'classroom', 'section', 'subject', 'teacher'])->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Online classes retrieved successfully',
                'data' => $onlineClasses
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving online classes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'Grade_id' => 'required|exists:grades,id',
            'Classroom_id' => 'required|exists:classrooms,id',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'meeting_link' => 'required|url',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'topic' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $onlineClass = new OnlineClasse();
            $onlineClass->Grade_id = $request->Grade_id;
            $onlineClass->Classroom_id = $request->Classroom_id;
            $onlineClass->section_id = $request->section_id;
            $onlineClass->subject_id = $request->subject_id;
            $onlineClass->teacher_id = $request->teacher_id;
            $onlineClass->meeting_link = $request->meeting_link;
            $onlineClass->start_time = $request->start_time;
            $onlineClass->end_time = $request->end_time;
            $onlineClass->topic = ['en' => $request->topic, 'ar' => $request->topic];
            $onlineClass->description = ['en' => $request->description, 'ar' => $request->description];
            $onlineClass->save();

            return response()->json([
                'success' => true,
                'message' => 'Online class created successfully',
                'data' => $onlineClass->load(['grade', 'classroom', 'section', 'subject', 'teacher'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating online class',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $onlineClass = OnlineClasse::with(['grade', 'classroom', 'section', 'subject', 'teacher'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Online class retrieved successfully',
                'data' => $onlineClass
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Online class not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'Grade_id' => 'required|exists:grades,id',
            'Classroom_id' => 'required|exists:classrooms,id',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'meeting_link' => 'required|url',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'topic' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $onlineClass = OnlineClasse::findOrFail($id);
            $onlineClass->Grade_id = $request->Grade_id;
            $onlineClass->Classroom_id = $request->Classroom_id;
            $onlineClass->section_id = $request->section_id;
            $onlineClass->subject_id = $request->subject_id;
            $onlineClass->teacher_id = $request->teacher_id;
            $onlineClass->meeting_link = $request->meeting_link;
            $onlineClass->start_time = $request->start_time;
            $onlineClass->end_time = $request->end_time;
            $onlineClass->topic = ['en' => $request->topic, 'ar' => $request->topic];
            $onlineClass->description = ['en' => $request->description, 'ar' => $request->description];
            $onlineClass->save();

            return response()->json([
                'success' => true,
                'message' => 'Online class updated successfully',
                'data' => $onlineClass->load(['grade', 'classroom', 'section', 'subject', 'teacher'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating online class',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $onlineClass = OnlineClasse::findOrFail($id);
            $onlineClass->delete();

            return response()->json([
                'success' => true,
                'message' => 'Online class deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting online class',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function indirectCreate()
    {
        try {
            $grades = Grade::all();
            $subjects = Subject::all();
            
            return response()->json([
                'success' => true,
                'message' => 'Data for indirect creation retrieved successfully',
                'data' => [
                    'grades' => $grades,
                    'subjects' => $subjects
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storeIndirect(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'Grade_id' => 'required|exists:grades,id',
            'Classroom_id' => 'required|exists:classrooms,id',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'meeting_link' => 'required|url',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'topic' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $onlineClass = new OnlineClasse();
            $onlineClass->Grade_id = $request->Grade_id;
            $onlineClass->Classroom_id = $request->Classroom_id;
            $onlineClass->section_id = $request->section_id;
            $onlineClass->subject_id = $request->subject_id;
            $onlineClass->teacher_id = $request->teacher_id;
            $onlineClass->meeting_link = $request->meeting_link;
            $onlineClass->start_time = $request->start_time;
            $onlineClass->end_time = $request->end_time;
            $onlineClass->topic = ['en' => $request->topic, 'ar' => $request->topic];
            $onlineClass->description = ['en' => $request->description, 'ar' => $request->description];
            $onlineClass->save();

            return response()->json([
                'success' => true,
                'message' => 'Online class created indirectly successfully',
                'data' => $onlineClass->load(['grade', 'classroom', 'section', 'subject', 'teacher'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating online class indirectly',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
