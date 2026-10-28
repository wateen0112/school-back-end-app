<?php

namespace App\Http\Controllers\Api\Parents;

use App\Http\Controllers\Controller;
use App\Models\Fee_invoice;
use App\Models\My_Parent;
use App\Models\ReceiptStudent;
use App\Services\DashboardService;
use App\Services\DiscountService;
use Illuminate\Http\Request;

class ParentFinanceController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $parent = null;

            if ($user instanceof My_Parent) {
                $parent = $user;
            } elseif (strpos(get_class($user), 'My_Parent') !== false) {
                $parent = $user;
            } elseif ($request->filled('parent_id')) {
                $parent = My_Parent::find($request->parent_id);
            }

            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parent not found or unauthorized',
                ], 403);
            }

            $service = new DashboardService();
            $discounts = new DiscountService();
            $children = $parent->students()->with(['grade', 'classroom', 'section'])->get();

            $totalInvoiced = 0;
            $totalPaid = 0;
            $totalDiscount = 0;
            $childrenData = [];

            foreach ($children as $student) {
                $invoices = Fee_invoice::where('student_id', $student->id)
                    ->with('fees')
                    ->orderBy('invoice_date')
                    ->orderBy('id')
                    ->get();

                $receipts = ReceiptStudent::where('student_id', $student->id)
                    ->orderBy('date', 'desc')
                    ->orderBy('id', 'desc')
                    ->get();

                $childTotalInvoiced = 0;
                $childTotalPaid = (float) $receipts->sum('Debit');

                // FIFO allocation of paid amounts across invoices (oldest first)
                $paidPool = $childTotalPaid;
                $invoiceItems = [];
                foreach ($invoices as $invoice) {
                    $amount = (float) $invoice->amount;
                    $childTotalInvoiced += $amount;
                    $paid = min($amount, $paidPool);
                    $paidPool = max(0, $paidPool - $paid);
                    $remaining = round($amount - $paid, 2);

                    if ($remaining <= 0 && $paid > 0) {
                        $status = 'paid';
                    } elseif ($paid > 0) {
                        $status = 'partial';
                    } else {
                        $status = 'unpaid';
                    }

                    $fee = $invoice->fees;
                    $feeTitle = $fee ? $fee->getTranslations('title') : [];
                    $invoiceItems[] = [
                        'id'             => (int) $invoice->id,
                        'invoice_date'   => $invoice->invoice_date instanceof \Carbon\Carbon
                            ? $invoice->invoice_date->format('Y-m-d')
                            : (string) $invoice->invoice_date,
                        'amount'         => round($amount, 2),
                        'paid'           => round($paid, 2),
                        'remaining'      => $remaining,
                        'status'         => $status,
                        'fee_title_ar'   => $feeTitle['ar'] ?? ($fee ? (string) $fee->title : '—'),
                        'fee_title_en'   => $feeTitle['en'] ?? ($fee ? (string) $fee->title : 'Fee'),
                        'fee_type'       => $fee ? (string) $fee->Fee_type : null,
                        'description'    => $invoice->description,
                    ];
                }

                $receiptItems = $receipts->map(function ($receipt) {
                    return [
                        'id'             => (int) $receipt->id,
                        'date'           => $receipt->date instanceof \Carbon\Carbon
                            ? $receipt->date->format('Y-m-d')
                            : (string) $receipt->date,
                        'amount'         => round((float) $receipt->Debit, 2),
                        'description'    => $receipt->description,
                    ];
                })->values();

                $childDiscount = $discounts->totalDiscount(
                    $student->id,
                    $childTotalInvoiced,
                    null
                );
                $childRemaining = round(
                    max(0, $childTotalInvoiced - $childTotalPaid - $childDiscount),
                    2
                );

                $totalInvoiced += $childTotalInvoiced;
                $totalPaid += $childTotalPaid;
                $totalDiscount += $childDiscount;

                $childrenData[] = [
                    'id'             => (int) $student->id,
                    'name'           => $service->nameOf($student, 'name'),
                    'grade_name'     => $student->grade ? $service->nameOf($student->grade) : null,
                    'classroom_name' => $student->classroom ? $service->nameOf($student->classroom) : null,
                    'section_name'   => $student->section ? $service->nameOf($student->section, 'Name_Section') : null,
                    'finance'        => [
                        'total_invoiced' => round($childTotalInvoiced, 2),
                        'total_paid'     => round($childTotalPaid, 2),
                        'total_discount' => round($childDiscount, 2),
                        'remaining'      => $childRemaining,
                        'invoice_count'  => $invoices->count(),
                        'receipt_count'  => $receipts->count(),
                    ],
                    'discounts'      => $discounts->serializedFor(
                        $student->id,
                        null,
                        $childTotalInvoiced
                    ),
                    'invoices'       => $invoiceItems,
                    'receipts'       => $receiptItems,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Parent financial report retrieved successfully',
                'data' => [
                    'parent' => [
                        'id'   => (int) $parent->id,
                        'name' => $service->nameOf($parent, 'Name_Father'),
                    ],
                    'summary' => [
                        'children_count' => $children->count(),
                        'total_invoiced' => round($totalInvoiced, 2),
                        'total_paid'     => round($totalPaid, 2),
                        'total_discount' => round($totalDiscount, 2),
                        'total_remaining'=> round(
                            max(0, $totalInvoiced - $totalPaid - $totalDiscount),
                            2
                        ),
                    ],
                    'children' => $childrenData,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving parent financial report',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}