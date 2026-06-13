<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Nationalitie;
use Illuminate\Http\Request;

class NationalityController extends Controller
{
    public function index()
    {
        try {
            $nationalities = Nationalitie::all();
            
            return response()->json([
                'success' => true,
                'message' => 'Nationalities retrieved successfully',
                'data' => $nationalities
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving nationalities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $nationality = Nationalitie::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Nationality retrieved successfully',
                'data' => $nationality
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Nationality not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}
