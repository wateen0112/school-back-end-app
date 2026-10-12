<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class InstallmentDatesAreSequential implements Rule
{
    public function passes($attribute, $value)
    {
        if (! is_array($value) || count($value) < 2) {
            return true;
        }

        $previousDate = null;

        foreach ($value as $installment) {
            $dueDate = $installment['due_date'] ?? null;

            if (! $dueDate) {
                continue;
            }

            if ($previousDate && strtotime($dueDate) < strtotime($previousDate)) {
                return false;
            }

            $previousDate = $dueDate;
        }

        return true;
    }

    public function message()
    {
        return trans('messages.installment_dates_not_sequential');
    }
}
