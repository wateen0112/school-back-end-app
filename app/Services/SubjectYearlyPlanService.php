<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\SubjectWeeklyRequirement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Cross-checks subject weekly requirements (periods_per_week) against the
 * total number of lessons planned for the whole academic year
 * (total_periods_per_year), using the active academic year's teaching weeks.
 */
class SubjectYearlyPlanService
{
    const FALLBACK_TEACHING_WEEKS = 36;

    /**
     * Number of teaching weeks in the active academic year.
     * Falls back to the year date range, then to a sane default.
     */
    public function teachingWeeks(): int
    {
        $year = AcademicYear::where('status', AcademicYear::STATUS_ACTIVE)
            ->orderByDesc('start_date')
            ->first();

        if ($year) {
            if (Schema::hasTable('academic_weeks')) {
                $weeksCount = $year->weeks()->count();
                if ($weeksCount > 0) {
                    return $weeksCount;
                }
            }

            if ($year->start_date && $year->end_date) {
                $fromDates = intdiv($year->start_date->diffInDays($year->end_date), 7) + 1;
                if ($fromDates > 0) {
                    return $fromDates;
                }
            }
        }

        return self::FALLBACK_TEACHING_WEEKS;
    }

    /**
     * Implied weekly periods from the yearly total (total / teaching weeks).
     */
    public function expectedPerWeek(SubjectWeeklyRequirement $requirement): ?float
    {
        if (!$requirement->total_periods_per_year) {
            return null;
        }

        return $requirement->total_periods_per_year / $this->teachingWeeks();
    }

    /**
     * Warning messages for every requirement whose periods_per_week is more
     * or less than the weekly average implied by the yearly total.
     *
     * @param Collection|SubjectWeeklyRequirement[] $requirements
     * @return string[]
     */
    public function mismatchMessages($requirements): array
    {
        $warnings = [];
        $weeks = $this->teachingWeeks();

        foreach ($requirements as $req) {
            if (!$req->total_periods_per_year) {
                continue;
            }

            $name = $this->subjectName($req);
            $implied = (int) round($req->total_periods_per_year / $weeks);

            if ($req->periods_per_week > $implied) {
                $warnings[] = "المادة \"{$name}\": الحصص الأسبوعية ({$req->periods_per_week}) أكثر من المخطط سنوياً "
                    . "({$req->total_periods_per_year} حصة على {$weeks} أسبوع تدريس ≈ {$implied} حصة أسبوعياً)";
            } elseif ($req->periods_per_week < $implied) {
                $warnings[] = "المادة \"{$name}\": الحصص الأسبوعية ({$req->periods_per_week}) أقل من المخطط سنوياً "
                    . "({$req->total_periods_per_year} حصة على {$weeks} أسبوع تدريس ≈ {$implied} حصة أسبوعياً)";
            }
        }

        return $warnings;
    }

    /**
     * Load requirements matching a grade/classroom (classroom-specific or
     * global) and return yearly-plan mismatch warnings for them.
     */
    public function mismatchesForGrade(?int $gradeId, ?int $classroomId = null): array
    {
        if (!$gradeId) {
            return [];
        }

        $requirements = SubjectWeeklyRequirement::with('subject')
            ->where('grade_id', $gradeId)
            ->where(function ($q) use ($classroomId) {
                $q->where('classroom_id', $classroomId);
                if ($classroomId) {
                    $q->orWhereNull('classroom_id');
                }
            })
            ->get();

        return $this->mismatchMessages($requirements);
    }

    private function subjectName(SubjectWeeklyRequirement $req): string
    {
        $subject = $req->subject;
        if (!$subject) {
            return 'Unknown';
        }

        $name = $subject->Name ?? ($subject->name ?? 'Unknown');
        if (is_array($name)) {
            return $name['ar'] ?? ($name['en'] ?? 'Unknown');
        }

        return $name;
    }
}
