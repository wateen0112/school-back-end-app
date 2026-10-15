<?php

namespace App\Http\Controllers\Api\Teachers;

use App\Http\Controllers\Controller;
use App\Models\SalaryPayment;
use Illuminate\Support\Facades\Auth;

class TeacherSalaryController extends Controller
{
    /** Own salary info + full payment history for the authenticated teacher. */
    public function index()
    {
        try {
            $teacher = Auth::guard('teacher')->user();

            if (! $teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher not authenticated',
                ], 401);
            }

            $payments = SalaryPayment::where('employee_type', 'teacher')
                ->where('employee_id', $teacher->id)
                ->orderByDesc('period_year')
                ->orderByDesc('period_month')
                ->get()
                ->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'period_year' => $p->period_year,
                        'period_month' => $p->period_month,
                        'amount' => $p->amount,
                        'due_date' => $p->due_date?->toDateString(),
                        'paid_at' => $p->paid_at?->toDateString(),
                        'status' => $p->status,
                        'payment_method' => $p->payment_method,
                        'notes' => $p->notes,
                        'is_overdue' => $p->status === 'pending' && $p->due_date->isPast(),
                    ];
                });

            $salaryType = $teacher->salary_type ?: 'monthly';

            $nextDue = $payments->firstWhere('status', 'pending');

            return response()->json([
                'success' => true,
                'message' => 'Teacher salary retrieved successfully',
                'data' => [
                    'base_salary' => $teacher->salary,
                    'salary_type' => $salaryType,
                    'hour_rate' => $teacher->hour_rate,
                    'custom_salary_amount' => $teacher->custom_salary_amount,
                    'custom_installments' => $salaryType === 'custom'
                        ? $teacher->customInstallments()->get()->map(fn ($i) => [
                            'id' => $i->id,
                            'title' => $i->title,
                            'amount' => (float) $i->amount,
                            'due_date' => $i->due_date?->toDateString(),
                            'status' => $i->status,
                        ])->values()
                        : null,
                    'next_due' => $nextDue ? [
                        'amount' => $nextDue->amount,
                        'due_date' => $nextDue->due_date?->toDateString(),
                        'period_year' => $nextDue->period_year,
                        'period_month' => $nextDue->period_month,
                        'is_overdue' => $nextDue->due_date->isPast(),
                    ] : null,
                    'history' => $payments,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving teacher salary',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
