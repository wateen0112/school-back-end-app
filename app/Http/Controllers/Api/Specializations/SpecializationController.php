<?php

namespace App\Http\Controllers\Api\Specializations;

use App\Http\Controllers\Controller;
use App\Models\Specialization;
use Illuminate\Http\Request;

class SpecializationController extends Controller
{
    /**
     * Display a listing of the specializations.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $specializations = Specialization::all();
            
            return response()->json([
                'success' => true,
                'message' => 'Specializations retrieved successfully',
                'data' => $specializations
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving specializations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified specialization.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $specialization = Specialization::find($id);
            
            if (!$specialization) {
                return response()->json([
                    'success' => false,
                    'message' => 'Specialization not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Specialization retrieved successfully',
                'data' => $specialization
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving specialization',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
