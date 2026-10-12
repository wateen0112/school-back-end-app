<?php

namespace App\Http\Controllers\Api\Specializations;

use App\Http\Controllers\Controller;
use App\Models\Specialization;
use App\Models\Teacher;
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

    /**
     * Store a newly created specialization.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'Name' => 'required|string|max:255',
            ]);

            $specialization = new Specialization();
            $specialization->Name = ['en' => $request->Name_en ?: $request->Name, 'ar' => $request->Name];
            $specialization->save();

            return response()->json([
                'success' => true,
                'message' => 'Specialization created successfully',
                'data' => $specialization
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating specialization',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified specialization.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id = null)
    {
        try {
            $id = $request->input('id', $id);
            $specialization = Specialization::findOrFail($id);

            try {
                $existing = $specialization->getTranslations('Name');
            } catch (\Throwable $e) {
                $existing = [];
            }

            $nameAr = $request->Name ?: ($existing['ar'] ?? null);
            $nameEn = $request->Name_en ?: ($existing['en'] ?? $nameAr);
            $specialization->Name = ['en' => $nameEn, 'ar' => $nameAr];
            $specialization->save();

            return response()->json([
                'success' => true,
                'message' => 'Specialization updated successfully',
                'data' => $specialization
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating specialization',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified specialization.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id = null)
    {
        try {
            $id = $request->input('id', $id);
            $teacherCount = Teacher::where('Specialization_id', $id)->count();

            if ($teacherCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete specialization. It has associated teachers.',
                    'teachers_count' => $teacherCount
                ], 400);
            }

            $specialization = Specialization::findOrFail($id);
            $specialization->delete();

            return response()->json([
                'success' => true,
                'message' => 'Specialization deleted successfully'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting specialization',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
