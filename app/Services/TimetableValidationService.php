<?php

namespace App\Services;

use App\Models\WeeklyProgram;
use App\Models\SubjectWeeklyRequirement;
use App\Models\Teacher;
use App\Models\Section;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\TeacherWeeklySchedule;
use App\Models\TeacherPreference;
use App\Models\SchoolScheduleSetting;
use Illuminate\Support\Facades\DB;

class TimetableValidationService
{
    private $errors = [];
    private $warnings = [];
    private $checkedCounts = [
        'teacher_conflicts' => 0,
        'class_conflicts' => 0,
        'room_conflicts' => 0,
        'missing_periods' => 0,
        'workload_violations' => 0,
        'availability_violations' => 0,
        'availability_breaks' => 0,
        'break_overlaps' => 0,
        'consecutive_violations' => 0,
        'capacity_violations' => 0,
        'requirement_violations' => 0,
        'yearly_plan_violations' => 0,
    ];

    /**
     * Validate a timetable for a section.
     *
     * @param int $sectionId
     * @param int|null $scenarioId
     * @return array ['valid' => bool, 'errors' => [], 'warnings' => [], 'summary' => []]
     */
    public function validate(int $sectionId, ?int $scenarioId = null): array
    {
        $this->errors = [];
        $this->warnings = [];
        $this->checkedCounts = array_fill_keys(array_keys($this->checkedCounts), 0);

        $programs = $this->getPrograms($sectionId, $scenarioId);

        if (empty($programs)) {
            $this->warnings[] = 'الجدول فارغ - لا توجد حصص مسجلة';
            return $this->buildResult();
        }

        $this->checkTeacherConflicts($programs);
        $this->checkClassConflicts($programs, $sectionId);
        $this->checkRoomConflicts($programs);
        $this->checkSubjectRequirements($sectionId, $programs);
        $this->checkYearlyPlan($sectionId);
        $this->checkExcessWorkload($programs);
        $this->checkAvailabilityViolations($programs);
        $this->checkScheduledAvailability($programs);
        $this->checkBreakOverlap($programs);
        $this->checkTeacherConsecutive($programs);
        $this->checkRoomCapacity($sectionId, $programs);
        $this->checkConsecutiveGaps($programs);
        $this->checkMissingPeriods($programs);

        return $this->buildResult();
    }

    /**
     * Get programs for validation.
     */
    private function getPrograms(int $sectionId, ?int $scenarioId): array
    {
        $query = WeeklyProgram::with(['section', 'subject', 'teacher'])
            ->where('section_id', $sectionId)
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time');

        if ($scenarioId) {
            $query->where('scenario_id', $scenarioId);
        }

        return $query->get()->toArray();
    }

    /**
     * Check for teacher scheduling conflicts.
     */
    private function checkTeacherConflicts(array $programs): void
    {
        $teacherSlots = [];
        foreach ($programs as $p) {
            $tid = $p['teacher_id'] ?? null;
            if (!$tid) continue;

            $key = "{$tid}_{$p['day_of_week']}_{$p['start_time']}";
            if (isset($teacherSlots[$key])) {
                $teacherName = $this->nameOf($p['teacher'] ?? null);
                $subjectName = $this->nameOf($p['subject'] ?? null);
                $dayName = $this->getDayName($p['day_of_week']);

                $this->errors[] = [
                    'type' => 'teacher_conflict',
                    'message' => "تعارض: المعلم \"{$teacherName}\" مقرر له حصص متزامنتين في {$dayName} {$p['start_time']}",
                    'details' => [
                        'teacher_id' => $tid,
                        'day_of_week' => $p['day_of_week'],
                        'time' => $p['start_time'],
                        'subject1' => $teacherSlots[$key]['subject'],
                        'subject2' => $subjectName,
                    ],
                ];
                $this->checkedCounts['teacher_conflicts']++;
            } else {
                $subjectName = $this->nameOf($p['subject'] ?? null);
                $teacherSlots[$key] = ['subject' => $subjectName];
            }
        }
    }

    /**
     * Check for class/section conflicts.
     */
    private function checkClassConflicts(array $programs, int $sectionId): void
    {
        $sectionSlots = [];
        foreach ($programs as $p) {
            $key = "{$p['day_of_week']}_{$p['start_time']}";
            if (isset($sectionSlots[$key])) {
                $this->errors[] = [
                    'type' => 'class_conflict',
                    'message' => "تعارض في القسم: حصصتان مقررتان في نفس الوقت في {$this->getDayName($p['day_of_week'])} {$p['start_time']}",
                    'details' => [
                        'section_id' => $sectionId,
                        'day_of_week' => $p['day_of_week'],
                        'time' => $p['start_time'],
                    ],
                ];
                $this->checkedCounts['class_conflicts']++;
            } else {
                $sectionSlots[$key] = true;
            }
        }
    }

    /**
     * Check for room/location conflicts.
     */
    private function checkRoomConflicts(array $programs): void
    {
        $roomSlots = [];
        foreach ($programs as $p) {
            $location = $p['location'] ?? null;
            if (!$location) continue;

            $key = "{$location}_{$p['day_of_week']}_{$p['start_time']}";
            if (isset($roomSlots[$key])) {
                $this->errors[] = [
                    'type' => 'room_conflict',
                    'message' => "تعارض في القاعة \"{$location}\": حصصتان في {$this->getDayName($p['day_of_week'])} {$p['start_time']}",
                    'details' => [
                        'location' => $location,
                        'day_of_week' => $p['day_of_week'],
                        'time' => $p['start_time'],
                    ],
                ];
                $this->checkedCounts['room_conflicts']++;
            } else {
                $roomSlots[$key] = true;
            }
        }
    }

    /**
     * Check if subject weekly requirements are met.
     */
    private function checkSubjectRequirements(int $sectionId, array $programs): void
    {
        $section = Section::find($sectionId);
        if (!$section) return;

        $requirements = SubjectWeeklyRequirement::with('subject')
            ->where('grade_id', $section->Grade_id)
            ->where(function ($q) use ($section) {
                $q->where('classroom_id', $section->Class_id)
                    ->orWhereNull('classroom_id');
            })
            ->get();

        $subjectCounts = [];
        foreach ($programs as $p) {
            $sid = $p['subject_id'];
            $subjectCounts[$sid] = ($subjectCounts[$sid] ?? 0) + 1;
        }

        foreach ($requirements as $req) {
            $actual = $subjectCounts[$req->subject_id] ?? 0;
            $subjectName = $this->nameOf($req->subject ?? null);

            if ($actual < $req->periods_per_week) {
                $this->errors[] = [
                    'type' => 'missing_periods',
                    'message' => "المادة \"{$subjectName}\" مطلوب {$req->periods_per_week} حصة لكن يوجد {$actual} فقط",
                    'details' => [
                        'subject_id' => $req->subject_id,
                        'required' => $req->periods_per_week,
                        'actual' => $actual,
                    ],
                ];
                $this->checkedCounts['missing_periods']++;
            } elseif ($actual > $req->periods_per_week) {
                $this->warnings[] = "المادة \"{$subjectName}\" لها {$actual} حصة لكن المتطلب {$req->periods_per_week} فقط";
            }
        }
    }

    /**
     * Warn when periods_per_week deviates from the weekly average implied by
     * the subject's total yearly lessons (total_periods_per_year).
     */
    private function checkYearlyPlan(int $sectionId): void
    {
        $section = Section::find($sectionId);
        if (!$section) return;

        $planService = new SubjectYearlyPlanService();
        $warnings = $planService->mismatchesForGrade($section->Grade_id, $section->Class_id);

        foreach ($warnings as $message) {
            $this->warnings[] = $message;
            $this->checkedCounts['yearly_plan_violations']++;
        }
    }

    /**
     * Check for excessive teacher workload.
     */
    private function checkExcessWorkload(array $programs): void
    {
        $teacherDaily = [];
        $teacherTotal = [];

        foreach ($programs as $p) {
            $tid = $p['teacher_id'] ?? null;
            if (!$tid) continue;

            $teacherTotal[$tid] = ($teacherTotal[$tid] ?? 0) + 1;
            $day = $p['day_of_week'];
            $teacherDaily[$tid][$day] = ($teacherDaily[$tid][$day] ?? 0) + 1;
        }

        foreach ($teacherTotal as $tid => $total) {
            if ($total > 30) {
                $this->warnings[] = "المعلم {$tid} لديه {$total} حصة أسبوعياً (الأحمال مرتفعة جداً)";
                $this->checkedCounts['workload_violations']++;
            }
        }

        foreach ($teacherDaily as $tid => $days) {
            foreach ($days as $day => $count) {
                if ($count > 7) {
                    $this->warnings[] = "المعلم {$tid} لديه {$count} حصص في {$this->getDayName($day)} (أحمال مرتفعة)";
                }
            }
        }
    }

    /**
     * Soft warning: teacher not enrolled in the section's teacher list.
     */
    private function checkAvailabilityViolations(array $programs): void
    {
        $sectionId = $programs[0]['section_id'] ?? null;
        if (!$sectionId) return;

        $sectionTeachers = DB::table('teacher_section')
            ->where('section_id', $sectionId)
            ->pluck('teacher_id')
            ->toArray();

        foreach ($programs as $p) {
            $tid = $p['teacher_id'] ?? null;
            if (!$tid) continue;

            if (!in_array($tid, $sectionTeachers)) {
                $teacherName = $this->nameOf($p['teacher'] ?? null);
                $this->warnings[] = "المعلم \"{$teacherName}\" غير مسجل في قائمة معلمي هذا القسم";
                $this->checkedCounts['availability_violations']++;
            }
        }
    }

    /**
     * HARD: real availability check based on the teacher's weekly schedule
     * (unavailable days, working window) and forbidden (avoid) days.
     */
    private function checkScheduledAvailability(array $programs): void
    {
        $schedules = TeacherWeeklySchedule::all()->groupBy('teacher_id');
        $prefs = TeacherPreference::select('teacher_id', 'avoid_days')->get()->keyBy('teacher_id');

        foreach ($programs as $p) {
            $tid = $p['teacher_id'] ?? null;
            if (!$tid) continue;

            $subjectName = $this->nameOf($p['subject'] ?? null);
            $teacherName = $this->nameOf($p['teacher'] ?? null);
            $day = (int) $p['day_of_week'];
            $time = substr($p['start_time'], 0, 5);
            $end = substr($p['end_time'], 0, 5);
            $dayName = $this->getDayName($day);

            // HARD: teacher forbade this day entirely.
            if (isset($prefs[$tid]) && is_array($prefs[$tid]->avoid_days)) {
                $avoidDays = array_map('intval', $prefs[$tid]->avoid_days);
                if (in_array($day, $avoidDays, true)) {
                    $this->errors[] = [
                        'type' => 'availability_break',
                        'message' => "لا يمكن جدولة {$subjectName} في {$dayName} {$time} لأن المعلم \"{$teacherName}\" غير متاح (يوم ممنوع)",
                        'details' => [
                            'teacher_id' => $tid,
                            'day_of_week' => $day,
                            'time' => $p['start_time'],
                        ],
                    ];
                    $this->checkedCounts['availability_breaks']++;
                    continue;
                }
            }

            // No schedule recorded for this teacher -> treat as available.
            if (!isset($schedules[$tid])) continue;

            $row = collect($schedules[$tid])->firstWhere('day_of_week', $day);
            if (!$row) continue;

            if (!$row->is_available) {
                $this->errors[] = [
                    'type' => 'availability_break',
                    'message' => "لا يمكن جدولة {$subjectName} في {$dayName} {$time} لأن المعلم \"{$teacherName}\" غير متاح في هذا اليوم",
                    'details' => [
                        'teacher_id' => $tid,
                        'day_of_week' => $day,
                        'time' => $p['start_time'],
                    ],
                ];
                $this->checkedCounts['availability_breaks']++;
                continue;
            }

            $windowStart = substr((string) $row->start_time, 0, 5);
            $windowEnd = substr((string) $row->end_time, 0, 5);
            if (($windowStart && $time < $windowStart) || ($windowEnd && $end > $windowEnd)) {
                $this->errors[] = [
                    'type' => 'availability_break',
                    'message' => "لا يمكن جدولة {$subjectName} في {$dayName} {$time} لأن المعلم \"{$teacherName}\" خارج نطاق عمله ({$windowStart} - {$windowEnd})",
                    'details' => [
                        'teacher_id' => $tid,
                        'day_of_week' => $day,
                        'time' => $p['start_time'],
                    ],
                ];
                $this->checkedCounts['availability_breaks']++;
            }
        }
    }

    /**
     * HARD: any program overlapping a school break period.
     */
    private function checkBreakOverlap(array $programs): void
    {
        $settings = SchoolScheduleSetting::first();
        if (!$settings || !is_array($settings->breaks) || empty($settings->breaks)) return;

        $breaks = $settings->breaks;
        foreach ($programs as $p) {
            $time = substr($p['start_time'], 0, 5);
            $end = substr($p['end_time'], 0, 5);
            $subjectName = $this->nameOf($p['subject'] ?? null);

            foreach ($breaks as $break) {
                if (empty($break['start']) || empty($break['end'])) continue;
                $overlaps = $break['start'] < $end && $break['end'] > $time;
                if ($overlaps) {
                    $breakName = $break['name'] ?? 'الاستراحة';
                    $this->errors[] = [
                        'type' => 'break_overlap',
                        'message' => "الحصة \"{$subjectName}\" في {$this->getDayName($p['day_of_week'])} {$time} تتداخل مع فترة {$breakName}",
                        'details' => [
                            'day_of_week' => $p['day_of_week'],
                            'time' => $p['start_time'],
                            'break_start' => $break['start'],
                            'break_end' => $break['end'],
                        ],
                    ];
                    $this->checkedCounts['break_overlaps']++;
                    break;
                }
            }
        }
    }

    /**
     * HARD: teacher consecutive periods per day may not exceed their max_consecutive.
     */
    private function checkTeacherConsecutive(array $programs): void
    {
        $maxMap = TeacherPreference::whereNotNull('max_consecutive')
            ->pluck('max_consecutive', 'teacher_id');

        if ($maxMap->isEmpty()) return;

        $teacherStarts = [];
        foreach ($programs as $p) {
            $tid = $p['teacher_id'] ?? null;
            if (!$tid || !$maxMap->has($tid)) continue;
            $teacherStarts[$tid][$p['day_of_week']][] = substr($p['start_time'], 0, 5);
        }

        $slotKeys = $this->slotKeys();
        foreach ($teacherStarts as $tid => $days) {
            foreach ($days as $day => $starts) {
                $indices = array_map(function ($t) use ($slotKeys) {
                    return array_search($t, $slotKeys, true);
                }, $starts);
                $indices = array_filter($indices, fn($i) => $i !== false);
                sort($indices);

                $run = 1;
                $maxRun = 1;
                for ($k = 1; $k < count($indices); $k++) {
                    if ($indices[$k] === $indices[$k - 1] + 1) {
                        $run++;
                        $maxRun = max($maxRun, $run);
                    } else {
                        $run = 1;
                    }
                }

                if ($maxRun > (int) $maxMap[$tid]) {
                    $teacherName = $this->findTeacherName($programs, $tid);
                    $this->errors[] = [
                        'type' => 'consecutive_violation',
                        'message' => "المعلم \"{$teacherName}\" لديه {$maxRun} حصص متتالية في {$this->getDayName($day)} (الحد الأقصى {$maxMap[$tid]})",
                        'details' => [
                            'teacher_id' => $tid,
                            'day_of_week' => $day,
                            'consecutive' => $maxRun,
                            'max' => (int) $maxMap[$tid],
                        ],
                    ];
                    $this->checkedCounts['consecutive_violations']++;
                }
            }
        }
    }

    /**
     * HARD: classroom capacity vs number of students in the section.
     */
    private function checkRoomCapacity(int $sectionId, array $programs): void
    {
        $section = Section::with('My_classs')->find($sectionId);
        if (!$section || !$section->My_classs) return;

        $capacity = $section->My_classs->capacity;
        if (!$capacity) return;

        $students = Student::where('section_id', $sectionId)->count();
        if ($students > (int) $capacity) {
            $this->errors[] = [
                'type' => 'capacity_violation',
                'message' => "سعة القاعة \"{$section->My_classs->Name_Class}\" ({$capacity}) أقل من عدد الطلاب في القسم ({$students})",
                'details' => [
                    'section_id' => $sectionId,
                    'capacity' => (int) $capacity,
                    'students' => $students,
                ],
            ];
            $this->checkedCounts['capacity_violations']++;
        }
    }

    /**
     * Check for unnecessary gaps in the student schedule.
     */
    private function checkConsecutiveGaps(array $programs): void
    {
        $dayPrograms = [];
        foreach ($programs as $p) {
            $dayPrograms[$p['day_of_week']][] = $p;
        }

        foreach ($dayPrograms as $day => $dayPs) {
            usort($dayPs, fn($a, $b) => strcmp($a['start_time'], $b['start_time']));

            for ($i = 0; $i < count($dayPs) - 1; $i++) {
                $current = $dayPs[$i];
                $next = $dayPs[$i + 1];

                $currentEnd = strtotime($current['end_time']);
                $nextStart = strtotime($next['start_time']);
                $gapMinutes = ($nextStart - $currentEnd) / 60;

                if ($gapMinutes > 60) {
                    $this->warnings[] = "فترة انتظار طويلة ({$gapMinutes} دقيقة) في {$this->getDayName($day)} بين {$current['end_time']} و {$next['start_time']}";
                }
            }
        }
    }

    /**
     * Check for completely empty days (respecting school schedule settings).
     */
    private function checkMissingPeriods(array $programs): void
    {
        $settings = SchoolScheduleSetting::first();
        $allDays = ($settings && is_array($settings->days) && count($settings->days) > 0)
            ? array_map('intval', $settings->days)
            : [0, 1, 2, 3, 4];

        $daysWithClasses = array_unique(array_column($programs, 'day_of_week'));

        $missingDays = array_diff($allDays, $daysWithClasses);
        foreach ($missingDays as $day) {
            $this->warnings[] = "لا توجد حصص في {$this->getDayName($day)}";
        }
    }

    private function buildResult(): array
    {
        return [
            'valid' => empty($this->errors),
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'summary' => [
                'total_errors' => count($this->errors),
                'total_warnings' => count($this->warnings),
                'teacher_conflicts' => $this->checkedCounts['teacher_conflicts'],
                'class_conflicts' => $this->checkedCounts['class_conflicts'],
                'room_conflicts' => $this->checkedCounts['room_conflicts'],
                'missing_periods' => $this->checkedCounts['missing_periods'],
                'workload_violations' => $this->checkedCounts['workload_violations'],
                'availability_violations' => $this->checkedCounts['availability_violations'],
                'availability_breaks' => $this->checkedCounts['availability_breaks'],
                'break_overlaps' => $this->checkedCounts['break_overlaps'],
                'consecutive_violations' => $this->checkedCounts['consecutive_violations'],
                'capacity_violations' => $this->checkedCounts['capacity_violations'],
                'yearly_plan_violations' => $this->checkedCounts['yearly_plan_violations'],
            ],
        ];
    }

    private function getDayName(int $day): string
    {
        $days = [0 => 'الأحد', 1 => 'الاثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء', 4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت'];
        return $days[$day] ?? "اليوم {$day}";
    }

    private function nameOf($entity): string
    {
        if (!$entity) return 'Unknown';
        $name = $entity['name'] ?? ($entity['Name'] ?? 'Unknown');
        if (is_array($name)) {
            return $name['ar'] ?? $name['en'] ?? 'Unknown';
        }
        return $name;
    }

    private function findTeacherName(array $programs, int $teacherId): string
    {
        foreach ($programs as $p) {
            if (($p['teacher_id'] ?? null) == $teacherId) {
                return $this->nameOf($p['teacher'] ?? null);
            }
        }
        return '#' . $teacherId;
    }

    private function slotKeys(): array
    {
        $default = [
            '08:00' => '08:45',
            '08:50' => '09:35',
            '09:40' => '10:25',
            '10:35' => '11:20',
            '11:25' => '12:10',
            '12:15' => '13:00',
            '13:30' => '14:15',
            '14:20' => '15:05',
        ];
        $settings = SchoolScheduleSetting::first();
        if ($settings && is_array($settings->breaks) && !empty($settings->breaks)) {
            foreach ($settings->breaks as $break) {
                if (empty($break['start']) || empty($break['end'])) continue;
                foreach ($default as $start => $end) {
                    if ($break['start'] < $end && $break['end'] > $start) {
                        unset($default[$start]);
                    }
                }
            }
        }
        return array_keys($default);
    }
}