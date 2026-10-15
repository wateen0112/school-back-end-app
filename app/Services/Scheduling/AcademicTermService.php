<?php

namespace App\Services\Scheduling;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Generates and manages the two default terms of an academic year.
 */
class AcademicTermService
{
    /**
     * Auto-generate Term 1 & Term 2 for a year (idempotent — existing terms
     * are kept). Optional per-term date overrides:
     * ['TERM_1' => ['start_date' => .., 'end_date' => ..], 'TERM_2' => ..]
     *
     * Must be called inside the caller's transaction for atomicity.
     */
    public function generateDefaults(AcademicYear $year, array $ranges = []): void
    {
        foreach (AcademicTerm::codes() as $index => $code) {
            if ($year->terms()->where('term_code', $code)->exists()) {
                continue;
            }

            [$start, $end] = $this->defaultRange($year, $index);

            if (! empty($ranges[$code]['start_date']) && ! empty($ranges[$code]['end_date'])) {
                $start = Carbon::parse($ranges[$code]['start_date']);
                $end = Carbon::parse($ranges[$code]['end_date']);
            }

            $year->terms()->create([
                'name' => ['ar' => AcademicTerm::defaultName($code, 'ar'), 'en' => AcademicTerm::defaultName($code, 'en')],
                'term_code' => $code,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'status' => AcademicTerm::STATUS_PENDING,
                'is_current' => false,
            ]);
        }
    }

    /**
     * Activate a year: mark it (solely) current+active and make sure it has
     * terms, with the first one becoming the active current term. Atomic.
     */
    public function activate(AcademicYear $year, array $ranges = []): AcademicYear
    {
        return DB::transaction(function () use ($year, $ranges) {
            AcademicYear::query()
                ->where('school_id', $year->school_id)
                ->where('id', '!=', $year->id)
                ->update(['status' => AcademicYear::STATUS_DRAFT, 'is_current' => false]);

            $year->update(['status' => AcademicYear::STATUS_ACTIVE, 'is_current' => true]);

            $this->generateDefaults($year, $ranges);

            $hasCurrent = $year->terms()->where('is_current', true)->exists();
            if (! $hasCurrent) {
                $first = $year->terms()->orderBy('term_code')->first();
                $first?->update(['status' => AcademicTerm::STATUS_ACTIVE, 'is_current' => true]);
            }

            return $year->fresh(['terms']);
        });
    }

    /** Make a term the active current term of its year. */
    public function setCurrentTerm(AcademicTerm $term): AcademicTerm
    {
        if ($term->status === AcademicTerm::STATUS_COMPLETED) {
            abort(422, app()->getLocale() === 'ar'
                ? 'لا يمكن تفعيل فصل دراسي مكتمل.'
                : 'A completed term cannot be activated.');
        }

        return DB::transaction(function () use ($term) {
            $term->academicYear->terms()->update(['is_current' => false]);

            $term->update(['status' => AcademicTerm::STATUS_ACTIVE, 'is_current' => true]);

            return $term->fresh();
        });
    }

    /** Split the year duration into two halves. */
    private function defaultRange(AcademicYear $year, int $index): array
    {
        $start = $year->start_date->copy();
        $end = $year->end_date->copy();
        $days = max(1, (int) $start->diffInDays($end));
        $half = (int) floor($days / 2);

        $termStart = $index === 0 ? $start : $start->copy()->addDays($half + 1);
        $termEnd = $index === 0 ? $start->copy()->addDays($half) : $end;

        return [$termStart, $termEnd];
    }
}
