<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Repository\StudentInstallmentRepositoryInterface;
use Illuminate\Http\Request;

class StudentInstallmentController extends Controller
{
    protected $studentInstallmentRepository;

    public function __construct(StudentInstallmentRepositoryInterface $studentInstallmentRepository)
    {
        $this->studentInstallmentRepository = $studentInstallmentRepository;
    }

    public function index(Request $request)
    {
        try {
            $studentId = $request->get('student_id');
            if ($studentId) {
                $user = auth()->user();
                if ($user) {
                    $userClass = get_class($user);
                    $isParent = strpos($userClass, 'My_Parent') !== false || strpos($userClass, 'Parent') !== false;
                    $isStudent = strpos($userClass, 'Student') !== false;
                    $student = \App\Models\Student::find($studentId);
                    if ($isParent && (!$student || (int) $student->parent_id !== (int) $user->id)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Unauthorized to view this student',
                        ], 403);
                    }
                    if ($isStudent && ($student && (int) $student->id !== (int) $user->id)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Unauthorized to view this student',
                        ], 403);
                    }
                }
            }

            $installments = $this->studentInstallmentRepository->index($request);

            $installments->getCollection()->transform(function ($item) {
                return [
                    'id' => $item->id,
                    'installment_plan_id' => $item->installment_plan_id,
                    'installment_id' => $item->installment_id,
                    'student_id' => $item->student_id,
                    'fee_invoice_id' => $item->fee_invoice_id,
                    'amount' => $item->amount,
                    'paid_amount' => $item->paid_amount,
                    'remaining_amount' => $item->remaining_amount,
                    'status' => $item->status,
                    'due_date' => $item->due_date ? $item->due_date->format('Y-m-d') : null,
                    'paid_at' => $item->paid_at,
                    'reminder_sent_at' => $item->reminder_sent_at,
                    'overdue_sent_at' => $item->overdue_sent_at,
                    'student' => $item->student ? ['id' => $item->student->id, 'name' => $item->student->name] : null,
                    'installment' => $item->installment ? ['id' => $item->installment->id, 'title' => $item->installment->title] : null,
                    'plan' => $item->installmentPlan ? ['id' => $item->installmentPlan->id, 'title' => $item->installmentPlan->title] : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Student installments retrieved successfully',
                'data' => $installments,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving student installments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $item = $this->studentInstallmentRepository->show($id);

            return response()->json([
                'success' => true,
                'message' => 'Student installment retrieved successfully',
                'data' => [
                    'id' => $item->id,
                    'installment_plan_id' => $item->installment_plan_id,
                    'installment_id' => $item->installment_id,
                    'student_id' => $item->student_id,
                    'fee_invoice_id' => $item->fee_invoice_id,
                    'amount' => $item->amount,
                    'paid_amount' => $item->paid_amount,
                    'remaining_amount' => $item->remaining_amount,
                    'status' => $item->status,
                    'due_date' => $item->due_date ? $item->due_date->format('Y-m-d') : null,
                    'paid_at' => $item->paid_at,
                    'reminder_sent_at' => $item->reminder_sent_at,
                    'overdue_sent_at' => $item->overdue_sent_at,
                    'student' => $item->student ? ['id' => $item->student->id, 'name' => $item->student->name] : null,
                    'installment' => $item->installment ? ['id' => $item->installment->id, 'title' => $item->installment->title, 'due_date' => $item->installment->due_date ? $item->installment->due_date->format('Y-m-d') : null] : null,
                    'plan' => $item->installmentPlan ? ['id' => $item->installmentPlan->id, 'title' => $item->installmentPlan->title] : null,
                    'receipts' => $item->receipts->map(function ($receipt) {
                        return [
                            'id' => $receipt->id,
                            'date' => $receipt->date,
                            'debit' => $receipt->Debit,
                            'description' => $receipt->description,
                        ];
                    }),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Student installment not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function myInstallments(Request $request)
    {
        try {
            $data = $this->studentInstallmentRepository->myInstallments($request);
            $user = auth()->user();
            $isParent = $user && (strpos(get_class($user), 'My_Parent') !== false || strpos(get_class($user), 'Parent') !== false);

            return response()->json([
                'success' => true,
                'message' => $isParent ? 'Children installments retrieved successfully' : 'My installments retrieved successfully',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving my installments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
