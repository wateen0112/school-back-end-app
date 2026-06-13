<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFeesRequest;
use App\Models\Fee;
use App\Models\Grade;
use App\Models\Classroom;
use Illuminate\Http\Request;

class FeesController extends Controller
{
    public function index()
    {
        try {
            $fees = Fee::with(['grade', 'classroom'])->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Fees retrieved successfully',
                'data' => $fees
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving fees',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(StoreFeesRequest $request)
    {
        try {
            $fee = new Fee();
            $fee->title = ['en' => $request->title_en, 'ar' => $request->title_ar];
            $fee->amount = $request->amount;
            $fee->Grade_id = $request->Grade_id;
            $fee->Classroom_id = $request->Classroom_id;
            $fee->description = $request->description;
            $fee->year = $request->year;
            $fee->Fee_type = $request->Fee_type;
            $fee->save();

            return response()->json([
                'success' => true,
                'message' => 'Fee created successfully',
                'data' => $fee->load(['grade', 'classroom'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating fee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $fee = Fee::with(['grade', 'classroom', 'feesInvoices.student'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Fee retrieved successfully',
                'data' => $fee
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fee not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(StoreFeesRequest $request, $id = null)
    {
        try {
            $fee = Fee::findOrFail($request->input('id', $id));
            $fee->title = ['en' => $request->title_en, 'ar' => $request->title_ar];
            $fee->amount = $request->amount;
            $fee->Grade_id = $request->Grade_id;
            $fee->Classroom_id = $request->Classroom_id;
            $fee->description = $request->description;
            $fee->year = $request->year;
            $fee->Fee_type = $request->Fee_type;
            $fee->save();

            return response()->json([
                'success' => true,
                'message' => 'Fee updated successfully',
                'data' => $fee->load(['grade', 'classroom'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating fee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id = null)
    {
        try {
            $fee = Fee::findOrFail($request->input('id', $id));
            $fee->delete();

            return response()->json([
                'success' => true,
                'message' => 'Fee deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting fee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getFeesByGrade($gradeId)
    {
        try {
            $grade = Grade::findOrFail($gradeId);
            $fees = Fee::where('grade_id', $gradeId)
                ->with('classroom')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Fees by grade retrieved successfully',
                'data' => [
                    'grade' => $grade,
                    'fees' => $fees
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving fees by grade',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getFeesByClassroom($classroomId)
    {
        try {
            $classroom = Classroom::findOrFail($classroomId);
            $fees = Fee::where('classroom_id', $classroomId)
                ->with('grade')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Fees by classroom retrieved successfully',
                'data' => [
                    'classroom' => $classroom,
                    'fees' => $fees
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving fees by classroom',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getFeeTypes()
    {
        try {
            $feeTypes = [
                'tuition' => 'Tuition Fee',
                'registration' => 'Registration Fee',
                'transportation' => 'Transportation Fee',
                'activities' => 'Activities Fee',
                'books' => 'Books Fee',
                'uniform' => 'Uniform Fee',
                'other' => 'Other Fee'
            ];

            return response()->json([
                'success' => true,
                'message' => 'Fee types retrieved successfully',
                'data' => $feeTypes
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving fee types',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
