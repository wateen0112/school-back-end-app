<?php

namespace App\Repository;

use App\Models\My_Parent;
use App\Models\Student;
use App\Models\StudentInstallment;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class StudentInstallmentRepository implements StudentInstallmentRepositoryInterface
{
    public function index($request)
    {
        $query = StudentInstallment::with(['student', 'installment', 'installmentPlan']);

        if ($request->has('plan_id')) {
            $query->where('installment_plan_id', $request->plan_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        return $query->orderBy('due_date', 'desc')->paginate($request->input('per_page', 20));
    }

    public function show($id)
    {
        return StudentInstallment::with(['student', 'installment', 'installmentPlan', 'receipts', 'feeInvoice'])
            ->findOrFail($id);
    }

    public function myInstallments(Request $request)
    {
        $user = auth()->user();

        if (! $user) {
            return collect();
        }

        if ($user instanceof Student) {
            return $this->studentInstallments($user, $request);
        }

        if ($user instanceof My_Parent || strpos(get_class($user), 'Parent') !== false) {
            return $this->parentInstallments($user, $request);
        }

        return collect();
    }

    private function studentInstallments(Student $student, Request $request)
    {
        $installments = StudentInstallment::with(['installment', 'installmentPlan'])
            ->where('student_id', $student->id)
            ->orderBy('due_date')
            ->get()
            ->map(function ($item) {
                return $this->formatInstallment($item);
            });

        $summary = $this->calculateSummary($installments);
        $filteredInstallments = $this->applyFilters($installments, $request);

        return [
            'summary' => $summary,
            'installments' => $filteredInstallments->values(),
        ];
    }

    private function parentInstallments($parent, Request $request)
    {
        $students = $parent->students()->with([
            'studentInstallments' => function ($query) {
                $query->orderBy('due_date');
            },
            'studentInstallments.installment',
            'studentInstallments.installmentPlan',
        ])->get();

        $allFormattedInstallments = collect();

        $children = $students->map(function ($student) use ($request, &$allFormattedInstallments) {
            $installments = $student->studentInstallments->map(function ($item) {
                return $this->formatInstallment($item);
            });

            $allFormattedInstallments = $allFormattedInstallments->merge($installments);
            $filteredInstallments = $this->applyFilters($installments, $request);

            return [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'installments' => $filteredInstallments->values(),
            ];
        });

        $summary = $this->calculateSummary($allFormattedInstallments);

        return [
            'summary' => $summary,
            'children' => $children->values(),
        ];
    }

    private function formatInstallment(StudentInstallment $item)
    {
        $isOverdue = $this->isOverdue($item);
        $daysUntilDue = $this->daysUntilDue($item);

        return [
            'id' => $item->id,
            'plan_title' => $item->installmentPlan->title ?? [],
            'installment_title' => $item->installment->title ?? null,
            'amount' => $item->amount,
            'paid_amount' => $item->paid_amount,
            'remaining_amount' => $item->remaining_amount,
            'status' => $item->status,
            'due_date' => $item->due_date ? $item->due_date->format('Y-m-d') : null,
            'is_overdue' => $isOverdue,
            'days_until_due' => $daysUntilDue,
            'urgency' => $this->calculateUrgency($item->status, $isOverdue, $daysUntilDue),
        ];
    }

    private function calculateUrgency($status, $isOverdue, $daysUntilDue)
    {
        if ($status === 'paid') {
            return 'normal';
        }

        if ($isOverdue || ($daysUntilDue !== null && $daysUntilDue <= 3)) {
            return 'urgent';
        }

        if ($daysUntilDue !== null && $daysUntilDue >= 4 && $daysUntilDue <= 14) {
            return 'upcoming';
        }

        return 'normal';
    }

    private function calculateSummary(Collection $installments)
    {
        return [
            'total_installments' => $installments->count(),
            'paid_count' => $installments->where('status', 'paid')->count(),
            'pending_count' => $installments->where('status', 'pending')->count(),
            'partial_count' => $installments->where('status', 'partial')->count(),
            'overdue_count' => $installments->where('status', 'overdue')->count(),
            'urgent_count' => $installments->where('urgency', 'urgent')->count(),
        ];
    }

    private function applyFilters(Collection $installments, Request $request)
    {
        $statusFilters = $this->parseFilter($request->input('status'));
        $urgencyFilters = $this->parseFilter($request->input('urgency'));

        if (! empty($statusFilters)) {
            $installments = $installments->whereIn('status', $statusFilters);
        }

        if (! empty($urgencyFilters)) {
            $installments = $installments->whereIn('urgency', $urgencyFilters);
        }

        return $installments;
    }

    private function parseFilter($value)
    {
        if (empty($value)) {
            return [];
        }

        return collect(explode(',', $value))
            ->map(function ($item) {
                return trim($item);
            })
            ->filter()
            ->values()
            ->toArray();
    }

    public function updatePaymentStatus(StudentInstallment $studentInstallment)
    {
        $paidAmount = $studentInstallment->receipts()->sum('Debit');
        $studentInstallment->paid_amount = $paidAmount;
        $studentInstallment->remaining_amount = max(0, $studentInstallment->amount - $paidAmount);

        if ($studentInstallment->remaining_amount <= 0) {
            $studentInstallment->status = 'paid';
            $studentInstallment->paid_at = $studentInstallment->paid_at ?? now();
        } elseif ($paidAmount > 0) {
            $studentInstallment->status = 'partial';
        } else {
            $studentInstallment->status = 'pending';
            $studentInstallment->paid_at = null;
        }

        if ($this->isOverdue($studentInstallment) && $studentInstallment->status !== 'paid') {
            $studentInstallment->status = 'overdue';
        }

        $studentInstallment->save();

        return $studentInstallment;
    }

    private function isOverdue(StudentInstallment $item)
    {
        return $item->status !== 'paid'
            && $item->due_date
            && $item->due_date->isPast();
    }

    private function daysUntilDue(StudentInstallment $item)
    {
        if (! $item->due_date) {
            return null;
        }

        return now()->startOfDay()->diffInDays($item->due_date->startOfDay(), false);
    }
}
