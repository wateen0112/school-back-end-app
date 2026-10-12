<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GenderController extends Controller
{
    public function index()
    {
        try {
            $genders = Cache::remember('lookup:genders', 86400, fn () => Gender::all());
            
            return response()->json([
                'success' => true,
                'message' => 'Genders retrieved successfully',
                'data' => $genders
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving genders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $gender = Gender::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Gender retrieved successfully',
                'data' => $gender
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gender not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}
