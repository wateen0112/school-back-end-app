<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\PaymentStudent;
use App\Models\ProcessingFee;
use App\Models\ReceiptStudent;
use App\Models\SalaryPayment;
use App\Support\CacheVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AccountingReportController extends Controller
{
    /** KPI cards for the accounting dashboard. */
    public function summary(Request $request)
    {
        try {
            $year = (int) ($request->year ?: now()->year);

            [$from, $to] = $this->periodRange($request, $year);

            $data = Cache::remember(
                'accounting:summary:v1:'.CacheVersion::stamp('accounting')
                    .':'.$year.'-'.($request->month ?: 'all'),
                300,
                function () use ($from, $to, $year) {
                    $revenue = (float) ReceiptStudent::whereBetween('date', [$from, $to])->sum('Debit');
                    $refunds = (float) PaymentStudent::whereBetween('date', [$from, $to])->sum('amount');
                    $processing = (float) ProcessingFee::whereBetween('date', [$from, $to])->sum('amount');
                    $expenses = (float) Expense::whereBetween('expense_date', [$from, $to])->sum('amount');
                    $salariesPaid = (float) SalaryPayment::where('status', 'paid')
                        ->whereBetween('paid_at', [$from, $to])->sum('amount');
                    $salariesDue = (float) SalaryPayment::where('status', 'pending')
                        ->whereBetween('due_date', [$from, $to])->sum('amount');

                    $grossProfit = $revenue - $refunds - $processing;
                    $netProfit = $grossProfit - $expenses - $salariesPaid;

                    return [
                        'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'year' => $year],
                        'revenue' => $revenue,
                        'refunds' => $refunds,
                        'processing_fees' => $processing,
                        'expenses' => $expenses,
                        'salaries_paid' => $salariesPaid,
                        'salaries_due' => $salariesDue,
                        'gross_profit' => $grossProfit,
                        'net_profit' => $netProfit,
                    ];
                }
            );

            return response()->json([
                'success' => true,
                'message' => 'Accounting summary retrieved successfully',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving accounting summary',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /** Monthly revenue vs expenses vs salaries + net, with category breakdown. */
    public function profitLoss(Request $request)
    {
        try {
            $year = (int) ($request->year ?: now()->year);
            [$from, $to] = $this->periodRange($request, $year);
            $cacheKey = 'accounting:pnl:v1:'.CacheVersion::stamp('accounting')
                .':'.$year.'-'.($request->month ?: 'all');

            $data = Cache::remember($cacheKey, 300, function () use ($from, $to, $year) {
                $monthly = [];
            foreach (range(1, 12) as $month) {
                $monthStart = now()->setYear($year)->setMonth($month)->startOfMonth();
                $monthEnd = now()->setYear($year)->setMonth($month)->endOfMonth();

                if ($monthStart->lt($from) || $monthEnd->gt($to)) {
                    continue;
                }

                $monthly[(int) $month] = [
                    'month' => $month,
                    'revenue' => 0.0,
                    'expenses' => 0.0,
                    'salaries' => 0.0,
                    'net' => 0.0,
                ];
            }

            $receipts = ReceiptStudent::select(
                    DB::raw('MONTH(date) as m'),
                    DB::raw('SUM(Debit) as total')
                )
                ->whereBetween('date', [$from, $to])
                ->groupBy(DB::raw('MONTH(date)'))
                ->pluck('total', 'm');

            $expenses = Expense::select(
                    DB::raw('MONTH(expense_date) as m'),
                    DB::raw('SUM(amount) as total')
                )
                ->whereBetween('expense_date', [$from, $to])
                ->groupBy(DB::raw('MONTH(expense_date)'))
                ->pluck('total', 'm');

            $salaries = SalaryPayment::select(
                    DB::raw('MONTH(paid_at) as m'),
                    DB::raw('SUM(amount) as total')
                )
                ->where('status', 'paid')
                ->whereBetween('paid_at', [$from, $to])
                ->groupBy(DB::raw('MONTH(paid_at)'))
                ->pluck('total', 'm');

            foreach ($monthly as $m => &$row) {
                $row['revenue'] = round((float) ($receipts[$m] ?? 0), 2);
                $row['expenses'] = round((float) ($expenses[$m] ?? 0), 2);
                $row['salaries'] = round((float) ($salaries[$m] ?? 0), 2);
                $row['net'] = round($row['revenue'] - $row['expenses'] - $row['salaries'], 2);
            }

            $expenseByCategory = Expense::query()
                ->leftJoin('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
                ->whereBetween('expense_date', [$from, $to])
                ->select(
                    'expense_categories.id as category_id',
                    'expense_categories.name as category_name',
                    DB::raw('SUM(expenses.amount) as total')
                )
                ->groupBy('expense_categories.id', 'expense_categories.name')
                ->orderByDesc('total')
                ->get()
                ->map(function ($row) {
                    $name = json_decode($row->category_name ?? '', true);

                    return [
                        'category_id' => $row->category_id,
                        'category_name' => is_array($name) ? $name : ($row->category_name ?: null),
                        'total' => $row->total,
                    ];
                });

                return [
                    'year' => $year,
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                    'monthly' => array_values($monthly),
                    'expense_by_category' => $expenseByCategory,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Profit & loss retrieved successfully',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving profit & loss',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /** Upcoming + overdue salary obligations. */
    public function upcomingSalaries()
    {
        try {
            $rows = Cache::remember(
                'accounting:upcoming:v1:'.CacheVersion::stamp('accounting'),
                300,
                function () {
                    return SalaryPayment::where('status', 'pending')
                        ->orderBy('due_date')
                        ->get()
                        ->map(function ($p) {
                            return [
                                'id' => $p->id,
                                'employee_name' => $p->employee_name,
                                'employee_type' => $p->employee_type,
                                'amount' => $p->amount,
                                'due_date' => $p->due_date,
                                'period_year' => $p->period_year,
                                'period_month' => $p->period_month,
                                'is_overdue' => $p->due_date->isPast(),
                                'days_until_due' => (int) now()->startOfDay()->diffInDays($p->due_date, false),
                            ];
                        });
                }
            );

            return response()->json([
                'success' => true,
                'message' => 'Upcoming salaries retrieved successfully',
                'data' => $rows,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving upcoming salaries',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /** @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon} */
    private function periodRange(Request $request, int $year): array
    {
        if ($request->filled('month')) {
            $month = (int) $request->month;
            $from = now()->setYear($year)->setMonth($month)->startOfMonth()->startOfDay();
            $to = now()->setYear($year)->setMonth($month)->endOfMonth()->endOfDay();
        } else {
            $from = now()->setYear($year)->startOfYear()->startOfDay();
            $to = now()->setYear($year)->endOfYear()->endOfDay();
        }

        return [$from, $to];
    }
}
