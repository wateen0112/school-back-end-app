<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Models\Fee_invoice;
use App\Models\FundAccount;
use App\Models\PaymentStudent;
use App\Models\ProcessingFee;
use App\Models\ReceiptStudent;
use App\Models\StudentAccount;
use App\Models\StudentInstallment;
use App\Support\CacheVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinancialReportController extends Controller
{
    /**
     * Summary: إحصائيات مالية عامة
     */
    public function overview(Request $request)
    {
try {
            $year = $request->input('year', now()->year);

            $data = Cache::remember(
                'financial:overview:v1:'.CacheVersion::stamp('accounting').':'.$year,
                300,
                function () use ($year) {
                    // إجمالي الفواتير (الرسوم المفروضة)
                    $totalInvoices = Fee_invoice::whereYear('invoice_date', $year)->sum('amount');

                    // إجمالي المقبوضات (المدفوعات الواردة)
                    $totalReceipts = ReceiptStudent::whereYear('date', $year)->sum('Debit');

                    // إجمالي المدفوعات (الصرف)
                    $totalPayments = PaymentStudent::whereYear('date', $year)->sum('amount');

                    // إجمالي الرسوم الإدارية
                    $totalProcessingFees = ProcessingFee::whereYear('date', $year)->sum('amount');

                    // صافي الربح = المقبوضات - المدفوعات - الرسوم الإدارية
                    $netProfit = $totalReceipts - $totalPayments - $totalProcessingFees;

                    // المبالغ المستحقة (الفواتير - المقبوضات)
                    $totalDue = $totalInvoices - $totalReceipts;

                    // إحصائيات الأقساط
                    $installmentStats = StudentInstallment::whereYear('due_date', $year)
                        ->select(
                            DB::raw('SUM(amount) as total_amount'),
                            DB::raw('SUM(paid_amount) as total_paid'),
                            DB::raw('SUM(remaining_amount) as total_remaining'),
                            DB::raw("SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count"),
                            DB::raw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count"),
                            DB::raw("SUM(CASE WHEN status = 'partial' THEN 1 ELSE 0 END) as partial_count"),
                            DB::raw("SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_count")
                        )
                        ->first();

                    return [
                        'year' => $year,
                        'total_invoices' => round($totalInvoices, 2),
                        'total_receipts' => round($totalReceipts, 2),
                        'total_payments' => round($totalPayments, 2),
                        'total_processing_fees' => round($totalProcessingFees, 2),
                        'net_profit' => round($netProfit, 2),
                        'total_due' => round($totalDue, 2),
                        'installments' => [
                            'total_amount' => round($installmentStats->total_amount ?? 0, 2),
                            'total_paid' => round($installmentStats->total_paid ?? 0, 2),
                            'total_remaining' => round($installmentStats->total_remaining ?? 0, 2),
                            'paid_count' => (int) ($installmentStats->paid_count ?? 0),
                            'pending_count' => (int) ($installmentStats->pending_count ?? 0),
                            'partial_count' => (int) ($installmentStats->partial_count ?? 0),
                            'overdue_count' => (int) ($installmentStats->overdue_count ?? 0),
                        ],
                    ];
                }
            );

            return response()->json([
                'success' => true,
                'message' => 'Financial overview retrieved successfully',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving financial overview',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Summary: أرباح وخسائر شهرية
     */
    public function monthlyProfit(Request $request)
    {
        try {
            $year = $request->input('year', now()->year);
            $month = $request->input('month');
            $gradeId = $request->input('grade_id');
            $classroomId = $request->input('classroom_id');
            $studentId = $request->input('student_id');
            $feeType = $request->input('fee_type');

            $monthlyData = [];

            $monthsToProcess = $month ? [$month] : range(1, 12);

            foreach ($monthsToProcess as $m) {
                $startOfMonth = Carbon::create($year, $m, 1)->startOfMonth();
                $endOfMonth = Carbon::create($year, $m, 1)->endOfMonth();

                // بناء استعلام المقبوضات مع الفلاتر
                $receiptsQuery = ReceiptStudent::whereBetween('date', [$startOfMonth, $endOfMonth]);
                $paymentsQuery = PaymentStudent::whereBetween('date', [$startOfMonth, $endOfMonth]);
                $processingFeesQuery = ProcessingFee::whereBetween('date', [$startOfMonth, $endOfMonth]);
                $invoicesQuery = Fee_invoice::whereBetween('invoice_date', [$startOfMonth, $endOfMonth]);

                if ($gradeId) {
                    $receiptsQuery->whereHas('student', fn($q) => $q->where('Grade_id', $gradeId));
                    $paymentsQuery->whereHas('student', fn($q) => $q->where('Grade_id', $gradeId));
                    $processingFeesQuery->whereHas('student', fn($q) => $q->where('Grade_id', $gradeId));
                    $invoicesQuery->where('Grade_id', $gradeId);
                }

                if ($classroomId) {
                    $receiptsQuery->whereHas('student', fn($q) => $q->where('Classroom_id', $classroomId));
                    $paymentsQuery->whereHas('student', fn($q) => $q->where('Classroom_id', $classroomId));
                    $processingFeesQuery->whereHas('student', fn($q) => $q->where('Classroom_id', $classroomId));
                    $invoicesQuery->where('Classroom_id', $classroomId);
                }

                if ($studentId) {
                    $receiptsQuery->where('student_id', $studentId);
                    $paymentsQuery->where('student_id', $studentId);
                    $processingFeesQuery->where('student_id', $studentId);
                    $invoicesQuery->where('student_id', $studentId);
                }

                if ($feeType) {
                    $invoicesQuery->whereHas('fees', fn($q) => $q->where('Fee_type', $feeType));
                }

                $receipts = $receiptsQuery->sum('Debit');
                $payments = $paymentsQuery->sum('amount');
                $processingFees = $processingFeesQuery->sum('amount');
                $invoices = $invoicesQuery->sum('amount');
                $netProfit = $receipts - $payments - $processingFees;

                $monthlyData[] = [
                    'month' => $m,
                    'month_name' => $this->getArabicMonthName($m),
                    'year' => $year,
                    'receipts' => round($receipts, 2),
                    'payments' => round($payments, 2),
                    'processing_fees' => round($processingFees, 2),
                    'invoices' => round($invoices, 2),
                    'net_profit' => round($netProfit, 2),
                ];
            }

            $totalReceipts = array_sum(array_column($monthlyData, 'receipts'));
            $totalPayments = array_sum(array_column($monthlyData, 'payments'));
            $totalProcessingFees = array_sum(array_column($monthlyData, 'processing_fees'));
            $totalInvoices = array_sum(array_column($monthlyData, 'invoices'));
            $totalNetProfit = array_sum(array_column($monthlyData, 'net_profit'));

            return response()->json([
                'success' => true,
                'message' => 'Monthly profit report retrieved successfully',
                'data' => [
                    'year' => $year,
                    'month' => $month,
                    'filters' => [
                        'grade_id' => $gradeId,
                        'classroom_id' => $classroomId,
                        'student_id' => $studentId,
                        'fee_type' => $feeType,
                    ],
                    'months' => $monthlyData,
                    'totals' => [
                        'total_receipts' => round($totalReceipts, 2),
                        'total_payments' => round($totalPayments, 2),
                        'total_processing_fees' => round($totalProcessingFees, 2),
                        'total_invoices' => round($totalInvoices, 2),
                        'total_net_profit' => round($totalNetProfit, 2),
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving monthly profit report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Summary: إحصائيات مفصلة
     */
    public function statistics(Request $request)
    {
        try {
            $year = $request->input('year', now()->year);
            $gradeId = $request->input('grade_id');
            $classroomId = $request->input('classroom_id');

            // بناء الاستعلام الأساسي
            $receiptQuery = ReceiptStudent::whereYear('date', $year);
            $paymentQuery = PaymentStudent::whereYear('date', $year);
            $invoiceQuery = Fee_invoice::whereYear('invoice_date', $year);
            $processingQuery = ProcessingFee::whereYear('date', $year);

            if ($gradeId) {
                $receiptQuery->whereHas('student', function ($q) use ($gradeId) {
                    $q->where('Grade_id', $gradeId);
                });
                $paymentQuery->whereHas('student', function ($q) use ($gradeId) {
                    $q->where('Grade_id', $gradeId);
                });
                $invoiceQuery->where('Grade_id', $gradeId);
                $processingQuery->whereHas('student', function ($q) use ($gradeId) {
                    $q->where('Grade_id', $gradeId);
                });
            }

            if ($classroomId) {
                $receiptQuery->whereHas('student', function ($q) use ($classroomId) {
                    $q->where('Classroom_id', $classroomId);
                });
                $paymentQuery->whereHas('student', function ($q) use ($classroomId) {
                    $q->where('Classroom_id', $classroomId);
                });
                $invoiceQuery->where('Classroom_id', $classroomId);
                $processingQuery->whereHas('student', function ($q) use ($classroomId) {
                    $q->where('Classroom_id', $classroomId);
                });
            }

            $totalReceipts = $receiptQuery->sum('Debit');
            $totalPayments = $paymentQuery->sum('amount');
            $totalInvoices = $invoiceQuery->sum('amount');
            $totalProcessingFees = $processingQuery->sum('amount');

            // توزيع المقبوضات حسب المرحلة الدراسية
            $receiptsByGrade = ReceiptStudent::whereYear('date', $year)
                ->select('students.Grade_id', DB::raw('SUM(receipt_students.Debit) as total'))
                ->join('students', 'receipt_students.student_id', '=', 'students.id')
                ->groupBy('students.Grade_id')
                ->with('student.grade')
                ->get()
                ->map(function ($item) {
                    return [
                        'grade_id' => $item->Grade_id,
                        'grade_name' => $item->student?->grade?->Name ?? 'Unknown',
                        'total' => round($item->total, 2),
                    ];
                });

            // توزيع المقبوضات حسب نوع الرسم (من الفواتير)
            $receiptsByFeeType = Fee_invoice::whereYear('invoice_date', $year)
                ->select('fees.Fee_type', DB::raw('SUM(fee_invoices.amount) as total'))
                ->join('fees', 'fee_invoices.fee_id', '=', 'fees.id')
                ->groupBy('fees.Fee_type')
                ->get()
                ->map(function ($item) {
                    return [
                        'fee_type' => $item->Fee_type,
                        'total' => round($item->total, 2),
                    ];
                });

            // أعلى 10 طلاب دفعاً
            $topPayers = ReceiptStudent::whereYear('date', $year)
                ->select('student_id', DB::raw('SUM(Debit) as total_paid'))
                ->groupBy('student_id')
                ->orderByDesc('total_paid')
                ->take(10)
                ->with('student')
                ->get()
                ->map(function ($item) {
                    return [
                        'student_id' => $item->student_id,
                        'student_name' => $item->student?->name ?? 'Unknown',
                        'total_paid' => round($item->total_paid, 2),
                    ];
                });

            // حالة الأقساط
            $installmentStatus = StudentInstallment::whereYear('due_date', $year)
                ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total_amount'), DB::raw('SUM(paid_amount) as total_paid'))
                ->groupBy('status')
                ->get()
                ->map(function ($item) {
                    return [
                        'status' => $item->status,
                        'count' => (int) $item->count,
                        'total_amount' => round($item->total_amount, 2),
                        'total_paid' => round($item->total_paid, 2),
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Financial statistics retrieved successfully',
                'data' => [
                    'year' => $year,
                    'filters' => [
                        'grade_id' => $gradeId,
                        'classroom_id' => $classroomId,
                    ],
                    'summary' => [
                        'total_receipts' => round($totalReceipts, 2),
                        'total_payments' => round($totalPayments, 2),
                        'total_invoices' => round($totalInvoices, 2),
                        'total_processing_fees' => round($totalProcessingFees, 2),
                        'net_profit' => round($totalReceipts - $totalPayments - $totalProcessingFees, 2),
                        'total_due' => round($totalInvoices - $totalReceipts, 2),
                    ],
                    'receipts_by_grade' => $receiptsByGrade,
                    'receipts_by_fee_type' => $receiptsByFeeType,
                    'top_payers' => $topPayers,
                    'installment_status' => $installmentStatus,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving financial statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Summary: تقرير حركة الصندوق (Fund Account)
     */
    public function fundMovement(Request $request)
    {
        try {
            $year = $request->input('year', now()->year);
            $month = $request->input('month');

            $query = FundAccount::query();

            if ($month) {
                $query->whereYear('date', $year)->whereMonth('date', $month);
            } else {
                $query->whereYear('date', $year);
            }

            $movements = $query->orderBy('date', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'date' => $item->date,
                        'debit' => round($item->Debit ?? 0, 2),
                        'credit' => round($item->credit ?? 0, 2),
                        'description' => $item->description,
                        'type' => $item->receipt_id ? 'receipt' : ($item->payment_id ? 'payment' : ($item->expense_id ? 'expense' : ($item->salary_payment_id ? 'salary' : 'other'))),
                    ];
                });

            $totalDebit = $movements->sum('debit');
            $totalCredit = $movements->sum('credit');
            $balance = $totalDebit - $totalCredit;

            return response()->json([
                'success' => true,
                'message' => 'Fund movement retrieved successfully',
                'data' => [
                    'year' => $year,
                    'month' => $month,
                    'movements' => $movements,
                    'summary' => [
                        'total_debit' => round($totalDebit, 2),
                        'total_credit' => round($totalCredit, 2),
                        'balance' => round($balance, 2),
                        'count' => $movements->count(),
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving fund movement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Summary: تقرير ديون الطلاب
     */
    public function studentDebts(Request $request)
    {
        try {
            $year = $request->input('year', now()->year);
            $gradeId = $request->input('grade_id');

            $query = StudentAccount::whereYear('date', $year);

            if ($gradeId) {
                $query->whereHas('student', function ($q) use ($gradeId) {
                    $q->where('Grade_id', $gradeId);
                });
            }

            $studentBalances = StudentAccount::select(
                    'student_id',
                    DB::raw('SUM(Debit) as total_debit'),
                    DB::raw('SUM(credit) as total_credit')
                )
                ->groupBy('student_id')
                ->with('student')
                ->get()
                ->map(function ($item) {
                    $balance = ($item->total_debit ?? 0) - ($item->total_credit ?? 0);
                    return [
                        'student_id' => $item->student_id,
                        'student_name' => $item->student?->name ?? 'Unknown',
                        'total_debit' => round($item->total_debit ?? 0, 2),
                        'total_credit' => round($item->total_credit ?? 0, 2),
                        'balance' => round($balance, 2),
                    ];
                })
                ->filter(function ($item) {
                    return $item['balance'] > 0;
                })
                ->values();

            $totalDebt = $studentBalances->sum('balance');

            return response()->json([
                'success' => true,
                'message' => 'Student debts retrieved successfully',
                'data' => [
                    'year' => $year,
                    'total_debt' => round($totalDebt, 2),
                    'students_count' => $studentBalances->count(),
                    'students' => $studentBalances,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving student debts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getArabicMonthName($month)
    {
        $months = [
            1 => 'يناير',
            2 => 'فبراير',
            3 => 'مارس',
            4 => 'أبريل',
            5 => 'مايو',
            6 => 'يونيو',
            7 => 'يوليو',
            8 => 'أغسطس',
            9 => 'سبتمبر',
            10 => 'أكتوبر',
            11 => 'نوفمبر',
            12 => 'ديسمبر',
        ];
        return $months[$month] ?? '';
    }
}
