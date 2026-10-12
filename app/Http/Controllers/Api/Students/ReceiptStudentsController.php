<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\FundAccount;
use App\Models\ReceiptStudent;
use App\Models\Student;
use App\Models\Fee_invoice;
use App\Models\StudentAccount;
use App\Models\StudentInstallment;
use App\Repository\StudentInstallmentRepositoryInterface;
use App\Support\CacheVersion;
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
            'debit' => 'required_without:Debit|numeric',
            'Debit' => 'required_without:debit|numeric',
            'description' => 'required|string',
            'student_installment_id' => 'nullable|exists:student_installments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $receipt = $this->createReceipt($request);
            $installment = $this->resolveInstallment($receipt);

            return response()->json([
                'success' => true,
                'message' => 'Receipt created and installment updated successfully',
                'data' => [
                    'receipt' => [
                        'id' => $receipt->id,
                        'date' => $receipt->date,
                        'debit' => $receipt->Debit,
                    ],
                    'installment' => $installment ? [
                        'id' => $installment->id,
                        'paid_amount' => $installment->paid_amount,
                        'remaining_amount' => $installment->remaining_amount,
                        'status' => $installment->status,
                    ] : null,
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
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
            'debit' => 'required_without:Debit|numeric',
            'Debit' => 'required_without:debit|numeric',
            'description' => 'required|string',
            'student_installment_id' => 'nullable|exists:student_installments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $receipt = $this->updateReceipt($request, $id);
            $installment = $this->resolveInstallment($receipt);

            return response()->json([
                'success' => true,
                'message' => 'Receipt updated and installment updated successfully',
                'data' => [
                    'receipt' => [
                        'id' => $receipt->id,
                        'date' => $receipt->date,
                        'debit' => $receipt->Debit,
                    ],
                    'installment' => $installment ? [
                        'id' => $installment->id,
                        'paid_amount' => $installment->paid_amount,
                        'remaining_amount' => $installment->remaining_amount,
                        'status' => $installment->status,
                    ] : null,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, $id = null)
    {
        try {
            $receipt = ReceiptStudent::findOrFail($request->input('id', $id));
            $receipt->delete();
            CacheVersion::bump('accounting');

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

    private function createReceipt(Request $request)
    {
        $amount = $request->has('Debit') ? $request->Debit : $request->debit;
        $studentInstallmentId = $request->input('student_installment_id');

        $this->validateInstallment($studentInstallmentId, $request->student_id);

        DB::beginTransaction();
        try {
            $receipt = new ReceiptStudent();
            $receipt->date = date('Y-m-d');
            $receipt->student_id = $request->student_id;
            $receipt->student_installment_id = $studentInstallmentId;
            $receipt->Debit = $amount;
            $receipt->description = $request->description;
            $receipt->save();

            $fundAccount = new FundAccount();
            $fundAccount->date = date('Y-m-d');
            $fundAccount->receipt_id = $receipt->id;
            $fundAccount->Debit = $amount;
            $fundAccount->credit = 0.00;
            $fundAccount->description = $request->description;
            $fundAccount->save();

            $studentAccount = new StudentAccount();
            $studentAccount->date = date('Y-m-d');
            $studentAccount->type = 'receipt';
            $studentAccount->receipt_id = $receipt->id;
            $studentAccount->student_installment_id = $studentInstallmentId;
            $studentAccount->student_id = $request->student_id;
            $studentAccount->Debit = 0.00;
            $studentAccount->credit = $amount;
            $studentAccount->description = $request->description;
            $studentAccount->save();

            if ($studentInstallmentId) {
                $studentInstallment = StudentInstallment::find($studentInstallmentId);
                if ($studentInstallment) {
                    app(StudentInstallmentRepositoryInterface::class)->updatePaymentStatus($studentInstallment);
                }
            }

            DB::commit();
            CacheVersion::bump('accounting');

            return $receipt;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function updateReceipt(Request $request, $id = null)
    {
        $amount = $request->has('Debit') ? $request->Debit : $request->debit;
        $studentInstallmentId = $request->input('student_installment_id');

        DB::beginTransaction();
        try {
            $receipt = ReceiptStudent::findOrFail($request->input('id', $id));
            $oldStudentInstallmentId = $receipt->student_installment_id;

            $this->validateInstallment($studentInstallmentId, $request->student_id);

            $receipt->date = date('Y-m-d');
            $receipt->student_id = $request->student_id;
            $receipt->student_installment_id = $studentInstallmentId;
            $receipt->Debit = $amount;
            $receipt->description = $request->description;
            $receipt->save();

            $fundAccount = FundAccount::where('receipt_id', $receipt->id)->first();
            if ($fundAccount) {
                $fundAccount->date = date('Y-m-d');
                $fundAccount->Debit = $amount;
                $fundAccount->credit = 0.00;
                $fundAccount->description = $request->description;
                $fundAccount->save();
            }

            $studentAccount = StudentAccount::where('receipt_id', $receipt->id)->first();
            if ($studentAccount) {
                $studentAccount->date = date('Y-m-d');
                $studentAccount->type = 'receipt';
                $studentAccount->student_id = $request->student_id;
                $studentAccount->student_installment_id = $studentInstallmentId;
                $studentAccount->Debit = 0.00;
                $studentAccount->credit = $amount;
                $studentAccount->description = $request->description;
                $studentAccount->save();
            }

            $repository = app(StudentInstallmentRepositoryInterface::class);

            if ($studentInstallmentId) {
                $studentInstallment = StudentInstallment::find($studentInstallmentId);
                if ($studentInstallment) {
                    $repository->updatePaymentStatus($studentInstallment);
                }
            }

            if ($oldStudentInstallmentId && $oldStudentInstallmentId != $studentInstallmentId) {
                $oldStudentInstallment = StudentInstallment::find($oldStudentInstallmentId);
                if ($oldStudentInstallment) {
                    $repository->updatePaymentStatus($oldStudentInstallment);
                }
            }

            DB::commit();
            CacheVersion::bump('accounting');

            return $receipt;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function resolveInstallment(ReceiptStudent $receipt)
    {
        if (! $receipt->student_installment_id) {
            return null;
        }

        return StudentInstallment::find($receipt->student_installment_id);
    }

    private function validateInstallment($studentInstallmentId, $studentId)
    {
        if (! $studentInstallmentId) {
            return;
        }

        $installment = StudentInstallment::where('id', $studentInstallmentId)
            ->where('student_id', $studentId)
            ->first();

        if (! $installment) {
            throw new \Exception(__('messages.invalid_student_installment'));
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
