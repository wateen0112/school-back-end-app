<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\Fee_invoice;
use App\Models\Student;
use App\Models\Fee;
use App\Models\StudentAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class FeesInvoicesController extends Controller
{
    public function index()
    {
        try {
            $feesInvoices = Fee_invoice::with(['student', 'fees', 'grade', 'classroom'])->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Fees invoices retrieved successfully',
                'data' => $feesInvoices
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving fees invoices',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'Grade_id' => 'required',
            'Classroom_id' => 'required',
            'List_Fees' => 'required|array',
            'List_Fees.*.student_id' => 'required|exists:students,id',
            'List_Fees.*.fee_id' => 'required|exists:fees,id',
            'List_Fees.*.amount' => 'required|numeric',
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
            $created = [];
            foreach ($request->List_Fees as $item) {
                $feesInvoice = new Fee_invoice();
                $feesInvoice->invoice_date = date('Y-m-d');
                $feesInvoice->student_id = $item['student_id'];
                $feesInvoice->Grade_id = $request->Grade_id;
                $feesInvoice->Classroom_id = $request->Classroom_id;
                $feesInvoice->fee_id = $item['fee_id'];
                $feesInvoice->amount = $item['amount'];
                $feesInvoice->description = $item['description'] ?? null;
                $feesInvoice->save();

                $studentAccount = new StudentAccount();
                $studentAccount->date = date('Y-m-d');
                $studentAccount->type = 'invoice';
                $studentAccount->fee_invoice_id = $feesInvoice->id;
                $studentAccount->student_id = $item['student_id'];
                $studentAccount->Debit = $item['amount'];
                $studentAccount->credit = 0.00;
                $studentAccount->description = $item['description'] ?? null;
                $studentAccount->save();

                $created[] = $feesInvoice->load(['student', 'fees', 'grade', 'classroom']);
            }
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Fees invoices created successfully',
                'data' => $created
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating fees invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $feesInvoice = Fee_invoice::with(['student', 'fees', 'grade', 'classroom'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Fees invoice retrieved successfully',
                'data' => $feesInvoice
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fees invoice not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id = null)
    {
        $validator = Validator::make($request->all(), [
            'fee_id' => 'required|exists:fees,id',
            'amount' => 'required|numeric',
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
            $feesInvoice = Fee_invoice::findOrFail($request->input('id', $id));
            $feesInvoice->fee_id = $request->fee_id;
            $feesInvoice->amount = $request->amount;
            $feesInvoice->description = $request->description;
            $feesInvoice->save();

            $studentAccount = StudentAccount::where('fee_invoice_id', $feesInvoice->id)->first();
            if ($studentAccount) {
                $studentAccount->Debit = $request->amount;
                $studentAccount->description = $request->description;
                $studentAccount->save();
            }
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Fees invoice updated successfully',
                'data' => $feesInvoice->load(['student', 'fees', 'grade', 'classroom'])
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating fees invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id = null)
    {
        try {
            $feesInvoice = Fee_invoice::findOrFail($request->input('id', $id));
            $feesInvoice->delete();

            return response()->json([
                'success' => true,
                'message' => 'Fees invoice deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting fees invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStudentInvoices($studentId)
    {
        try {
            $student = Student::findOrFail($studentId);
            $invoices = Fee_invoice::where('student_id', $studentId)
                ->with(['fees', 'grade', 'classroom'])
                ->orderBy('invoice_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Student invoices retrieved successfully',
                'data' => [
                    'student' => $student,
                    'invoices' => $invoices
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving student invoices',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function markAsPaid($id)
    {
        try {
            return response()->json([
                'success' => false,
                'message' => 'This workflow is handled by receipt_students in the Blade app.'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error marking fees invoice as paid',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function generateInvoiceNumber()
    {
        $latestInvoice = Fee_invoice::orderBy('id', 'desc')->first();
        $nextNumber = $latestInvoice ? $latestInvoice->id + 1 : 1;
        return 'INV-' . date('Y') . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
