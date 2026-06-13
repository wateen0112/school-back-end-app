<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Type_Blood;
use Illuminate\Http\Request;

class BloodTypeController extends Controller
{
    public function index()
    {
        try {
            $bloodTypes = Type_Blood::all();
            
            return response()->json([
                'success' => true,
                'message' => 'Blood types retrieved successfully',
                'data' => $bloodTypes
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving blood types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $bloodType = Type_Blood::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Blood type retrieved successfully',
                'data' => $bloodType
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Blood type not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}
