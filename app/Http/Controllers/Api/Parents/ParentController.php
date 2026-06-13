<?php

namespace App\Http\Controllers\Api\Parents;

use App\Http\Controllers\Controller;
use App\Models\My_Parent;
use App\Models\Nationalitie;
use App\Models\Religion;
use App\Models\Type_Blood;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ParentController extends Controller
{
    public function index()
    {
        try {
            $parents = My_Parent::with('students')->get()->makeHidden(['password']);
            
            return response()->json([
                'success' => true,
                'message' => 'Parents retrieved successfully',
                'data' => $parents
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving parents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'Father_Name' => 'required|string|max:255',
            'Father_National_ID' => 'required|string|max:255|unique:my__parents,National_ID_Father',
            'email' => 'required|email|unique:my__parents,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            Log::info('Starting parent creation with data: ' . json_encode($request->all()));
            $nationalityId = Nationalitie::query()->value('id');
            $bloodTypeId = Type_Blood::query()->value('id');
            $religionId = Religion::query()->value('id');

            if (! $nationalityId || ! $bloodTypeId || ! $religionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing parent reference data',
                    'errors' => [
                        'nationality' => $nationalityId ? [] : ['No nationalities found.'],
                        'blood_type' => $bloodTypeId ? [] : ['No blood types found.'],
                        'religion' => $religionId ? [] : ['No religions found.'],
                    ],
                ], 422);
            }
            
            // Test minimal parent creation first
            $parent = new My_Parent();
            $parent->Name_Father = $request->Father_Name;
            $parent->National_ID_Father = $request->Father_National_ID;
            $parent->Passport_ID_Father = $request->input('Passport_ID_Father', $request->Father_National_ID);
            $parent->Phone_Father = $request->input('Phone_Father', '0000000000');
            $parent->Job_Father = $request->input('Job_Father', 'Not specified');
            $parent->Nationality_Father_id = $request->input('Nationality_Father_id', $nationalityId);
            $parent->Blood_Type_Father_id = $request->input('Blood_Type_Father_id', $bloodTypeId);
            $parent->Religion_Father_id = $request->input('Religion_Father_id', $religionId);
            $parent->Address_Father = $request->input('Address_Father', 'Not specified');
            $parent->Name_Mother = $request->input('Mother_Name', $request->Father_Name);
            $parent->National_ID_Mother = $request->input('Mother_National_ID', $request->Father_National_ID);
            $parent->Passport_ID_Mother = $request->input('Passport_ID_Mother', $request->Father_National_ID);
            $parent->Phone_Mother = $request->input('Phone_Mother', '0000000000');
            $parent->Job_Mother = $request->input('Job_Mother', 'Not specified');
            $parent->Nationality_Mother_id = $request->input('Nationality_Mother_id', $nationalityId);
            $parent->Blood_Type_Mother_id = $request->input('Blood_Type_Mother_id', $bloodTypeId);
            $parent->Religion_Mother_id = $request->input('Religion_Mother_id', $religionId);
            $parent->Address_Mother = $request->input('Address_Mother', 'Not specified');
            $parent->email = $request->email;
            $parent->password = Hash::make($request->password);
            
            $parent->save();
            Log::info('Parent saved successfully');

            return response()->json([
                'success' => true,
                'message' => 'Parent created successfully',
                'data' => $parent->makeHidden(['password'])
            ], 201);

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error creating parent: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Database error creating parent',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            Log::error('General error creating parent: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating parent',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $parent = My_Parent::with('students.grade', 'students.classroom', 'students.section')->findOrFail($id)->makeHidden(['password']);
            
            return response()->json([
                'success' => true,
                'message' => 'Parent retrieved successfully',
                'data' => $parent
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Parent not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'Father_Name' => 'required|string|max:255',
            'Father_National_ID' => 'required|string|max:255|unique:my__parents,National_ID_Father,' . $id,
            'email' => 'required|email|unique:my__parents,email,' . $id,
            'password' => 'sometimes|required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $parent = My_Parent::findOrFail($id);

            $parent->Name_Father = $request->Father_Name;
            $parent->National_ID_Father = $request->Father_National_ID;
            $parent->email = $request->email;

            if ($request->filled('password')) {
                $parent->password = Hash::make($request->password);
            }
            
            $parent->save();

            return response()->json([
                'success' => true,
                'message' => 'Parent updated successfully',
                'data' => $parent->load('students')->makeHidden(['password'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating parent',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $parent = My_Parent::findOrFail($id);
            $parent->delete();

            return response()->json([
                'success' => true,
                'message' => 'Parent deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting parent',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
