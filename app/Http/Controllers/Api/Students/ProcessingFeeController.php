<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\ProcessingFee;
use App\Models\Student;
use App\Models\StudentAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProcessingFeeController extends Controller
{
    public function index()
    {
        try {
            $processingFees = ProcessingFee::with('student')->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Processing fees retrieved successfully',
                'data' => $processingFees
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving processing fees',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'Debit' => 'required|numeric',
            'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $processingFee = new ProcessingFee();
            $processingFee->date = date('Y-m-d');
            $processingFee->student_id = $request->student_id;
            $processingFee->amount = $request->Debit;
            $processingFee->description = $request->description;
            $processingFee->save();

            $studentAccount = new StudentAccount();
            $studentAccount->date = date('Y-m-d');
            $studentAccount->type = 'ProcessingFee';
            $studentAccount->student_id = $request->student_id;
            $studentAccount->processing_id = $processingFee->id;
            $studentAccount->Debit = 0.00;
            $studentAccount->credit = $request->Debit;
            $studentAccount->description = $request->description;
            $studentAccount->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Processing fee created successfully',
                'data' => $processingFee->load('student')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating processing fee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $processingFee = ProcessingFee::with('student')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Processing fee retrieved successfully',
                'data' => $processingFee
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Processing fee not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id = null)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'Debit' => 'required|numeric',
            'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $processingFee = ProcessingFee::findOrFail($request->input('id', $id));
            $processingFee->date = date('Y-m-d');
            $processingFee->student_id = $request->student_id;
            $processingFee->amount = $request->Debit;
            $processingFee->description = $request->description;
            $processingFee->save();

            $studentAccount = StudentAccount::where('processing_id', $processingFee->id)->first();
            if ($studentAccount) {
                $studentAccount->date = date('Y-m-d');
                $studentAccount->type = 'ProcessingFee';
                $studentAccount->student_id = $request->student_id;
                $studentAccount->Debit = 0.00;
                $studentAccount->credit = $request->Debit;
                $studentAccount->description = $request->description;
                $studentAccount->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Processing fee updated successfully',
                'data' => $processingFee->load('student')
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating processing fee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id = null)
    {
        try {
            $processingFee = ProcessingFee::findOrFail($request->input('id', $id));
            $processingFee->delete();

            return response()->json([
                'success' => true,
                'message' => 'Processing fee deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting processing fee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStudentProcessingFees($studentId)
    {
        try {
            $student = Student::findOrFail($studentId);
            $processingFees = ProcessingFee::where('student_id', $studentId)
                ->orderBy('date', 'desc')
                ->get();

            $totalAmount = $processingFees->sum('amount');

            return response()->json([
                'success' => true,
                'message' => 'Student processing fees retrieved successfully',
                'data' => [
                    'student' => $student,
                    'processing_fees' => $processingFees,
                    'total_amount' => $totalAmount
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving student processing fees',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function markAsPaid($id)
    {
        try {
            $processingFee = ProcessingFee::findOrFail($id);
            return response()->json([
                'success' => false,
                'message' => 'This workflow is handled by Payment_students in the Blade app.'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error marking processing fee as paid',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getFeeTypes()
    {
        try {
            $feeTypes = [
                'registration' => 'Registration Fee',
                'late_payment' => 'Late Payment Fee',
                'transfer' => 'Transfer Fee',
                'certificate' => 'Certificate Fee',
                'transcript' => 'Transcript Fee',
                'other' => 'Other Fee'
            ];

            return response()->json([
                'success' => true,
                'message' => 'Processing fee types retrieved successfully',
                'data' => $feeTypes
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving processing fee types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function generateFeeNumber()
    {
        $latestFee = ProcessingFee::orderBy('id', 'desc')->first();
        $nextNumber = $latestFee ? $latestFee->id + 1 : 1;
        return 'PF-' . date('Y') . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
