<?php

namespace App\Services\Teachers;

use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Applies the salary contract of a teacher.
 *
 * Three salary models are supported:
 *  - monthly: classic base salary (`salary`) generated every month.
 *  - hourly:  an `hour_rate`; monthly pay = worked hours × rate.
 *  - custom:  a fixed `custom_salary_amount` divided into non-fixed
 *             installments (teacher_custom_installments).
 *
 * The same logic runs on teacher create/update and when the plan is edited
 * directly through the salary-plan endpoint.
 */
class TeacherSalaryPlanService
{
    public function rules(): array
    {
        return [
            'Salary' => 'nullable|numeric|min:0',
            'salary_type' => 'nullable|in:monthly,hourly,custom',
            'hour_rate' => 'required_if:salary_type,hourly|nullable|numeric|min:0.01',
            'custom_salary_amount' => 'required_if:salary_type,custom|nullable|numeric|min:0.01',
            'custom_installments' => 'nullable|array',
            'custom_installments.*.amount' => 'required_with:custom_installments|numeric|min:0.01',
            'custom_installments.*.due_date' => 'required_with:custom_installments|date',
            'custom_installments.*.title' => 'nullable|string|max:255',
            'custom_installments.*.notes' => 'nullable|string',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function apply(Teacher $teacher, array $data): Teacher
    {
        Validator::make($data, $this->rules())->after(function ($validator) use ($data) {
            // The installments of a custom contract should add up to the
            // agreed total amount — a mismatch is almost always a typo.
            if (($data['salary_type'] ?? null) === Teacher::SALARY_CUSTOM
                && ! empty($data['custom_installments'])
                && isset($data['custom_salary_amount'])) {
                $sum = collect($data['custom_installments'])->sum('amount');
                if (abs($sum - (float) $data['custom_salary_amount']) > 0.01) {
                    $validator->errors()->add(
                        'custom_installments',
                        'Installments total ('.$sum.') does not match the contract amount ('.$data['custom_salary_amount'].').'
                    );
                }
            }
        })->validate();

        $type = $data['salary_type'] ?? Teacher::SALARY_MONTHLY;

        DB::transaction(function () use ($teacher, $data, $type) {
            $teacher->salary_type = $type;
            $teacher->hour_rate = $type === Teacher::SALARY_HOURLY
                ? ($data['hour_rate'] ?? null)
                : null;
            $teacher->custom_salary_amount = $type === Teacher::SALARY_CUSTOM
                ? ($data['custom_salary_amount'] ?? null)
                : null;
            // Base monthly salary only makes sense for the monthly model.
            $teacher->salary = $type === Teacher::SALARY_MONTHLY && isset($data['Salary'])
                ? ($data['Salary'] !== '' ? $data['Salary'] : null)
                : null;
            $teacher->save();

            if ($type === Teacher::SALARY_CUSTOM && array_key_exists('custom_installments', $data)) {
                $this->syncInstallments($teacher, $data['custom_installments'] ?? []);
            } elseif ($type !== Teacher::SALARY_CUSTOM && $teacher->customInstallments()->pending()->exists()) {
                // Contract changed away from custom: drop only installments
                // that were never turned into a salary payment.
                $teacher->customInstallments()->pending()->delete();
            }
        });

        return $teacher->fresh(['customInstallments']);
    }

    /**
     * Replace the pending (not yet generated) installments with the given
     * list; installments already turned into salary payments are untouched.
     *
     * @param  array<int, array{amount: mixed, due_date: mixed, title?: mixed, notes?: mixed}>  $installments
     */
    public function syncInstallments(Teacher $teacher, array $installments): void
    {
        $teacher->customInstallments()->pending()->delete();

        foreach ($installments as $installment) {
            $teacher->customInstallments()->create([
                'title' => $installment['title'] ?? null,
                'amount' => $installment['amount'],
                'due_date' => $installment['due_date'],
                'notes' => $installment['notes'] ?? null,
                'status' => 'pending',
            ]);
        }
    }

    /** Remaining (pending) value of a custom contract. */
    public function remainingAmount(Teacher $teacher): float
    {
        return (float) $teacher->customInstallments()
            ->pending()
            ->sum('amount');
    }
}
