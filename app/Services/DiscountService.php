<?php

namespace App\Services;

use App\Models\StudentDiscount;

class DiscountService
{
    /**
     * Active discounts of a student, optionally scoped to a study year.
     * A discount applies when its `years` list is empty (all years) or
     * contains the given year. The legacy `year` column is still honored.
     */
    public function forStudent(int $studentId, ?string $year = null)
    {
        return StudentDiscount::active()
            ->where('student_id', $studentId)
            ->when($year, function ($query) use ($year) {
                $query->where(function ($q) use ($year) {
                    $q->whereNull('years')
                        ->orWhereJsonContains('years', $year)
                        ->orWhere('year', $year);
                });
            })
            ->get();
    }

    /**
     * Total discount amount applied against the given base amount.
     * Percentage discounts are applied on the base first, then fixed
     * (cash) discounts. The result is capped at the base amount.
     */
    public function totalDiscount(int $studentId, float $amount, ?string $year = null): float
    {
        if ($amount <= 0) {
            return 0.0;
        }

        $discounts = $this->forStudent($studentId, $year);
        $total = 0.0;

        foreach ($discounts->where('is_percentage', true) as $discount) {
            $total += $amount * ((float) $discount->value / 100);
        }

        foreach ($discounts->where('is_percentage', false) as $discount) {
            $total += (float) $discount->value;
        }

        return round(min($total, $amount), 2);
    }

    /**
     * Serializable discount list (localized labels) for API payloads.
     */
    public function serializedFor(int $studentId, ?string $year = null, float $baseAmount = 0): array
    {
        return $this->forStudent($studentId, $year)
            ->map(function (StudentDiscount $discount) use ($baseAmount) {
                $label = StudentDiscount::TYPE_LABELS[$discount->type]
                    ?? ['ar' => $discount->type, 'en' => $discount->type];

                return [
                    'id' => (int) $discount->id,
                    'type' => $discount->type,
                    'type_label_ar' => $label['ar'],
                    'type_label_en' => $label['en'],
                    'value' => (float) $discount->value,
                    'is_percentage' => (bool) $discount->is_percentage,
                    'amount' => $discount->is_percentage
                        ? round($baseAmount * ((float) $discount->value / 100), 2)
                        : (float) $discount->value,
                    'reason' => $discount->reason,
                    'year' => $discount->year,
                    'years' => $discount->years,
                ];
            })
            ->values()
            ->all();
    }
}
