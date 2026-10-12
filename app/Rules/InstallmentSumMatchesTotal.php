<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class InstallmentSumMatchesTotal implements Rule
{
    protected $total;

    public function __construct($total)
    {
        $this->total = (float) $total;
    }

    public function passes($attribute, $value)
    {
        if (! is_array($value)) {
            return false;
        }

        $sum = collect($value)->sum('amount');

        return abs($sum - $this->total) <= 0.01;
    }

    public function message()
    {
        return trans('messages.installment_sum_mismatch');
    }
}
