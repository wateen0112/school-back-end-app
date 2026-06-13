<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\FundAccount;
use App\Models\ReceiptStudent;
use App\Models\Student;
use App\Models\Fee_invoice;
use App\Models\StudentAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ReceiptStudentsController extends Controller
{
    public function index()
    {
        try {
            $receipts = ReceiptStudent::with('student')->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Student receipts retrieved successfully',
                'data' => $receipts
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving student receipts',
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
            $receipt = new ReceiptStudent();
            $receipt->date = date('Y-m-d');
            $receipt->student_id = $request->student_id;
            $receipt->Debit = $request->Debit;
            $receipt->description = $request->description;
            $receipt->save();

            $fundAccount = new FundAccount();
            $fundAccount->date = date('Y-m-d');
            $fundAccount->receipt_id = $receipt->id;
            $fundAccount->Debit = $request->Debit;
            $fundAccount->credit = 0.00;
            $fundAccount->description = $request->description;
            $fundAccount->save();

            $studentAccount = new StudentAccount();
            $studentAccount->date = date('Y-m-d');
            $studentAccount->type = 'receipt';
            $studentAccount->receipt_id = $receipt->id;
            $studentAccount->student_id = $request->student_id;
            $studentAccount->Debit = 0.00;
            $studentAccount->credit = $request->Debit;
            $studentAccount->description = $request->description;
            $studentAccount->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Student receipt created successfully',
                'data' => $receipt->load('student')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating student receipt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $receipt = ReceiptStudent::with('student')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Student receipt retrieved successfully',
                'data' => $receipt
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Student receipt not found',
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
            $receipt = ReceiptStudent::findOrFail($request->input('id', $id));
            $receipt->date = date('Y-m-d');
            $receipt->student_id = $request->student_id;
            $receipt->Debit = $request->Debit;
            $receipt->description = $request->description;
            $receipt->save();

            $fundAccount = FundAccount::where('receipt_id', $receipt->id)->first();
            if ($fundAccount) {
                $fundAccount->date = date('Y-m-d');
                $fundAccount->Debit = $request->Debit;
                $fundAccount->credit = 0.00;
                $fundAccount->description = $request->description;
                $fundAccount->save();
            }

            $studentAccount = StudentAccount::where('receipt_id', $receipt->id)->first();
            if ($studentAccount) {
                $studentAccount->date = date('Y-m-d');
                $studentAccount->type = 'receipt';
                $studentAccount->student_id = $request->student_id;
                $studentAccount->Debit = 0.00;
                $studentAccount->credit = $request->Debit;
                $studentAccount->description = $request->description;
                $studentAccount->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Student receipt updated successfully',
                'data' => $receipt->load('student')
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating student receipt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id = null)
    {
        try {
            $receipt = ReceiptStudent::findOrFail($request->input('id', $id));
            $receipt->delete();

            return response()->json([
                'success' => true,
                'message' => 'Student receipt deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting student receipt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStudentReceipts($studentId)
    {
        try {
            $student = Student::findOrFail($studentId);
            $receipts = ReceiptStudent::where('student_id', $studentId)
                ->orderBy('date', 'desc')
                ->get();

            $totalPaid = $receipts->sum('Debit');

            return response()->json([
                'success' => true,
                'message' => 'Student receipts retrieved successfully',
                'data' => [
                    'student' => $student,
                    'receipts' => $receipts,
                    'total_paid' => $totalPaid
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving student receipts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getInvoiceReceipts($invoiceId)
    {
        try {
            $feesInvoice = Fee_invoice::findOrFail($invoiceId);
            $receipts = ReceiptStudent::with('student')->orderBy('date', 'desc')->get();
            $totalPaid = $receipts->sum('Debit');
            $remainingAmount = $feesInvoice->amount - $totalPaid;

            return response()->json([
                'success' => true,
                'message' => 'Invoice receipts retrieved successfully',
                'data' => [
                    'fees_invoice' => $feesInvoice,
                    'receipts' => $receipts,
                    'total_paid' => $totalPaid,
                    'remaining_amount' => $remainingAmount
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving invoice receipts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function generateReceiptNumber()
    {
        $latestReceipt = ReceiptStudent::orderBy('id', 'desc')->first();
        $nextNumber = $latestReceipt ? $latestReceipt->id + 1 : 1;
        return 'REC-' . date('Y') . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    private function updateInvoiceStatus($feesInvoiceId)
    {
        try {
            return;
        } catch (\Exception $e) {
            // Log error but don't fail the main operation
            \Log::error('Error updating invoice status: ' . $e->getMessage());
        }
    }
}
