<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\online_classe;
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
            $onlineClasses = online_classe::with(['grade', 'classroom', 'section'])->get();
            
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
            'topic' => 'required|string|max:255',
            'meeting_id' => 'required|string|max:255',
            'start_at' => 'required|date',
            'duration' => 'required|integer|min:1',
            'password' => 'nullable|string|max:255',
            'start_url' => 'nullable|url',
            'join_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $onlineClass = new online_classe();
            $onlineClass->integration = false;
            $onlineClass->created_by = 'admin';
            $onlineClass->Grade_id = $request->Grade_id;
            $onlineClass->Classroom_id = $request->Classroom_id;
            $onlineClass->section_id = $request->section_id;
            $onlineClass->topic = $request->topic;
            $onlineClass->meeting_id = $request->meeting_id;
            $onlineClass->start_at = $request->start_at;
            $onlineClass->duration = $request->duration;
            $onlineClass->password = $request->password;
            $onlineClass->start_url = $request->start_url;
            $onlineClass->join_url = $request->join_url;
            $onlineClass->save();

            return response()->json([
                'success' => true,
                'message' => 'Online class created successfully',
                'data' => $onlineClass->load(['grade', 'classroom', 'section'])
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
            $onlineClass = online_classe::with(['grade', 'classroom', 'section'])->findOrFail($id);
            
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
            'topic' => 'required|string|max:255',
            'meeting_id' => 'required|string|max:255',
            'start_at' => 'required|date',
            'duration' => 'required|integer|min:1',
            'password' => 'nullable|string|max:255',
            'start_url' => 'nullable|url',
            'join_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $onlineClass = online_classe::findOrFail($id);
            $onlineClass->Grade_id = $request->Grade_id;
            $onlineClass->Classroom_id = $request->Classroom_id;
            $onlineClass->section_id = $request->section_id;
            $onlineClass->topic = $request->topic;
            $onlineClass->meeting_id = $request->meeting_id;
            $onlineClass->start_at = $request->start_at;
            $onlineClass->duration = $request->duration;
            $onlineClass->password = $request->password;
            $onlineClass->start_url = $request->start_url;
            $onlineClass->join_url = $request->join_url;
            $onlineClass->save();

            return response()->json([
                'success' => true,
                'message' => 'Online class updated successfully',
                'data' => $onlineClass->load(['grade', 'classroom', 'section'])
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
            $onlineClass = online_classe::findOrFail($id);
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
            'topic' => 'required|string|max:255',
            'meeting_id' => 'required|string|max:255',
            'start_at' => 'required|date',
            'duration' => 'required|integer|min:1',
            'password' => 'nullable|string|max:255',
            'start_url' => 'nullable|url',
            'join_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $onlineClass = new online_classe();
            $onlineClass->integration = false;
            $onlineClass->created_by = 'admin';
            $onlineClass->Grade_id = $request->Grade_id;
            $onlineClass->Classroom_id = $request->Classroom_id;
            $onlineClass->section_id = $request->section_id;
            $onlineClass->topic = $request->topic;
            $onlineClass->meeting_id = $request->meeting_id;
            $onlineClass->start_at = $request->start_at;
            $onlineClass->duration = $request->duration;
            $onlineClass->password = $request->password;
            $onlineClass->start_url = $request->start_url;
            $onlineClass->join_url = $request->join_url;
            $onlineClass->save();

            return response()->json([
                'success' => true,
                'message' => 'Online class created indirectly successfully',
                'data' => $onlineClass->load(['grade', 'classroom', 'section'])
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
