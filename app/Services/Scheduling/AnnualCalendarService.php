<?php

namespace App\Services\Scheduling;

use App\Models\AcademicWeek;
use App\Models\AcademicYear;
use App\Models\Holiday;
use App\Models\SchedulePeriod;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Support\Collection;

/**
 * Builds and materializes the calendar of an academic year: its weeks,
 * its daily teaching dates (working days minus holidays) and its period
 * grid (lesson slots with configured breaks).
 */
class AnnualCalendarService
{
    /**
     * Full computed calendar for one academic year.
     *
     * @return array{academic_year_id:int, weeks:array, periods:array, teaching_dates:array, stats:array}
     */
    public function calendar(AcademicYear $year): array
    {
        $weeks = $this->buildWeeks($year);
        $periods = $this->buildPeriods($year);
        $teachingDates = $this->teachingDates($year);

        return [
            'academic_year_id' => $year->id,
            'start_date'       => $year->start_date->format('Y-m-d'),
            'end_date'         => $year->end_date->format('Y-m-d'),
            'working_days'     => $year->working_days ?: [0, 1, 2, 3, 4],
            'weeks'            => $weeks,
            'periods'          => $periods,
            'teaching_dates'   => $teachingDates->map(fn (Carbon $d) => [
                'date'        => $d->format('Y-m-d'),
                'day_of_week' => (int) $d->format('w'),
                'week_number' => $this->weekNumberForDate($year, $d),
                'is_working'  => true,
                'holiday'     => $this->holidayOn($year, $d)?->only(['id', 'type', 'name', 'affects_schedule']),
            ])->values(),
            'stats' => [
                'weeks'             => count($weeks),
                'teaching_days'     => $teachingDates->count(),
                'holiday_days'      => $this->holidays($year)->count(),
                'periods_per_day'   => collect($periods)->where('type', SchedulePeriod::TYPE_LESSON)->count(),
                'expected_periods'  => $teachingDates->count() * collect($periods)->where('type', SchedulePeriod::TYPE_LESSON)->count(),
            ],
        ];
    }

    /**
     * The academic year split into 7-day weeks. Each week carries its days
     * with working/holiday flags so the UI can render an annual calendar.
     *
     * @return array<int, array{week_number:int, start_date:string, end_date:string, days:array}>
     */
    public function buildWeeks(AcademicYear $year): array
    {
        $start = Carbon::parse($year->start_date);
        $end = Carbon::parse($year->end_date);
        $holidays = $this->holidays($year)->keyBy->date;
        $workingDays = $year->working_days ?: [0, 1, 2, 3, 4];

        $weeks = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $weekNumber = $this->weekNumberForDate($year, $cursor);
            $weekStart = $cursor->copy();
            $weekEnd = $cursor->copy()->addDays(6);
            $days = [];

            for ($i = 0; $i < 7; $i++) {
                $day = $weekStart->copy()->addDays($i);
                if ($day->gt($end)) {
                    break;
                }
                $holiday = $holidays->get($day->format('Y-m-d'));
                $days[] = [
                    'date'        => $day->format('Y-m-d'),
                    'day_of_week' => (int) $day->format('w'),
                    'is_working'  => in_array((int) $day->format('w'), $workingDays, true) && ! $holiday,
                    'is_holiday'  => (bool) $holiday,
                    'holiday'     => $holiday ? $holiday->only(['id', 'type', 'name']) : null,
                ];
            }

            $weeks[] = [
                'week_number' => $weekNumber,
                'start_date'  => $weekStart->format('Y-m-d'),
                'end_date'    => $weekEnd->gt($end) ? $end->format('Y-m-d') : $weekEnd->format('Y-m-d'),
                'days'        => $days,
            ];

            $cursor = $cursor->addDays(7);
        }

        return $weeks;
    }

    /**
     * Week number (1-based) for any date inside the year.
     */
    public function weekNumberForDate(AcademicYear $year, Carbon $date): int
    {
        $start = Carbon::parse($year->start_date);
        if ($date->lt($start)) {
            return 1;
        }

        return intdiv($start->diffInDays($date), 7) + 1;
    }

    /**
     * The daily period grid derived from the year configuration.
     * Breaks configured as absolute time windows are inserted between lesson slots.
     *
     * @return array<int, array{period_number:int, start_time:string, end_time:string, type:string, note:?string}>
     */
    public function buildPeriods(AcademicYear $year): array
    {
        $start = Carbon::parse($year->start_time ?: '08:00:00');
        $duration = (int) ($year->period_duration_min ?: 45);
        $count = (int) ($year->periods_count ?: 8);
        $breaks = $year->breaks ?: [];

        $periods = [];
        $cursor = $start->copy();
        $nextNumber = 1;

        for ($i = 0; $i < $count; $i++) {
            while ($break = $this->activeBreak($breaks, $cursor, $duration)) {
                $periods[] = [
                    'period_number' => $nextNumber++,
                    'start_time'    => $cursor->format('H:i:s'),
                    'end_time'      => $break['end']->format('H:i:s'),
                    'type'          => SchedulePeriod::TYPE_BREAK,
                    'note'          => $break['name'],
                ];
                $cursor = $break['end']->copy();
            }

            $end = $cursor->copy()->addMinutes($duration);
            $periods[] = [
                'period_number' => $nextNumber++,
                'start_time'    => $cursor->format('H:i:s'),
                'end_time'      => $end->format('H:i:s'),
                'type'          => SchedulePeriod::TYPE_LESSON,
                'note'          => null,
            ];
            $cursor = $end;
        }

        return $periods;
    }

    /**
     * Replace the persisted period rows for the year with the computed grid.
     *
     * @return int number of persisted periods
     */
    public function syncPeriods(AcademicYear $year): int
    {
        SchedulePeriod::where('academic_year_id', $year->id)->delete();
        $rows = $this->buildPeriods($year);
        foreach ($rows as $row) {
            SchedulePeriod::create(['academic_year_id' => $year->id] + $row);
        }

        return count($rows);
    }

    /**
     * Replace the persisted week rows for the year with the computed weeks.
     *
     * @return int number of persisted weeks
     */
    public function syncWeeks(AcademicYear $year): int
    {
        AcademicWeek::where('academic_year_id', $year->id)->delete();
        $weeks = $this->buildWeeks($year);
        foreach ($weeks as $week) {
            AcademicWeek::updateOrCreate(
                [
                    'academic_year_id' => $year->id,
                    'week_number'      => $week['week_number'],
                ],
                [
                    'start_date' => $week['start_date'],
                    'end_date'   => $week['end_date'],
                ]
            );
        }

        return count($weeks);
    }

    /**
     * Valid teaching dates for the year (working days that are not holidays).
     */
    public function teachingDates(AcademicYear $year): Collection
    {
        $dates = collect();
        foreach ($this->buildWeeks($year) as $week) {
            foreach ($week['days'] as $day) {
                if ($day['is_working']) {
                    $dates->push(Carbon::parse($day['date']));
                }
            }
        }

        return $dates;
    }

    /**
     * Holidays scoped to the year's school: year-specific rows plus global rows.
     */
    public function holidays(AcademicYear $year): Collection
    {
        return Holiday::query()
            ->where('school_id', $year->school_id)
            ->where(function ($q) use ($year) {
                $q->where('academic_year_id', $year->id)
                    ->orWhereNull('academic_year_id');
            })
            ->get();
    }

    /**
     * @param array $breaks [{start, end, name}] absolute times as H:i[:s]
     * @return array{end: Carbon, name: ?string}|null the break that would swallow the slot
     */
    private function activeBreak(array $breaks, Carbon $cursor, int $durationMin): ?array
    {
        foreach ($breaks as $break) {
            $start = Carbon::parse($break['start'] ?? null);
            $end = Carbon::parse($break['end'] ?? null);
            if (! $start || ! $end || ! $end->gt($start)) {
                continue;
            }

            // A break interrupts whatever happens between its start and end.
            if ($cursor->between($start, $end, false)) {
                return ['end' => $end, 'name' => $break['name'] ?? null];
            }

            // The next lesson slot would end inside an upcoming break -> skip the lesson.
            $slotEnd = $cursor->copy()->addMinutes($durationMin);
            if ($start->gte($cursor) && $start->lt($slotEnd)) {
                return ['end' => $end, 'name' => $break['name'] ?? null];
            }
        }

        return null;
    }

    private function holidayOn(AcademicYear $year, Carbon $date): ?Holiday
    {
        return $this->holidays($year)->firstWhere('date', $date->format('Y-m-d'));
    }
}