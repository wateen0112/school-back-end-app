<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\SalaryPayment;
use App\Models\Teacher;
use App\Services\Accounting\SalaryPaymentService;
use App\Services\Pending\PendingActionRegistry;
use App\Services\Pending\PendingRequestService;
use App\Support\CacheVersion;
use App\Support\ApiResponse;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SalaryController extends Controller
{
    protected SalaryPaymentService $payments;

    public function __construct()
    {
        $this->payments = new SalaryPaymentService();
    }

    /** Salary-eligible employees: teachers + staff (admin users), with base salary. */
    public function employees()
    {
        try {
            $teachers = Teacher::where(function ($q) {
                // Salaried (monthly), hourly and custom-contract teachers.
                $q->whereNotNull('salary')
                    ->orWhere('salary_type', 'hourly')
                    ->orWhere('salary_type', 'custom');
            })->get()->map(function ($t) {
                return [
                    'employee_type' => 'teacher',
                    'employee_id' => $t->id,
                    'name' => $t->Name,
                    'salary' => $t->salary,
                    'salary_type' => $t->salary_type ?: 'monthly',
                    'hour_rate' => $t->hour_rate,
                    'custom_salary_amount' => $t->custom_salary_amount,
                    'custom_installments_pending' => $t->salary_type === 'custom'
                        ? (int) $t->customInstallments()->pending()->count()
                        : null,
                    'role_label' => trans('messages.teacher'),
                ];
            });

            $staff = User::whereNotNull('salary')->where('is_super_admin', false)->get()->map(function ($u) {
                return [
                    'employee_type' => 'staff',
                    'employee_id' => $u->id,
                    'name' => $u->name,
                    'salary' => $u->salary,
                    'role_label' => trans('messages.staff'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Salary-eligible employees retrieved successfully',
                'data' => $teachers->merge($staff)->values(),
            ], 200);
        } catch (\Exception $e) {
            return ApiResponse::fromException($e, 'Error retrieving employees');
        }
    }

    public function index(Request $request)
    {
        try {
            $query = SalaryPayment::query()->orderByDesc('period_year')->orderByDesc('period_month');

            if ($request->filled('employee_type')) {
                $query->where('employee_type', $request->employee_type);
            }
            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }
            if ($request->filled('period_year')) {
                $query->where('period_year', $request->period_year);
            }
            if ($request->filled('period_month')) {
                $query->where('period_month', $request->period_month);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $payments = $query->get()->map(function ($p) {
                return $this->serialize($p);
            });

            return response()->json([
                'success' => true,
                'message' => 'Salary payments retrieved successfully',
                'data' => $payments,
            ], 200);
        } catch (\Exception $e) {
            return ApiResponse::fromException($e, 'Error retrieving salary payments');
        }
    }

    public function show($id)
    {
        try {
            $payment = $this->serialize(SalaryPayment::findOrFail($id));

            return response()->json([
                'success' => true,
                'message' => 'Salary payment retrieved successfully',
                'data' => $payment,
            ], 200);
        } catch (\Exception $e) {
            return ApiResponse::notFound('Salary payment not found');
        }
    }

    /** Generate pending salary rows for every salaried employee for a given month. */
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period_year' => 'required|integer|min:2000|max:2100',
            'period_month' => 'required|integer|min:1|max:12',
            'due_date' => 'nullable|date',
            'payment_method' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $year = (int) $request->period_year;
        $month = (int) $request->period_month;
        $dueDate = $request->due_date ?: now()->setYear($year)->setMonth($month)->endOfMonth()->toDateString();

        try {
            $candidates = $this->payrollCandidates();

            $created = 0;
            $skipped = 0;

            DB::beginTransaction();

            foreach ($candidates as $candidate) {
                $exists = SalaryPayment::where([
                    'employee_type' => $candidate['employee_type'],
                    'employee_id' => $candidate['employee_id'],
                    'period_year' => $year,
                    'period_month' => $month,
                ])->where('source', 'monthly')->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                SalaryPayment::create([
                    'employee_type' => $candidate['employee_type'],
                    'employee_id' => $candidate['employee_id'],
                    'period_year' => $year,
                    'period_month' => $month,
                    'amount' => $candidate['salary'],
                    'due_date' => $dueDate,
                    'status' => 'pending',
                    'source' => 'monthly',
                    'payment_method' => $request->payment_method,
                ]);
                $created++;
            }

            // Hourly teachers: pay = worked hours in the period × hour rate.
            foreach (Teacher::where('salary_type', 'hourly')->whereNotNull('hour_rate')->get() as $teacher) {
                $hours = (float) $teacher->workHours()
                    ->whereYear('work_date', $year)
                    ->whereMonth('work_date', $month)
                    ->sum('hours');

                $exists = SalaryPayment::where([
                    'employee_type' => 'teacher',
                    'employee_id' => $teacher->id,
                    'period_year' => $year,
                    'period_month' => $month,
                ])->where('source', 'hours')->exists();

                if ($exists || $hours <= 0) {
                    $skipped++;
                    continue;
                }

                SalaryPayment::create([
                    'employee_type' => 'teacher',
                    'employee_id' => $teacher->id,
                    'period_year' => $year,
                    'period_month' => $month,
                    'amount' => round($hours * (float) $teacher->hour_rate, 2),
                    'due_date' => $dueDate,
                    'status' => 'pending',
                    'source' => 'hours',
                    'payment_method' => $request->payment_method,
                    'notes' => sprintf('%s hours × %s (hour rate)', rtrim(rtrim(number_format($hours, 2, '.', ''), '0'), '.'), $teacher->hour_rate),
                ]);
                $created++;
            }

            // Custom contracts: turn every pending installment into a payment.
            foreach (Teacher::where('salary_type', 'custom')->with('customInstallments')->get() as $teacher) {
                foreach ($teacher->customInstallments->where('status', 'pending') as $installment) {
                    if ($installment->salaryPayment()->exists()) {
                        $skipped++;
                        continue;
                    }

                    SalaryPayment::create([
                        'employee_type' => 'teacher',
                        'employee_id' => $teacher->id,
                        'period_year' => (int) $installment->due_date->format('Y'),
                        'period_month' => (int) $installment->due_date->format('m'),
                        'amount' => $installment->amount,
                        'due_date' => $installment->due_date->toDateString(),
                        'status' => 'pending',
                        'source' => 'custom',
                        'installment_id' => $installment->id,
                        'payment_method' => $request->payment_method,
                        'notes' => $installment->title ?: sprintf('Custom salary installment #%d', $installment->id),
                    ]);
                    $created++;
                }
            }

            DB::commit();
            CacheVersion::bump('accounting');

            return response()->json([
                'success' => true,
                'message' => "Payroll generated: {$created} created, {$skipped} skipped (already exist)",
                'data' => ['created' => $created, 'skipped' => $skipped],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::fromException($e, 'Error generating payroll');
        }
    }

    /** Mark a pending salary payment as paid → cash OUT in the fund ledger. */
    public function pay(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'paid_at' => 'nullable|date',
            'payment_method' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $payment = SalaryPayment::findOrFail($id);

            // Paying a salary is a sensitive action: anybody who is not an
            // approver queues the request instead of moving the money.
            if (PendingRequestService::requiresApproval($request->user(), PendingActionRegistry::PAY_SALARY)) {
                $pending = PendingRequestService::defer(PendingActionRegistry::PAY_SALARY, [
                    'entity'    => $payment,
                    'title'     => 'Pay salary of '.$payment->employee_name,
                    'title_ar'  => 'صرف راتب '.$payment->employee_name,
                    'description' => sprintf(
                        'Salary payment #%d for %s (%s/%s) — amount: %s',
                        $payment->id,
                        $payment->employee_name,
                        $payment->period_month,
                        $payment->period_year,
                        $payment->amount
                    ),
                    'description_ar' => sprintf(
                        'سند صرف راتب رقم %d لـ %s (%s/%s) — المبلغ: %s',
                        $payment->id,
                        $payment->employee_name,
                        $payment->period_month,
                        $payment->period_year,
                        $payment->amount
                    ),
                    'payload' => $request->only(['paid_at', 'payment_method', 'notes']),
                    'reason'  => $request->input('reason'),
                    'priority' => $request->input('priority'),
                ], $request->user());

                return PendingRequestService::deferredResponse($pending);
            }

            $this->payments->pay($payment, $request->only(['paid_at', 'payment_method', 'notes']), auth('api')->id());

            return response()->json([
                'success' => true,
                'message' => 'Salary paid successfully',
                'data' => $this->serialize($payment->fresh()),
            ], 200);
        } catch (\Exception $e) {
            // SalaryPaymentService owns its transaction, so nothing to roll back here.
            return ApiResponse::fromException($e, 'Error paying salary');
        }
    }

    /** Manual single salary entry (e.g., bonus / advance / correction). */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_type' => 'required|in:teacher,staff',
            'employee_id' => 'required|integer',
            'period_year' => 'required|integer|min:2000|max:2100',
            'period_month' => 'required|integer|min:1|max:12',
            'amount' => 'required|numeric|min:0.01',
            'due_date' => 'required|date',
            'payment_method' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'manual_reference_no' => 'nullable|string|max:100',
        ]);

        $validator->after(function ($v) use ($request) {
            if ($request->employee_type === 'teacher') {
                if (! Teacher::whereKey($request->employee_id)->exists()) {
                    $v->errors()->add('employee_id', 'Selected teacher does not exist');
                }
            } elseif (! User::whereKey($request->employee_id)->exists()) {
                $v->errors()->add('employee_id', 'Selected staff member does not exist');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $payment = SalaryPayment::create($request->only([
                'employee_type', 'employee_id', 'period_year', 'period_month',
                'amount', 'due_date', 'payment_method', 'notes', 'manual_reference_no',
            ]) + ['status' => 'pending']);
            CacheVersion::bump('accounting');

            return response()->json([
                'success' => true,
                'message' => 'Salary entry created successfully',
                'data' => $this->serialize($payment),
            ], 201);
        } catch (\Exception $e) {
            return ApiResponse::fromException($e, 'Error creating salary entry');
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'due_date' => 'required|date',
            'status' => 'nullable|in:pending,cancelled',
            'payment_method' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $payment = SalaryPayment::findOrFail($id);

            if ($payment->status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Paid salaries cannot be edited',
                ], 409);
            }

            $payment->update($request->only(['amount', 'due_date', 'status', 'payment_method', 'notes', 'manual_reference_no']));

            // Releasing a custom payment frees its installment back to pending.
            if ($payment->installment_id && $payment->status === 'cancelled') {
                $payment->installment()->update(['status' => 'pending']);
            }
            CacheVersion::bump('accounting');

            return response()->json([
                'success' => true,
                'message' => 'Salary entry updated successfully',
                'data' => $this->serialize($payment),
            ], 200);
        } catch (\Exception $e) {
            return ApiResponse::fromException($e, 'Error updating salary entry');
        }
    }

    public function destroy($id)
    {
        try {
            $payment = SalaryPayment::findOrFail($id);

            if ($payment->status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Paid salaries cannot be deleted',
                ], 409);
            }

            if ($payment->installment_id) {
                $payment->installment()->update(['status' => 'pending']);
            }
            $payment->delete();
            CacheVersion::bump('accounting');

            return response()->json([
                'success' => true,
                'message' => 'Salary entry deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return ApiResponse::fromException($e, 'Error deleting salary entry');
        }
    }

    /** Set the base monthly salary for a teacher or staff member. */
    public function updateBaseSalary(Request $request, $type, $id)
    {
        $validator = Validator::make($request->all(), [
            'salary' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $salary = $request->filled('salary') ? $request->salary : null;

        try {
            if ($type === 'teacher') {
                $employee = Teacher::findOrFail($id);
                $employee->salary = $salary;
                $employee->save();

                $name = $employee->Name;
            } elseif ($type === 'staff') {
                $employee = User::findOrFail($id);
                $employee->salary = $salary;
                $employee->save();

                $name = $employee->name;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid employee type',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Base salary updated successfully',
                'data' => [
                    'employee_type' => $type,
                    'employee_id' => (int) $id,
                    'name' => $name,
                    'salary' => $salary,
                ],
            ], 200);
        } catch (\Exception $e) {
            return ApiResponse::notFound('Error updating base salary');
        }
    }

    /** @return array<int, array{employee_type: string, employee_id: int, salary: float}> */
    private function payrollCandidates(): array
    {
        // Only monthly-contract teachers are flat-amount candidates; hourly
        // and custom contracts are computed separately in generate().
        $teachers = Teacher::whereNotNull('salary')
            ->where(fn ($q) => $q->whereNull('salary_type')->orWhere('salary_type', 'monthly'))
            ->get(['id', 'salary'])->map(fn ($t) => [
            'employee_type' => 'teacher',
            'employee_id' => $t->id,
            'salary' => $t->salary,
        ])->all();

        $staff = User::whereNotNull('salary')->where('is_super_admin', false)->get(['id', 'salary'])->map(fn ($u) => [
            'employee_type' => 'staff',
            'employee_id' => $u->id,
            'salary' => $u->salary,
        ])->all();

        return array_merge($teachers, $staff);
    }

    private function serialize(SalaryPayment $payment): array
    {
        return [
            'id' => $payment->id,
            'employee_type' => $payment->employee_type,
            'employee_id' => $payment->employee_id,
            'employee_name' => $payment->employee_name,
            'period_year' => $payment->period_year,
            'period_month' => $payment->period_month,
            'amount' => $payment->amount,
            'due_date' => $payment->due_date?->toDateString(),
            'paid_at' => $payment->paid_at?->toDateString(),
            'status' => $payment->status,
            'source' => $payment->source ?: 'monthly',
            'installment_id' => $payment->installment_id,
            'worked_hours' => $payment->source === 'hours'
                ? (float) ($payment->employee?->workHours()
                    ->whereYear('work_date', $payment->period_year)
                    ->whereMonth('work_date', $payment->period_month)
                    ->sum('hours') ?? 0)
                : null,
            'payment_method' => $payment->payment_method,
            'notes' => $payment->notes,
            'invoice_number' => $payment->invoice_number,
            'manual_reference_no' => $payment->manual_reference_no,
            'is_overdue' => $payment->status === 'pending' && $payment->due_date->isPast(),
            'days_until_due' => $payment->status === 'pending'
                ? (int) now()->startOfDay()->diffInDays($payment->due_date, false)
                : null,
            'created_at' => $payment->created_at,
        ];
    }
}
