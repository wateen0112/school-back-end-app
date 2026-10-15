<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\SchedulePeriod;
use App\Models\Section;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Rebuilds the weekly timetable from scratch: wipes `weekly_programs` and
 * `timetable_scenarios`, then regenerates both.
 *
 * Why a rebuild is needed
 * -----------------------
 * `DatabaseSeeder::flushRelatedRows()` empties `subjects`, `sections`, `teachers`
 * and `teacher_section` with FOREIGN_KEY_CHECKS=0, but it never empties
 * `weekly_programs`. On a non-fresh database the old lesson rows therefore survive
 * while the sections/subjects/teachers they point at are gone, leaving orphans that
 * the API happily returns and the dashboard renders. This seeder removes them.
 *
 * Schedule layout
 * ---------------
 * The slot grid is read from the active academic year's configured
 * `schedule_periods`: the working days come from the year and each `lesson`
 * period becomes one row. Configured `break` periods are skipped, so no lesson
 * is ever placed on a break. When the year has no configured periods, a
 * hardcoded six-period fallback is used.
 *
 * Eight subjects are assigned per section; when there are more subjects than
 * periods the surplus rests, rotating so the rest is spread evenly across the
 * week (e.g. 8 subjects over 6 periods => 6*4 + 2*3 = 30 lessons per section).
 *
 * Sections are grouped by their *assignment signature* (subject -> teacher). Every
 * section in a group shares the same teachers, and the grid is built as a Latin
 * rectangle: the k-th active subject of a group of n sections meets section s in
 * period (k + s) mod n. Because s varies over distinct values that land on distinct
 * periods, a teacher is never scheduled in two sections at the same time — which is
 * the whole point, since one teacher here serves all five sections of a class.
 *
 * Scenarios
 * ---------
 * One clean draft scenario per grade, replacing the junk rows. No lesson rows are
 * attached to them: `WeeklyProgramController::index()` filters on `is_active` only
 * and does not scope by `scenario_id`, so scenario lessons would show up alongside
 * the live schedule and duplicate every cell in the timetable. A scenario with no
 * rows is the normal state of a draft awaiting optimisation.
 *
 * Usage:
 *   php artisan db:seed --class="Database\Seeders\DemoWeeklyTimetableSeeder"
 */
class DemoWeeklyTimetableSeeder extends Seeder
{
    /** Fallback days (Sunday .. Thursday) used when the academic year has none. */
    private const DAYS = [0, 1, 2, 3, 4];

    /**
     * Fallback [start, end] daily periods used only when the database has no
     * configured schedule_periods for the active academic year.
     */
    private const PERIODS = [
        ['08:00', '08:45'],
        ['08:50', '09:35'],
        ['09:40', '10:25'],
        ['10:30', '11:15'],
        ['11:20', '12:05'],
        ['12:10', '12:55'],
    ];

    /** Minimum subjects a section needs before a real rotating grid is possible. */
    private const MIN_SUBJECTS = 6;

    /** @var array<int, int> Working days to schedule (loaded from the academic year). */
    private $days = self::DAYS;

    /** @var array<int, array{0: string, 1: string}> Lesson periods, breaks excluded. */
    private $periods = self::PERIODS;

    /** Where the slot grid came from, for the run report. */
    private $periodSource = 'hardcoded fallback';

    public function run()
    {
        $this->loadPeriods();

        $this->purgeOrphanAssignments();

        $this->wipe();

        $groups = $this->buildGroups();
        $rows = $this->buildLessonRows($groups);

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('weekly_programs')->insert($chunk);
        }

        $this->seedScenarios($groups);

        $this->report($rows, $groups);
    }

    /**
     * Load the slot grid from the active academic year's configured periods,
     * falling back to the hardcoded layout when none exist. Only `lesson` rows
     * are used, so configured breaks become natural gaps (no lesson lands on a
     * break) and the working days come from the year as well.
     */
    private function loadPeriods(): void
    {
        try {
            $year = AcademicYear::query()
                ->where('status', AcademicYear::STATUS_ACTIVE)
                ->orderByDesc('is_current')
                ->orderByDesc('id')
                ->first();

            if (! $year) {
                $this->command?->warn('No active academic year; using hardcoded periods.');

                return;
            }

            $lessons = SchedulePeriod::query()
                ->where('academic_year_id', $year->id)
                ->whereNull('day_of_week')
                ->where('type', SchedulePeriod::TYPE_LESSON)
                ->orderBy('period_number')
                ->get();

            if ($lessons->isEmpty()) {
                $this->command?->warn("Academic year #{$year->id} has no lesson periods; using hardcoded periods.");

                return;
            }

            $this->periods = $lessons->map(fn ($p) => [
                substr($p->start_time, 0, 5),
                substr($p->end_time, 0, 5),
            ])->all();

            if (is_array($year->working_days) && count($year->working_days) > 0) {
                $this->days = array_values(array_map('intval', $year->working_days));
            }

            $this->periodSource = "academic year #{$year->id} schedule_periods";
        } catch (\Throwable $e) {
            $this->command?->warn('Could not load schedule periods ('.$e->getMessage().'); using hardcoded periods.');
        }
    }

    /**
     * Wipe the live schedule first: weekly_programs.scenario_id is ON DELETE RESTRICT,
     * so the scenarios cannot go until their lessons do.
     */
    private function wipe(): void
    {
        $programs = DB::table('weekly_programs')->count();
        $scenarios = DB::table('timetable_scenarios')->count();

        DB::table('weekly_programs')->delete();
        DB::table('timetable_scenarios')->delete();

        $this->command?->info("Removed {$programs} weekly_programs and {$scenarios} timetable_scenarios.");
    }

    /**
     * `section_subject_teacher` outlives the sections it points at, because the
     * DatabaseSeeder flush list skips it. Purge those rows so the grid is built from
     * assignments that actually resolve to a section, a subject and a teacher.
     */
    private function purgeOrphanAssignments(): void
    {
        $sectionIds = DB::table('sections')->pluck('id')->all();
        if ($sectionIds === []) {
            return;
        }

        $deleted = DB::table('section_subject_teacher')
            ->whereNotIn('section_id', $sectionIds)
            ->delete();

        if ($deleted > 0) {
            $this->command?->warn("Purged {$deleted} orphaned section_subject_teacher rows (their section no longer exists).");
        }
    }

    /**
     * Group every section that has enough subject/teacher assignments by the exact
     * teacher map it shares, so the grid builder knows which sections compete for
     * the same teachers.
     *
     * @return array<string, array{sectionIds: array<int>, subjects: array<int, int>, gradeId: int|null, classroomId: int|null}>
     */
    private function buildGroups(): array
    {
        $sections = Section::query()
            ->join('classrooms', 'sections.Class_id', '=', 'classrooms.id')
            ->select('sections.id', 'sections.Name_Section', 'sections.Grade_id', 'sections.Class_id')
            ->orderBy('sections.id')
            ->get();

        $assignments = DB::table('section_subject_teacher')
            ->whereIn('section_id', $sections->pluck('id'))
            ->whereNotNull('subject_id')
            ->orderBy('section_id')
            ->orderBy('subject_id')
            ->get()
            ->groupBy('section_id');

        $groups = [];
        $skipped = 0;
        // The Latin rectangle needs one subject per lesson period.
        $minSubjects = max(self::MIN_SUBJECTS, count($this->periods));

        foreach ($sections as $section) {
            $rows = $assignments->get($section->id, collect());

            // A teacher-less subject cannot be timetabled, and fewer subjects
            // than periods leaves no rotation (the grid needs one per period).
            $usable = $rows->filter(fn ($r) => $r->teacher_id !== null)->values();

            if ($usable->count() < $minSubjects) {
                $skipped++;

                continue;
            }

            // subject_id => teacher_id, the identity that decides who clashes.
            $map = [];
            foreach ($usable as $row) {
                $map[(int) $row->subject_id] = (int) $row->teacher_id;
            }
            ksort($map);

            $signature = md5(json_encode($map));

            $groups[$signature] ??= [
                'sectionIds'    => [],
                'subjects'      => array_keys($map),
                'teachers'      => $map,
                'gradeId'       => $section->Grade_id,
                'classroomId'   => $section->Class_id,
            ];

            $groups[$signature]['sectionIds'][] = (int) $section->id;
        }

        if ($skipped > 0) {
            $this->command?->warn("Skipped {$skipped} sections: fewer than {$minSubjects} assigned subjects with a teacher.");
        }

        return $groups;
    }

    /**
     * Build every lesson row for the live schedule (scenario_id NULL).
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildLessonRows(array $groups): array
    {
        $rows = [];
        $now = now();
        $periodCount = count($this->periods);

        foreach ($groups as $group) {
            $subjects = $group['subjects'];
            $teachers = $group['teachers'];

            // Sections in a group share every teacher, so at most `periodCount` of
            // them can be laid out before their teachers would have to be in two
            // rooms at once. Extra sections go into a further chunk, phase-shifted.
            $chunks = array_chunk($group['sectionIds'], $periodCount);

            foreach ($chunks as $chunkNo => $chunk) {
                $phase = ($chunkNo * 2) % $periodCount;

                foreach ($this->days as $day) {
                    $active = $this->activeSubjectsForDay($day, $subjects, $periodCount);

                    foreach ($chunk as $localIndex => $sectionId) {
                        $sectionIndex = ($localIndex + $phase) % $periodCount;

                        foreach ($this->periods as $periodIndex => [$start, $end]) {
                            // Latin rectangle: exactly one subject owns this cell, and
                            // each active subject lands on a different period in every
                            // section, so a shared teacher is never double-booked.
                            $subjectId = $active[($periodIndex - $sectionIndex + $periodCount) % $periodCount];

                            $rows[] = [
                                'scenario_id' => null,
                                'section_id'  => $sectionId,
                                'day_of_week' => $day,
                                'start_time'  => $start,
                                'end_time'    => $end,
                                'subject_id'  => $subjectId,
                                'teacher_id'  => $teachers[$subjectId] ?? null,
                                'location'    => 'القاعة '.(100 + (int) $group['classroomId']),
                                'notes'       => null,
                                'is_active'   => 1,
                                'created_at'  => $now,
                                'updated_at'  => $now,
                            ];
                        }
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * The subjects taught on a given day: exactly one per period, so the Latin
     * rectangle in buildLessonRows() has something to index into.
     *
     * When there are more subjects than periods the surplus rests, rotating as a
     * window that advances by the size of that window. Over DAYS days a subject is
     * therefore rested a near-equal number of times rather than always trailing the
     * same subjects.
     *
     * @param  array<int, int>  $subjects
     * @return array<int, int>
     */
    private function activeSubjectsForDay(int $day, array $subjects, int $periodCount): array
    {
        $subjectCount = count($subjects);
        $restCount = max(0, $subjectCount - $periodCount);

        // Positions within the (already sorted) subject list, not subject ids.
        $restingPositions = [];
        for ($i = 0; $i < $restCount; $i++) {
            $restingPositions[] = ($restCount * $day + $i) % $subjectCount;
        }

        $active = [];
        foreach ($subjects as $position => $subjectId) {
            if (! in_array($position, $restingPositions, true)) {
                $active[] = $subjectId;
            }
        }

        // Guard the invariant the grid depends on.
        if (count($active) !== $periodCount) {
            throw new \RuntimeException(sprintf(
                'Timetable grid needs exactly %d subjects to fill a day, got %d.',
                $periodCount,
                count($active)
            ));
        }

        return $active;
    }

    /**
     * One clean draft scenario per grade that received a timetable.
     *
     * @param  array<string, array{gradeId: int|null}>  $groups
     */
    private function seedScenarios(array $groups): void
    {
        $byGrade = [];
        foreach ($groups as $group) {
            if ($group['gradeId'] === null) {
                continue;
            }
            $byGrade[$group['gradeId']] = ($byGrade[$group['gradeId']] ?? 0) + count($group['sectionIds']);
        }

        foreach ($byGrade as $gradeId => $sectionCount) {
            $grade = Grade::find($gradeId);
            // Grade uses Spatie HasTranslations, so ->Name resolves to the app's
            // current locale (English) rather than the Arabic label. Ask explicitly.
            $gradeName = $grade
                ? $grade->getTranslation('Name', 'ar', true)
                : "grade #{$gradeId}";

            DB::table('timetable_scenarios')->insert([
                'name'         => "البرنامج الأسبوعي المقترح — {$gradeName}",
                'description'  => "سيناريو مُولَّد آليًا لـ {$sectionCount} قسم. لم تُسند إليه حصص بعد: استخدم مولّد الجداول لملئه ثم راجعه قبل النشر.",
                'grade_id'     => $gradeId,
                'classroom_id' => null,
                'section_id'   => null,
                'version'      => 1,
                'status'       => 'draft',
                'is_active'    => 0,
                'locked_slots' => null,
                'metadata'     => json_encode([
                    'generated_by'       => 'DemoWeeklyTimetableSeeder',
                    'sections'           => $sectionCount,
                    'days'               => count($this->days),
                    'periods_per_day'    => count($this->periods),
                    'period_source'      => $this->periodSource,
                    'lessons_per_section' => count($this->days) * count($this->periods),
                ], JSON_UNESCAPED_UNICODE),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, array<string, mixed>>  $groups
     */
    private function report(array $rows, array $groups): void
    {
        $conflicts = $this->teacherConflicts($rows);

        $this->command?->info('Slot grid from: '.$this->periodSource.
            ' ('.count($this->days).' days x '.count($this->periods).' lesson periods).');

        $this->command?->info(count($rows).' lessons seeded across '.count($groups).' teacher group(s).');

        if ($conflicts > 0) {
            $this->command?->warn("WARNING: {$conflicts} teacher double-booking slot(s) detected.");

            return;
        }

        $this->command?->info('No teacher double-bookings.');
    }

    /**
     * Count (teacher, day, period) cells holding more than one lesson. Must be zero:
     * a teacher can only be in one room at a time.
     */
    private function teacherConflicts(array $rows): int
    {
        $seen = [];
        $conflicts = 0;

        foreach ($rows as $row) {
            if ($row['teacher_id'] === null) {
                continue;
            }

            $key = $row['teacher_id'].'|'.$row['day_of_week'].'|'.$row['start_time'];

            if (isset($seen[$key])) {
                $conflicts++;
            } else {
                $seen[$key] = true;
            }
        }

        return $conflicts;
    }
}