<?php

namespace App\Services;

use App\Models\WeeklyProgram;
use App\Models\SubjectWeeklyRequirement;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Section;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\TeacherWeeklySchedule;
use App\Models\TeacherPreference;
use App\Models\SchoolScheduleSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class TimetableOptimizationService
{
    private $daysInWeek = [0, 1, 2, 3, 4]; // Sun-Thu
    private $maxIterations = 1000;

    /**
     * Original configured time slots (kept so break filtering can rebuild the active set).
     */
    private $baseTimeSlots = [
        '08:00' => '08:45',
        '08:50' => '09:35',
        '09:40' => '10:25',
        '10:35' => '11:20',
        '11:25' => '12:10',
        '12:15' => '13:00',
        '13:30' => '14:15',
        '14:20' => '15:05',
    ];

    /**
     * Active time slots after applying school breaks.
     */
    private $timeSlots = [];

    /**
     * Optional per-day slots derived from the academic-year schedule periods:
     * [day_of_week => [start => end]]. When non-empty it takes precedence over
     * the uniform $timeSlots set (which is kept as a union for shared helpers).
     */
    private $daySlots = [];

    /**
     * Teacher availability matrix: [teacher_id][day] => ['start','end','is_available','is_preferred'].
     */
    private $availability = [];

    /**
     * Teacher preference map: [teacher_id] => ['preferred_consecutive','max_consecutive','preferred_start_time','preferred_end_time','avoid_days'].
     */
    private $preferenceMap = [];

    /**
     * Fixed placements of this teacher in OTHER sections: [teacher_id][day][start] => true.
     */
    private $busyMap = [];

    private $schoolSettings = null;

    private $strictAvailability = false;

    /**
     * [subject_id => teacher_id] for the current section.
     */
    private $sectionTeacherMap = [];

    public function __construct()
    {
        $this->timeSlots = $this->baseTimeSlots;
    }

    /**
     * Build a map of subject_id => teacher_id for a section.
     * Falls back to subject.teacher_id when no explicit assignment exists.
     */
    private function buildSectionTeacherMap(Section $section): array
    {
        $map = [];

        foreach ($section->subjectTeachers as $assignment) {
            if ($assignment->subject_id && $assignment->teacher_id) {
                $map[(int) $assignment->subject_id] = (int) $assignment->teacher_id;
            }
        }

        return $map;
    }

    public function setTimeSlots(array $slots)
    {
        $this->baseTimeSlots = $slots;
        $this->timeSlots = $slots;
        return $this;
    }

    /**
     * Use explicit per-day slots (from academic-year schedule periods).
     *
     * @param array $daySlots [day_of_week => [start => end]]
     */
    public function setDaySlots(array $daySlots)
    {
        $this->daySlots = $daySlots;

        $union = [];
        foreach ($daySlots as $slots) {
            foreach ($slots as $start => $end) {
                $union[$start] = $end;
            }
        }
        ksort($union);

        if (!empty($union)) {
            $this->timeSlots = $union;
        }

        return $this;
    }

    /**
     * Slots available on a given day (per-day when configured, else uniform).
     */
    private function slotsFor(int $day): array
    {
        if (!empty($this->daySlots)) {
            return $this->daySlots[$day] ?? [];
        }

        return $this->timeSlots;
    }

    /**
     * Generate an optimized timetable for a section.
     *
     * @param int $sectionId
     * @param array $options  ['scenario_id' => null, 'preserve_locked' => true, 'existing_programs' => [], 'seed' => null, 'strict_availability' => false]
     * @return array ['programs' => [], 'warnings' => [], 'stats' => []]
     */
    public function generate(int $sectionId, array $options = []): array
    {
        $section = Section::with(['subjectTeachers.teacher', 'subjectTeachers.subject'])->findOrFail($sectionId);
        $gradeId = $section->Grade_id;
        $classroomId = $section->Class_id;

        $this->sectionTeacherMap = $this->buildSectionTeacherMap($section);

        // Seed the RNG so alternate scenarios differ but stay reproducible.
        if (isset($options['seed'])) {
            mt_srand((int) $options['seed']);
        }

        $this->strictAvailability = (bool) ($options['strict_availability'] ?? false);

        // Apply school working hours / days / breaks.
        $this->schoolSettings = SchoolScheduleSetting::first();

        // Academic-year schedule periods take precedence when supplied.
        if (!empty($options['day_slots'])) {
            $this->setDaySlots($options['day_slots']);
            $this->daysInWeek = !empty($options['days'])
                ? array_values(array_map('intval', $options['days']))
                : $this->resolveWorkingDays();
        } else {
            $this->daysInWeek = $this->resolveWorkingDays();
            $this->applyBreaksToSlots();
        }

        $this->loadTeacherData($sectionId);

        $requirements = SubjectWeeklyRequirement::with('subject')
            ->where('grade_id', $gradeId)
            ->where(function ($q) use ($classroomId) {
                $q->where('classroom_id', $classroomId)
                    ->orWhereNull('classroom_id');
            })
            ->get();

        if ($requirements->isEmpty()) {
            return ['programs' => [], 'warnings' => ['لا توجد متطلبات أسبوعية للمواد'], 'stats' => []];
        }

        // Distribute requirement subjects that have NO teacher assigned in
        // this section across the section's own teachers (least loaded
        // first), so teacher availability/preferences actually drive the
        // schedule instead of falling back to a legacy subject.teacher_id.
        $this->assignUnassignedSubjects($requirements);

        $lockedSlots = ($options['preserve_locked'] ?? true) ? $this->getLockedSlots($sectionId, $options['scenario_id'] ?? null) : [];
        $existingPrograms = $options['existing_programs'] ?? [];

        $teacherWorkload = $this->getTeacherWorkload();

        $subjectPeriods = $this->buildSubjectPeriods($requirements);
        $studentWorkload = $this->buildStudentWorkloadDistribution($requirements, count($this->daysInWeek));

        $grid = $this->buildEmptyGrid();
        $this->placeLockedSlots($grid, $lockedSlots, $existingPrograms);

        // Locked/existing placements already consume subject requirements, so
        // count them up front to avoid over-allocating periods on top of them.
        foreach ($grid as $day => $slots) {
            foreach ($slots as $cell) {
                if ($cell === null) continue;
                $sid = $cell['subject_id'];
                if (isset($subjectPeriods[$sid])) {
                    $subjectPeriods[$sid]['placed']++;
                }
            }
        }

        $this->distributePeriods($grid, $subjectPeriods, $teacherWorkload, $studentWorkload, $sectionId);

        $programs = $this->gridToPrograms($grid, $sectionId, $options['scenario_id'] ?? null);

        $classroomCapacity = $section->My_classs ? $section->My_classs->capacity : null;
        $studentCount = Student::where('section_id', $sectionId)->count();

        $warnings = $this->generateWarnings($grid, $teacherWorkload, $requirements, $classroomCapacity, $studentCount);

        $unmet = 0;
        foreach ($requirements as $req) {
            $placed = 0;
            foreach ($grid as $day => $slots) {
                foreach ($slots as $cell) {
                    if ($cell && $cell['subject_id'] == $req->subject_id) $placed++;
                }
            }
            if ($placed < $req->periods_per_week) $unmet++;
        }

        $stats = [
            'total_periods'     => count($programs),
            'required_periods'  => $requirements->sum('periods_per_week'),
            'placed_periods'    => count($programs),
            'unmet_subjects'    => $unmet,
            'subjects_count'    => $requirements->count(),
            'days_used'         => $this->countDaysUsed($grid),
            'teacher_utilization' => $this->calculateTeacherUtilization($teacherWorkload),
        ];

        return ['programs' => $programs, 'warnings' => $warnings, 'stats' => $stats];
    }

    private function resolveWorkingDays(): array
    {
        if ($this->schoolSettings && is_array($this->schoolSettings->days) && count($this->schoolSettings->days) > 0) {
            $days = array_map('intval', $this->schoolSettings->days);
            sort($days);
            return array_values(array_unique($days));
        }
        return [0, 1, 2, 3, 4];
    }

    private function applyBreaksToSlots(): void
    {
        $slots = $this->baseTimeSlots;
        $breaks = $this->schoolSettings && is_array($this->schoolSettings->breaks)
            ? $this->schoolSettings->breaks
            : [];

        foreach ($breaks as $break) {
            if (empty($break['start']) || empty($break['end'])) continue;
            foreach ($slots as $start => $end) {
                $overlaps = ($break['start'] < $end) && ($break['end'] > $start);
                if ($overlaps) {
                    unset($slots[$start]);
                }
            }
        }

        $this->timeSlots = $slots;
    }

    private function loadTeacherData(int $sectionId): void
    {
        $this->availability = [];
        $this->preferenceMap = [];
        $this->busyMap = [];

        foreach (TeacherWeeklySchedule::all() as $row) {
            $this->availability[$row->teacher_id][$row->day_of_week] = [
                'start'        => $this->shortTime($row->start_time),
                'end'          => $this->shortTime($row->end_time),
                'is_available' => (bool) $row->is_available,
                'is_preferred' => (bool) $row->is_preferred,
            ];
        }

        foreach (TeacherPreference::all() as $row) {
            $this->preferenceMap[$row->teacher_id] = [
                'preferred_consecutive' => $row->preferred_consecutive ? (int) $row->preferred_consecutive : null,
                'max_consecutive'       => $row->max_consecutive ? (int) $row->max_consecutive : null,
                'max_periods_per_day'   => $row->max_periods_per_day ? (int) $row->max_periods_per_day : null,
                'preferred_start_time'  => $row->preferred_start_time ? $this->shortTime($row->preferred_start_time) : null,
                'preferred_end_time'    => $row->preferred_end_time ? $this->shortTime($row->preferred_end_time) : null,
                'avoid_days'            => is_array($row->avoid_days) ? array_map('intval', $row->avoid_days) : [],
            ];
        }

        // Fixed placements in other sections (used for conflicts + consecutive detection).
        $fixedPrograms = WeeklyProgram::where('is_active', true)
            ->where('section_id', '!=', $sectionId)
            ->get(['teacher_id', 'day_of_week', 'start_time']);

        foreach ($fixedPrograms as $p) {
            if (!$p->teacher_id) continue;
            $this->busyMap[$p->teacher_id][$p->day_of_week][$this->shortTime($p->start_time)] = true;
        }
    }

    private function shortTime($time): string
    {
        return substr((string) $time, 0, 5);
    }

    /**
     * Assign requirement subjects without a teacher in this section to one of
     * the section's teachers, balancing the total weekly load so preferences
     * and availability of ALL section teachers are taken into account.
     */
    private function assignUnassignedSubjects(Collection $requirements): void
    {
        $pool = array_values(array_unique(array_filter($this->sectionTeacherMap)));
        if (empty($pool)) return;

        $load = array_fill_keys($pool, 0);
        foreach ($this->sectionTeacherMap as $subjectId => $tid) {
            $req = $requirements->firstWhere('subject_id', $subjectId);
            if ($req) $load[$tid] += (int) $req->periods_per_week;
        }

        foreach ($requirements as $req) {
            if (isset($this->sectionTeacherMap[$req->subject_id])) continue;
            asort($load);
            $tid = (int) array_key_first($load);
            $this->sectionTeacherMap[$req->subject_id] = $tid;
            $load[$tid] += (int) $req->periods_per_week;
        }
    }

    /**
     * Build subject periods map: subject_id => number of periods needed.
     */
    private function buildSubjectPeriods(Collection $requirements): array
    {
        $periods = [];
        foreach ($requirements as $req) {
            $periods[$req->subject_id] = [
                'needed' => $req->periods_per_week,
                'placed' => 0,
                'max_per_day' => $req->max_periods_per_day,
                'allow_consecutive' => $req->allow_consecutive,
                'subject_name' => $req->subject ? $req->subject->Name : 'Unknown',
            ];
        }
        return $periods;
    }

    /**
     * Build student workload distribution targets per day.
     */
    private function buildStudentWorkloadDistribution(Collection $requirements, int $days): array
    {
        $totalPeriods = $requirements->sum('periods_per_week');
        $targetPerDay = $days > 0 ? ceil($totalPeriods / $days) : 0;
        return [
            'target_per_day' => $targetPerDay,
            'max_per_day' => $targetPerDay + 2,
            'total' => $totalPeriods,
        ];
    }

    /**
     * Current teacher workload derived from fixed programs of other sections.
     */
    private function getTeacherWorkload(): array
    {
        $workload = [];
        foreach ($this->busyMap as $tid => $days) {
            if (!isset($workload[$tid])) $workload[$tid] = ['total' => 0, 'per_day' => []];
            foreach ($days as $day => $times) {
                $workload[$tid]['total'] += count($times);
                $workload[$tid]['per_day'][$day] = count($times);
            }
        }
        return $workload;
    }

    /**
     * Get locked (manually locked) slots. Honors scenario.locked_slots refs when present.
     */
    private function getLockedSlots(int $sectionId, ?int $scenarioId): array
    {
        $scenario = $scenarioId ? \App\Models\TimetableScenario::find($scenarioId) : null;

        if ($scenario && is_array($scenario->locked_slots) && count($scenario->locked_slots) > 0) {
            $query = WeeklyProgram::where('section_id', $sectionId)->where('is_active', true);
            $query->where(function ($q) use ($scenario) {
                foreach ($scenario->locked_slots as $ref) {
                    if (!isset($ref['day']) || !isset($ref['start_time'])) continue;
                    $q->orWhere(function ($qq) use ($ref) {
                        $qq->where('day_of_week', (int) $ref['day'])
                            ->where('start_time', substr($ref['start_time'], 0, 5) . ':00');
                    });
                }
            });
            return $query->get()->toArray();
        }

        // Legacy behavior: preserve all existing programs for the section/scenario.
        $q = WeeklyProgram::where('section_id', $sectionId)->where('is_active', true);
        if ($scenarioId) {
            $q->where('scenario_id', $scenarioId);
        }
        return $q->get()->toArray();
    }

    /**
     * Build an empty scheduling grid: grid[day][slot] = null.
     */
    private function buildEmptyGrid(): array
    {
        $grid = [];
        foreach ($this->daysInWeek as $day) {
            $grid[$day] = [];
            foreach ($this->slotsFor($day) as $start => $end) {
                $grid[$day][$start] = null;
            }
        }
        return $grid;
    }

    /**
     * Place locked (existing) programs into the grid.
     */
    private function placeLockedSlots(array &$grid, array $lockedSlots, array $existingPrograms): void
    {
        $all = array_merge($lockedSlots, $existingPrograms);
        foreach ($all as $program) {
            $day = $program['day_of_week'];
            $start = substr($program['start_time'], 0, 5);
            if (array_key_exists($day, $grid) && array_key_exists($start, $grid[$day])) {
                $grid[$day][$start] = [
                    'subject_id' => $program['subject_id'],
                    'teacher_id' => $program['teacher_id'] ?? null,
                    'locked' => true,
                ];
            }
        }
    }

    /**
     * Main distribution algorithm using constraint-based scheduling.
     */
    private function distributePeriods(
        array &$grid,
        array &$subjectPeriods,
        array $teacherWorkload,
        array $studentWorkload,
        int $sectionId
    ): void {
        $unplaced = [];
        foreach ($subjectPeriods as $sid => &$info) {
            $remaining = $info['needed'] - $info['placed'];
            for ($i = 0; $i < $remaining; $i++) {
                $unplaced[] = $sid;
            }
        }

        // Sort by most constrained first (subjects with fewer available slots)
        usort($unplaced, function ($a, $b) use ($grid, $subjectPeriods) {
            $aSlots = $this->countAvailableSlots($grid, $subjectPeriods[$a]);
            $bSlots = $this->countAvailableSlots($grid, $subjectPeriods[$b]);
            return $aSlots <=> $bSlots;
        });

        foreach ($unplaced as $subjectId) {
            $info = &$subjectPeriods[$subjectId];
            if ($info['placed'] >= $info['needed']) continue;

            $bestSlot = $this->findBestSlot(
                $grid, $subjectId, $info, $teacherWorkload, $studentWorkload
            );

            if ($bestSlot) {
                $grid[$bestSlot['day']][$bestSlot['time']] = [
                    'subject_id' => $subjectId,
                    'teacher_id' => $bestSlot['teacher_id'] ?? null,
                    'locked' => false,
                ];
                $info['placed']++;

                if ($bestSlot['teacher_id']) {
                    $tid = $bestSlot['teacher_id'];
                    if (!isset($teacherWorkload[$tid])) $teacherWorkload[$tid] = ['total' => 0, 'per_day' => []];
                    $teacherWorkload[$tid]['total']++;
                    $teacherWorkload[$tid]['per_day'][$bestSlot['day']] = ($teacherWorkload[$tid]['per_day'][$bestSlot['day']] ?? 0) + 1;
                }
            }
        }
    }

    /**
     * Find the best available slot for a subject period using scoring.
     * Hard constraints are skipped, soft constraints affect the score.
     */
    private function findBestSlot(
        array $grid,
        int $subjectId,
        array $info,
        array $teacherWorkload,
        array $studentWorkload
    ): ?array {
        $teacherId = $this->sectionTeacherMap[$subjectId] ?? null;

        if (!$teacherId) {
            // Last resort: legacy direct column (only when the section has no
            // teachers at all and the balancing pass couldn't assign one).
            $teacherId = Subject::find($subjectId)->teacher_id ?? null;
        }

        $bestSlot = null;
        $bestScore = -PHP_INT_MAX;

        foreach ($this->daysInWeek as $day) {
            $dayCount = $this->countDaySlots($grid, $day);

            // HARD: student workload per day
            if ($dayCount >= $studentWorkload['max_per_day']) continue;

            // HARD: subject max per day
            $subjectDayCount = $this->countSubjectInDay($grid, $day, $subjectId);
            if ($subjectDayCount >= $info['max_per_day']) continue;

            foreach ($this->slotsFor($day) as $time => $end) {
                if ($grid[$day][$time] !== null) continue;

                // HARD: subject consecutive constraint — when consecutive
                // periods are disallowed, only forbid placing this subject
                // directly before/after another period of the SAME subject
                // (not multiple periods of it on the same day).
                if (!$info['allow_consecutive']) {
                    $slotKeys = array_keys($this->slotsFor($day));
                    $idx = array_search($time, $slotKeys, true);
                    if ($idx !== false) {
                        $prev = $idx > 0 ? $grid[$day][$slotKeys[$idx - 1]] : null;
                        $next = $idx < count($slotKeys) - 1 ? $grid[$day][$slotKeys[$idx + 1]] : null;
                        if (($prev && $prev['subject_id'] == $subjectId)
                            || ($next && $next['subject_id'] == $subjectId)) {
                            continue;
                        }
                    }
                }

                if (!$this->isTeacherSlotAvailable($grid, $day, $time, $teacherId)) continue;

                $score = $this->calculateSlotScore(
                    $day, $time, $end, $teacherId, $teacherWorkload, $dayCount, $subjectDayCount, $grid
                );

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestSlot = ['day' => $day, 'time' => $time, 'teacher_id' => $teacherId];
                }
            }
        }

        return $bestSlot;
    }

    /**
     * HARD constraint check: can the teacher actually teach in this slot?
     */
    private function isTeacherSlotAvailable(array $grid, int $day, string $time, ?int $teacherId): bool
    {
        if (!$teacherId) return true;

        $pref = $this->preferenceMap[$teacherId] ?? null;

        // HARD: teacher requested not to teach on this day.
        if ($pref && !empty($pref['avoid_days']) && in_array($day, $pref['avoid_days'], true)) {
            return false;
        }

        $slotEnd = $this->slotsFor($day)[$time] ?? $time;
        $avail = $this->availability[$teacherId][$day] ?? null;

        if ($avail) {
            // HARD: unavailable day.
            if (!$avail['is_available']) return false;
            // HARD: outside the teacher's working window.
            if ($avail['start'] && $time < $avail['start']) return false;
            if ($avail['end'] && $slotEnd > $avail['end']) return false;
        } elseif ($this->strictAvailability) {
            // HARD: no schedule recorded and strict mode requires one.
            return false;
        }

        // HARD: teacher already busy at this time in another section.
        // (This also covers same-time double booking; a grid cell can only
        // hold one lesson, and same-day repeat lessons are allowed so that
        // preferred_consecutive / max_consecutive can actually be honored.)
        if (isset($this->busyMap[$teacherId][$day][$time])) return false;

        // HARD: would exceed the teacher's max consecutive periods per day.
        if ($pref && !empty($pref['max_consecutive'])) {
            if ($this->consecutiveRunLength($day, $time, $teacherId, $grid) > (int) $pref['max_consecutive']) {
                return false;
            }
        }

        // HARD: teacher's maximum lessons per day (fixed lessons in other
        // sections + lessons already placed in this grid).
        if ($pref && !empty($pref['max_periods_per_day'])) {
            $dayCount = isset($this->busyMap[$teacherId][$day]) ? count($this->busyMap[$teacherId][$day]) : 0;
            foreach ($this->slotsFor($day) as $t => $e) {
                if (isset($grid[$day][$t]) && $grid[$day][$t] && $grid[$day][$t]['teacher_id'] == $teacherId) {
                    $dayCount++;
                }
            }
            if ($dayCount >= (int) $pref['max_periods_per_day']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Length of the contiguous run of consecutive periods the teacher would
     * have on this day if placed at $time (across sections + this grid).
     */
    private function consecutiveRunLength(int $day, string $time, int $teacherId, array $grid): int
    {
        $slotKeys = array_keys($this->slotsFor($day));
        $indices = [];

        if (isset($this->busyMap[$teacherId][$day])) {
            foreach (array_keys($this->busyMap[$teacherId][$day]) as $t) {
                $idx = array_search($t, $slotKeys, true);
                if ($idx !== false) $indices[$idx] = true;
            }
        }
        foreach ($this->slotsFor($day) as $t => $e) {
            if (isset($grid[$day][$t]) && $grid[$day][$t] && $grid[$day][$t]['teacher_id'] == $teacherId) {
                $idx = array_search($t, $slotKeys, true);
                if ($idx !== false) $indices[$idx] = true;
            }
        }

        $candidate = array_search($time, $slotKeys, true);
        if ($candidate === false) return 0;
        $indices[$candidate] = true;

        $sorted = array_keys($indices);
        sort($sorted);

        // Simple contiguous segment containing the candidate index.
        $contains = array_search($candidate, $sorted, true);
        if ($contains === false) return 1;
        $run = 1;
        for ($k = $contains - 1; $k >= 0; $k--) {
            if ($sorted[$k] === $sorted[$k + 1] - 1) $run++;
            else break;
        }
        for ($k = $contains + 1; $k < count($sorted); $k++) {
            if ($sorted[$k] === $sorted[$k - 1] + 1) $run++;
            else break;
        }

        return (int) $run;
    }

    /**
     * Calculate a score for placing a period at a specific slot.
     * Higher = better. Soft constraints only.
     */
    private function calculateSlotScore(
        int $day,
        string $time,
        string $end,
        ?int $teacherId,
        array $teacherWorkload,
        int $daySlotCount,
        int $subjectDayCount,
        array $grid
    ): float {
        $score = 0;

        // Soft: prefer mornings (first half of slots get a bonus).
        // Kept small so it never overrides an explicit teacher preference.
        $slotIndex = array_search($time, array_keys($this->slotsFor($day)));
        if ($slotIndex !== false && $slotIndex < ceil(count($this->slotsFor($day)) / 2)) {
            $score += 4;
        }

        // Soft: balance student load across days.
        $score -= $daySlotCount * 3;

        // Soft: spread the same subject across days rather than stacking it,
        // but keep the penalty small so a teacher's daily window can still
        // be filled up to their max periods per day.
        $score -= $subjectDayCount * 2;

        if ($teacherId) {
            // Teacher preferences dominate placement decisions.
            $pref = $this->preferenceMap[$teacherId] ?? null;
            $avail = $this->availability[$teacherId][$day] ?? null;

            // Soft: teacher marked this day as preferred.
            if ($avail && $avail['is_preferred']) {
                $score += 25;
            }

            if ($pref) {
                // Soft: preferred working window. Slots inside the window get
                // a strong bonus, slots outside get a strong penalty (so the
                // window is respected whenever it doesn't clash with hard
                // constraints). Each bound applies on its own when set.
                $hasWindow = $pref['preferred_start_time'] || $pref['preferred_end_time'];
                $inWindow = true;
                if ($pref['preferred_start_time'] && $time < $pref['preferred_start_time']) {
                    $inWindow = false;
                }
                if ($pref['preferred_end_time'] && $end > $pref['preferred_end_time']) {
                    $inWindow = false;
                }
                if ($hasWindow) {
                    $score += $inWindow ? 20 : -20;
                }

                // Soft: placing here achieves the teacher's preferred
                // consecutive block length (strongly prefer adjacent slots).
                if (!empty($pref['preferred_consecutive'])) {
                    $run = $this->consecutiveRunLength($day, $time, $teacherId, $grid);
                    if ($run >= (int) $pref['preferred_consecutive']) {
                        $score += 12;
                    } elseif ($run >= 2) {
                        $score += 6;
                    }
                }
            }

            // Soft: balance teacher workload (all sections).
            // per_day is already derived from busyMap by getTeacherWorkload().
            $teacherDayCount = $teacherWorkload[$teacherId]['per_day'][$day] ?? 0;
            $score -= $teacherDayCount * 5;
        }

        // Soft: keep each day's lessons contiguous — heavily penalize any
        // empty slot that would sit BETWEEN this lesson and the lessons
        // already placed that day (prevents gaps inside the school day).
        $slotKeys = array_keys($this->slotsFor($day));
        $idx = array_search($time, $slotKeys, true);
        if ($idx !== false) {
            $placedIdx = [];
            foreach ($slotKeys as $i => $t) {
                if (isset($grid[$day][$t]) && $grid[$day][$t] !== null) $placedIdx[] = $i;
            }
            if (!empty($placedIdx)) {
                $gaps = 0;
                foreach ($placedIdx as $pi) {
                    $from = min($pi, $idx);
                    $to = max($pi, $idx);
                    for ($k = $from + 1; $k < $to; $k++) {
                        if (!isset($grid[$day][$slotKeys[$k]]) || $grid[$day][$slotKeys[$k]] === null) $gaps++;
                    }
                }
                $score -= $gaps * 6;
                // Small bonus for extending the block directly adjacent.
                $adjacent = in_array($idx - 1, $placedIdx, true) || in_array($idx + 1, $placedIdx, true);
                if ($adjacent) $score += 3;
            } else {
                // First lesson of the day: prefer the earliest slot so the
                // day starts packed from the morning.
                $score -= $idx;
            }
        }

        // Variety across runs.
        $score += mt_rand(0, 5);

        return $score;
    }

    private function countAvailableSlots(array $grid, array $info): int
    {
        $count = 0;
        foreach ($this->daysInWeek as $day) {
            foreach ($this->slotsFor($day) as $time => $end) {
                if ($grid[$day][$time] === null) $count++;
            }
        }
        return max($count, 1);
    }

    private function countDaySlots(array $grid, int $day): int
    {
        $count = 0;
        foreach ($this->slotsFor($day) as $time => $end) {
            if (isset($grid[$day][$time]) && $grid[$day][$time] !== null) $count++;
        }
        return $count;
    }

    private function countSubjectInDay(array $grid, int $day, int $subjectId): int
    {
        $count = 0;
        foreach ($this->slotsFor($day) as $time => $end) {
            if (isset($grid[$day][$time]) && $grid[$day][$time] && $grid[$day][$time]['subject_id'] == $subjectId) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Convert the grid to an array of program records.
     */
    private function gridToPrograms(array $grid, int $sectionId, ?int $scenarioId): array
    {
        $programs = [];
        foreach ($grid as $day => $slots) {
            foreach ($slots as $time => $cell) {
                if ($cell === null) continue;
                $end = $this->slotsFor((int) $day)[$time] ?? $time;
                $programs[] = [
                    'section_id' => $sectionId,
                    'day_of_week' => $day,
                    'start_time' => $time . ':00',
                    'end_time' => $end . ':00',
                    'subject_id' => $cell['subject_id'],
                    'teacher_id' => $cell['teacher_id'],
                    'scenario_id' => $scenarioId,
                    'is_active' => true,
                ];
            }
        }
        return $programs;
    }

    private function countDaysUsed(array $grid): int
    {
        $count = 0;
        foreach ($grid as $day => $slots) {
            foreach ($slots as $cell) {
                if ($cell !== null) {
                    $count++;
                    break;
                }
            }
        }
        return $count;
    }

    private function calculateTeacherUtilization(array $teacherWorkload): array
    {
        $utilization = [];
        foreach ($teacherWorkload as $tid => $wl) {
            $utilization[$tid] = $wl['total'];
        }
        return $utilization;
    }

    /**
     * Generate warnings about the generated timetable.
     */
    private function generateWarnings(array $grid, array $teacherWorkload, Collection $requirements, ?int $classroomCapacity, int $studentCount): array
    {
        $warnings = [];

        // Unmet subject requirements.
        foreach ($requirements as $req) {
            $placed = 0;
            foreach ($grid as $day => $slots) {
                foreach ($slots as $cell) {
                    if ($cell && $cell['subject_id'] == $req->subject_id) $placed++;
                }
            }
            if ($placed < $req->periods_per_week) {
                $name = $req->subject ? $req->subject->Name : 'Unknown';
                if (is_array($name)) $name = $name['ar'] ?? $name['en'] ?? 'Unknown';
                $warnings[] = "المادة \"{$name}\" مطلوب {$req->periods_per_week} حصة لكن تم وضع {$placed} فقط";
            }
        }

        // Yearly-plan consistency: flag weekly counts that deviate from the
        // weekly average implied by total_periods_per_year.
        $planService = new SubjectYearlyPlanService();
        foreach ($planService->mismatchMessages($requirements) as $message) {
            $warnings[] = $message;
        }

        // Teacher overload.
        foreach ($teacherWorkload as $tid => $wl) {
            if ($wl['total'] > 30) {
                $warnings[] = "المعلم {$tid} عليه {$wl['total']} حصة (الأحمال مرتفعة)";
            }
            foreach ($wl['per_day'] as $day => $count) {
                if ($count > 7) {
                    $warnings[] = "المعلم {$tid} لديه {$count} حصص في اليوم {$this->dayName($day)} (أحمال مرتفعة)";
                }
            }
        }

        // Classroom capacity overflow.
        if ($classroomCapacity && $studentCount > $classroomCapacity) {
            $warnings[] = "سعة القاعة ({$classroomCapacity}) أقل من عدد الطلاب ({$studentCount})";
        }

        return $warnings;
    }

    private function dayName(int $day): string
    {
        $days = [0 => 'الأحد', 1 => 'الاثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء', 4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت'];
        return $days[$day] ?? "اليوم {$day}";
    }
}