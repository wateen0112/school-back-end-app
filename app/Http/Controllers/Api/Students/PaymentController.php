<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\FundAccount;
use App\Models\PaymentStudent;
use App\Models\Student;
use App\Models\StudentAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index()
    {
        try {
            $payments = PaymentStudent::with('student')->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Payments retrieved successfully',
                'data' => $payments
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving payments',
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
            $payment = new PaymentStudent();
            $payment->date = date('Y-m-d');
            $payment->student_id = $request->student_id;
            $payment->amount = $request->Debit;
            $payment->description = $request->description;
            $payment->save();

            $fundAccount = new FundAccount();
            $fundAccount->date = date('Y-m-d');
            $fundAccount->payment_id = $payment->id;
            $fundAccount->Debit = 0.00;
            $fundAccount->credit = $request->Debit;
            $fundAccount->description = $request->description;
            $fundAccount->save();

            $studentAccount = new StudentAccount();
            $studentAccount->date = date('Y-m-d');
            $studentAccount->type = 'payment';
            $studentAccount->student_id = $request->student_id;
            $studentAccount->payment_id = $payment->id;
            $studentAccount->Debit = $request->Debit;
            $studentAccount->credit = 0.00;
            $studentAccount->description = $request->description;
            $studentAccount->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment created successfully',
                'data' => $payment->load('student')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $payment = PaymentStudent::with('student')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Payment retrieved successfully',
                'data' => $payment
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
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
            $payment = PaymentStudent::findOrFail($request->input('id', $id));
            $payment->date = date('Y-m-d');
            $payment->student_id = $request->student_id;
            $payment->amount = $request->Debit;
            $payment->description = $request->description;
            $payment->save();

            $fundAccount = FundAccount::where('payment_id', $payment->id)->first();
            if ($fundAccount) {
                $fundAccount->date = date('Y-m-d');
                $fundAccount->Debit = 0.00;
                $fundAccount->credit = $request->Debit;
                $fundAccount->description = $request->description;
                $fundAccount->save();
            }

            $studentAccount = StudentAccount::where('payment_id', $payment->id)->first();
            if ($studentAccount) {
                $studentAccount->date = date('Y-m-d');
                $studentAccount->type = 'payment';
                $studentAccount->student_id = $request->student_id;
                $studentAccount->Debit = $request->Debit;
                $studentAccount->credit = 0.00;
                $studentAccount->description = $request->description;
                $studentAccount->save();
            }
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment updated successfully',
                'data' => $payment->load('student')
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id = null)
    {
        try {
            $payment = PaymentStudent::findOrFail($request->input('id', $id));
            $payment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Payment deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStudentPayments($studentId)
    {
        try {
            $student = Student::findOrFail($studentId);
            $payments = PaymentStudent::where('student_id', $studentId)
                ->orderBy('date', 'desc')
                ->get();

            $totalPaid = $payments->sum('amount');
            $paymentsByType = collect();

            return response()->json([
                'success' => true,
                'message' => 'Student payments retrieved successfully',
                'data' => [
                    'student' => $student,
                    'payments' => $payments,
                    'total_paid' => $totalPaid,
                    'payments_by_type' => $paymentsByType
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving student payments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPaymentStatistics()
    {
        try {
            $totalPayments = PaymentStudent::sum('amount');
            $paymentsByMethod = collect();
            $paymentsByType = collect();

            $recentPayments = PaymentStudent::with('student')
                ->orderBy('date', 'desc')
                ->take(10)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Payment statistics retrieved successfully',
                'data' => [
                    'total_payments' => $totalPayments,
                    'payments_by_method' => $paymentsByMethod,
                    'payments_by_type' => $paymentsByType,
                    'recent_payments' => $recentPayments
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving payment statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function generatePaymentNumber()
    {
        $latestPayment = PaymentStudent::orderBy('id', 'desc')->first();
        $nextNumber = $latestPayment ? $latestPayment->id + 1 : 1;
        return 'PAY-' . date('Y') . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    private function updateRelatedEntityStatus($payment)
    {
        try {
            return;
        } catch (\Exception $e) {
            // Log error but don't fail the main operation
            \Log::error('Error updating related entity status: ' . $e->getMessage());
        }
    }
}
