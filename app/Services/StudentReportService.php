<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Degree;
use App\Models\Evaluation;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\Student;
use App\Models\StudentMonthlyReport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StudentReportService
{
    protected const LEVEL_ORDER = ['bad' => 1, 'good' => 2, 'very_good' => 3, 'excellent' => 4];

    protected const MONTHS_AR = [
        1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل', 5 => 'مايو', 6 => 'يونيو',
        7 => 'يوليو', 8 => 'أغسطس', 9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
    ];

    protected const MONTHS_EN = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
        7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];

    /**
     * Generate reports for all active students for the given month/year.
     *
     * `$schoolId` is not optional in practice: without it the loop walks every
     * `students` row in the database, so one school's approval would generate
     * (and push notifications for) another school's students. Callers on a
     * single-student path pass the school through and the student id instead.
     *
     * @return array{generated: int, failed: int, skipped: int, errors: array}
     */
    public function generateForAll(int $month, int $year, bool $sendNotifications = true, ?int $schoolId = null): array
    {
        $students = Student::query()
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('id')
            ->get();

        $generated = 0;
        $failed = 0;
        $skipped = 0;
        $errors = [];

        foreach ($students as $student) {
            try {
                $report = $this->generateForStudent($student->id, $month, $year, $sendNotifications);
                if ($report === null) {
                    $skipped++;
                } else {
                    $generated++;
                }
            } catch (\Exception $e) {
                $failed++;
                $errors[] = "Student #{$student->id}: {$e->getMessage()}";
            }
        }

        return [
            'generated' => $generated,
            'failed' => $failed,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Generate (or refresh) a single student's report for the given month/year.
     *
     * Returns null when there is no data at all for the student in that period.
     */
    public function generateForStudent(int $studentId, int $month, int $year, bool $sendNotifications = true): ?StudentMonthlyReport
    {
        $attendance = $this->calculateAttendance($studentId, $month, $year);
        $evaluations = $this->calculateEvaluations($studentId, $month, $year);
        $examGrades = $this->calculateExamGrades($studentId, $month, $year);
        $quizScores = $this->calculateQuizScores($studentId, $month, $year);

        $hasData = $attendance['total_days'] > 0
            || $evaluations['total'] > 0
            || $examGrades['overall_percentage'] > 0
            || $quizScores['total'] > 0;

        if (! $hasData) {
            return null;
        }

        $progressScore = $this->computeProgressScore(
            $attendance['rate'],
            $evaluations['average_level'],
            $examGrades['overall_percentage']
        );

        $report = StudentMonthlyReport::updateOrCreate(
            [
                'student_id' => $studentId,
                'month' => $month,
                'year' => $year,
            ],
            [
                'attendance_total_days' => $attendance['total_days'],
                'attendance_present' => $attendance['present'],
                'attendance_absent' => $attendance['absent'],
                'attendance_rate' => $attendance['rate'],
                'evaluations_total' => $evaluations['total'],
                'evaluations_average_level' => $evaluations['average_level'],
                'evaluations_breakdown' => $evaluations['breakdown'],
                'exam_subjects' => $examGrades['subjects'],
                'exam_overall_percentage' => $examGrades['overall_percentage'],
                'quizzes_total' => $quizScores['total'],
                'quiz_average_percentage' => $quizScores['average_percentage'],
                'progress_score' => $progressScore,
                'previous_month_score' => $this->previousMonthScore($studentId, $month, $year),
                'trend' => $this->calculateTrend($studentId, $month, $year, $progressScore),
                'teacher_notes' => $evaluations['notes'],
                'generated_at' => now(),
            ]
        );

        if ($sendNotifications) {
            $this->sendReportNotifications($report);
        }

        return $report;
    }

    /**
     * Compute the 0-100 progress score combining attendance (30%), teacher
     * evaluations (40%) and paper exam scores (30%).
     */
    public function computeProgressScore(float $attendanceRate, float $evalAverageLevel, float $examPercentage): float
    {
        $evaluationScore = $evalAverageLevel / 4 * 100;

        $score = ($attendanceRate * 0.3) + ($evaluationScore * 0.4) + ($examPercentage * 0.3);

        return round(max(0, min(100, $score)), 2);
    }

    public function calculateAttendance(int $studentId, int $month, int $year): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = Carbon::create($year, $month, 1)->endOfMonth();

        $rows = Attendance::where('student_id', $studentId)
            ->whereBetween('attendence_date', [$start->toDateString(), $end->toDateString()])
            ->get(['attendence_status']);

        $total = $rows->count();
        $present = $rows->where('attendence_status', true)->count();
        $absent = $rows->where('attendence_status', false)->count();

        return [
            'total_days' => $total,
            'present' => $present,
            'absent' => $absent,
            'rate' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
        ];
    }

    public function calculateEvaluations(int $studentId, int $month, int $year): array
    {
        $evaluations = Evaluation::with(['subject'])
            ->where('student_id', $studentId)
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        $total = $evaluations->count();

        $counts = ['bad' => 0, 'good' => 0, 'very_good' => 0, 'excellent' => 0];
        foreach ($evaluations as $evaluation) {
            $counts[$evaluation->evaluation_level] = ($counts[$evaluation->evaluation_level] ?? 0) + 1;
        }

        $averageLevel = $total > 0 ? round($evaluations->avg('level_value'), 2) : 0;

        return [
            'total' => $total,
            'average_level' => $averageLevel,
            'breakdown' => [
                'counts' => $counts,
                'percentages' => [
                    'bad' => $total > 0 ? round(($counts['bad'] / $total) * 100, 2) : 0,
                    'good' => $total > 0 ? round(($counts['good'] / $total) * 100, 2) : 0,
                    'very_good' => $total > 0 ? round(($counts['very_good'] / $total) * 100, 2) : 0,
                    'excellent' => $total > 0 ? round(($counts['excellent'] / $total) * 100, 2) : 0,
                ],
            ],
            'notes' => $evaluations->whereNotNull('notes')->map(function ($e) {
                return [
                    'subject_id' => $e->subject_id,
                    'subject' => $e->subject ? $this->nameOf($e->subject) : null,
                    'level' => $e->evaluation_level,
                    'notes' => $e->notes,
                ];
            })->values()->all(),
        ];
    }

    public function calculateExamGrades(int $studentId, int $month, int $year): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $end = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $rows = DB::table('paper_exam_marks as m')
            ->join('paper_exams as e', 'e.id', '=', 'm.paper_exam_id')
            ->join('subjects as s', 's.id', '=', 'e.subject_id')
            ->where('m.student_id', $studentId)
            ->where('e.status', 'published')
            ->whereBetween('e.exam_date', [$start, $end])
            ->selectRaw('e.subject_id as subject_id, s.Name as subject_name, AVG(m.marks_obtained) as avg_score, MAX(e.max_score) as max_score, COUNT(*) as exams_count')
            ->groupBy('e.subject_id', 's.Name')
            ->orderBy('avg_score', 'desc')
            ->get();

        $subjects = [];
        $totalPercentage = 0;
        $weighted = 0;

        foreach ($rows as $row) {
            $name = $row->subject_name;
            if (is_array($name)) {
                $name = $this->pickTranslation($name);
            } elseif (is_string($name) && str_starts_with(trim($name), '{')) {
                $decoded = json_decode($name, true);
                $name = is_array($decoded) ? $this->pickTranslation($decoded) : '—';
            }
            $max = (float) $row->max_score ?: 100;
            $avg = (float) $row->avg_score;
            $percentage = $max > 0 ? round(($avg / $max) * 100, 1) : 0;

            $subjects[] = [
                'subject_id' => (int) $row->subject_id,
                'subject_name' => $name,
                'avg_score' => round($avg, 1),
                'max_score' => $max,
                'percentage' => $percentage,
                'exams_count' => (int) $row->exams_count,
            ];

            $totalPercentage += $percentage;
            $weighted++;
        }

        return [
            'overall_percentage' => $weighted > 0 ? round($totalPercentage / $weighted, 2) : 0,
            'subjects' => $subjects,
        ];
    }

    public function calculateQuizScores(int $studentId, int $month, int $year): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = Carbon::create($year, $month, 1)->endOfMonth();

        $degrees = Degree::with('quizze.questions')
            ->where('student_id', $studentId)
            ->where('status', 'submitted')
            ->whereBetween('submitted_at', [$start, $end])
            ->get();

        $percentages = [];

        foreach ($degrees as $degree) {
            $quiz = $degree->quizze;
            $maxScore = $quiz ? $quiz->questions->sum('score') : 0;
            if ($maxScore > 0) {
                $percentages[] = round(($degree->score / $maxScore) * 100, 1);
            }
        }

        $total = count($percentages);

        return [
            'total' => $total,
            'average_percentage' => $total > 0 ? round(array_sum($percentages) / $total, 2) : 0,
        ];
    }

    public function previousMonthScore(int $studentId, int $month, int $year): ?float
    {
        $previous = $this->previousPeriod($month, $year);

        $report = StudentMonthlyReport::where('student_id', $studentId)
            ->where('month', $previous['month'])
            ->where('year', $previous['year'])
            ->first();

        return $report ? (float) $report->progress_score : null;
    }

    public function calculateTrend(int $studentId, int $month, int $year, float $currentScore): string
    {
        $previousScore = $this->previousMonthScore($studentId, $month, $year);

        if ($previousScore === null) {
            return 'new';
        }

        if ($currentScore - $previousScore > 2) {
            return 'improved';
        }

        if ($previousScore - $currentScore > 2) {
            return 'declined';
        }

        return 'stable';
    }

    /**
     * Create an in-app notification (student + parent) and fire an FCM push.
     */
    public function sendReportNotifications(StudentMonthlyReport $report): array
    {
        $student = Student::withTrashed()->find($report->student_id);

        if (! $student) {
            return ['success' => false, 'message' => 'Student not found'];
        }

        $studentName = $this->nameOf($student, 'name') ?: '—';
        $monthName = self::MONTHS_AR[$report->month] ?? $report->month;
        $score = number_format($report->progress_score, 0);

        $trendLabel = match ($report->trend) {
            'improved' => 'تحسّن ملحوظ',
            'declined' => 'تراجع يحتاج متابعة',
            'stable' => 'استقرار في الأداء',
            default => 'تقرير شهري جديد',
        };

        $title = "تقرير شهر {$monthName} — {$studentName}";
        $body = "نسبة التقدم الكلية: {$score}% ({$trendLabel}).";

        $data = [
            'type' => 'student_monthly_report',
            'student_report_id' => $report->id,
            'student_id' => $student->id,
            'month' => $report->month,
            'year' => $report->year,
        ];

        DB::beginTransaction();

        try {
            $notification = Notification::create([
                'title' => $title,
                'body' => $body,
                'type' => 'targeted',
                'sender_id' => null,
                'sender_type' => null,
                'target_role' => null,
                'data' => $data,
            ]);

            $recipients = [];
            $now = now();

            $recipients[] = [
                'notification_id' => $notification->id,
                'recipient_id' => $student->id,
                'recipient_type' => 'App\Models\Student',
                'is_read' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($student->parent_id && $student->parent) {
                $recipients[] = [
                    'notification_id' => $notification->id,
                    'recipient_id' => $student->parent_id,
                    'recipient_type' => 'App\Models\My_Parent',
                    'is_read' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            NotificationRecipient::insert($recipients);

            DB::commit();

            $this->sendFcm($student, $title, $body, $data);

            return ['success' => true, 'notification' => $notification];
        } catch (\Exception $e) {
            DB::rollBack();

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Build the standard report payload shape shared by the API responses.
     */
    public function present(StudentMonthlyReport $report): array
    {
        $student = $report->student ?: Student::with(['grade', 'classroom', 'section'])->withTrashed()->find($report->student_id);

        return $this->shape([
            'id'                     => $report->id,
            'attendance_total_days'  => $report->attendance_total_days,
            'attendance_present'     => $report->attendance_present,
            'attendance_absent'      => $report->attendance_absent,
            'attendance_rate'        => $report->attendance_rate,
            'evaluations_total'      => $report->evaluations_total,
            'evaluations_average_level' => $report->evaluations_average_level,
            'evaluations_breakdown'  => $report->evaluations_breakdown,
            'exam_subjects'          => $report->exam_subjects,
            'exam_overall_percentage' => $report->exam_overall_percentage,
            'quizzes_total'          => $report->quizzes_total,
            'quiz_average_percentage' => $report->quiz_average_percentage,
            'progress_score'         => $report->progress_score,
            'previous_month_score'   => $report->previous_month_score,
            'trend'                  => $report->trend,
            'teacher_notes'          => $report->teacher_notes,
            'generated_at'           => $report->generated_at?->toDateTimeString(),
        ], $student, (int) $report->month, (int) $report->year);
    }

    /**
     * Render one report in the API shape, from already-resolved values.
     *
     * `present()` (a saved report) and `previewForStudent()` (a not-yet-saved
     * one) both funnel through here, so an approver reviewing the queue sees
     * byte-identical numbers to the ones the student will receive.
     *
     * @param  array<string, mixed>  $values
     */
    private function shape(array $values, ?Student $student, int $month, int $year): array
    {
        $isArabic = app()->getLocale() === 'ar';
        $months = $isArabic ? self::MONTHS_AR : self::MONTHS_EN;
        $breakdown = $values['evaluations_breakdown'] ?? [];

        return [
            'id'         => $values['id'] ?? null,
            'month'      => $month,
            'year'       => $year,
            'month_name' => $months[$month] ?? ($isArabic ? "شهر {$month}" : "Month {$month}"),
            'student'    => $student ? [
                'id'             => $student->id,
                'name'           => $this->nameOf($student, 'name'),
                'grade_name'     => $student->grade ? $this->nameOf($student->grade) : null,
                'classroom_name' => $student->classroom ? $this->nameOf($student->classroom) : null,
                'section_name'   => $student->section ? $this->nameOf($student->section, 'Name_Section') : null,
            ] : null,
            'attendance' => [
                'total_days' => (int) ($values['attendance_total_days'] ?? 0),
                'present'    => (int) ($values['attendance_present'] ?? 0),
                'absent'     => (int) ($values['attendance_absent'] ?? 0),
                'rate'       => (float) ($values['attendance_rate'] ?? 0),
            ],
            'evaluations' => [
                'total'         => (int) ($values['evaluations_total'] ?? 0),
                'average_level' => (float) ($values['evaluations_average_level'] ?? 0),
                'breakdown'     => $breakdown ?: ['counts' => [], 'percentages' => []],
            ],
            'exams' => [
                'subjects'           => $this->localizedExamSubjects($values['exam_subjects'] ?? []),
                'overall_percentage' => (float) ($values['exam_overall_percentage'] ?? 0),
            ],
            'quizzes' => [
                'total'               => (int) ($values['quizzes_total'] ?? 0),
                'average_percentage' => (float) ($values['quiz_average_percentage'] ?? 0),
            ],
            'progress_score' => (float) ($values['progress_score'] ?? 0),
            'previous_month_score' => $values['previous_month_score'] !== null
                ? (float) $values['previous_month_score']
                : null,
            'trend'          => $values['trend'] ?? 'new',
            'teacher_notes'  => $this->localizedTeacherNotes($values['teacher_notes'] ?? []),
            'generated_at'   => $values['generated_at'] ?? null,
        ];
    }

    /**
     * The report `generateForStudent()` would produce, computed without writing
     * anything: no StudentMonthlyReport row, no notification, no FCM push.
     *
     * Returns null under exactly the same condition the generator returns null
     * (no data at all for the period), which is what lets the approval screen
     * tell the approver which students would be silently skipped.
     */
    public function previewForStudent(int $studentId, int $month, int $year): ?array
    {
        $attendance = $this->calculateAttendance($studentId, $month, $year);
        $evaluations = $this->calculateEvaluations($studentId, $month, $year);
        $examGrades = $this->calculateExamGrades($studentId, $month, $year);
        $quizScores = $this->calculateQuizScores($studentId, $month, $year);

        $hasData = $attendance['total_days'] > 0
            || $evaluations['total'] > 0
            || $examGrades['overall_percentage'] > 0
            || $quizScores['total'] > 0;

        if (! $hasData) {
            return null;
        }

        $progressScore = $this->computeProgressScore(
            $attendance['rate'],
            $evaluations['average_level'],
            $examGrades['overall_percentage']
        );

        $student = Student::with(['grade', 'classroom', 'section'])->withTrashed()->find($studentId);

        $preview = $this->shape([
            'attendance_total_days' => $attendance['total_days'],
            'attendance_present' => $attendance['present'],
            'attendance_absent' => $attendance['absent'],
            'attendance_rate' => $attendance['rate'],
            'evaluations_total' => $evaluations['total'],
            'evaluations_average_level' => $evaluations['average_level'],
            'evaluations_breakdown' => $evaluations['breakdown'],
            'exam_subjects' => $examGrades['subjects'],
            'exam_overall_percentage' => $examGrades['overall_percentage'],
            'quizzes_total' => $quizScores['total'],
            'quiz_average_percentage' => $quizScores['average_percentage'],
            'progress_score' => $progressScore,
            'previous_month_score' => $this->previousMonthScore($studentId, $month, $year),
            'trend' => $this->calculateTrend($studentId, $month, $year, $progressScore),
            'teacher_notes' => $evaluations['notes'],
        ], $student, $month, $year);

        // Not part of the persisted shape: tells the UI this row would produce a
        // report (and therefore two notifications) once approved.
        $preview['will_generate'] = true;

        return $preview;
    }

    /**
     * Which students `generateForAll()` would actually write a report for.
     *
     * `generateForAll()` walks every student row, so resolving that set with
     * four per-student queries would be unusable on a real school. This answers
     * the same question with four grouped queries and no writes.
     *
     * @return array{with_data: array<int, true>, attendance: array<int, array{total:int,present:int,absent:int,rate:float}>}
     */
    public function resolveScope(?int $schoolId, int $month, int $year): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $end = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $studentIds = Student::query()
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->pluck('id');

        $withData = [];

        // Attendance is the one source that also feeds the summary averages.
        $attendance = DB::table('attendances')
            ->whereIn('student_id', $studentIds)
            ->whereBetween('attendence_date', [$start, $end])
            ->selectRaw('student_id, COUNT(*) as total_days, SUM(attendence_status) as present')
            ->groupBy('student_id')
            ->get()
            ->mapWithKeys(function ($row) use (&$withData) {
                $withData[(int) $row->student_id] = true;

                $total = (int) $row->total_days;
                $present = (int) $row->present;

                return [(int) $row->student_id => [
                    'total'   => $total,
                    'present' => $present,
                    'absent'  => $total - $present,
                    'rate'    => $total > 0 ? round(($present / $total) * 100, 2) : 0,
                ]];
            })
            ->all();

        $markSources = [
            DB::table('evaluations')
                ->whereIn('student_id', $studentIds)
                ->where('month', $month)->where('year', $year),
            DB::table('paper_exam_marks as m')
                ->join('paper_exams as e', 'e.id', '=', 'm.paper_exam_id')
                ->whereIn('m.student_id', $studentIds)
                ->where('e.status', 'published')
                ->whereBetween('e.exam_date', [$start, $end]),
            DB::table('degrees')
                ->whereIn('student_id', $studentIds)
                ->where('status', 'submitted')
                ->whereBetween('submitted_at', [
                    Carbon::create($year, $month, 1)->startOfMonth(),
                    Carbon::create($year, $month, 1)->endOfMonth(),
                ]),
        ];

        foreach ($markSources as $query) {
            foreach ($query->distinct()->pluck('student_id') as $id) {
                $withData[(int) $id] = true;
            }
        }

        return ['with_data' => $withData, 'attendance' => $attendance];
    }

    /**
     * Students in scope for a bulk report run, as an id => name map.
     *
     * @return \Illuminate\Support\Collection<int, Student>
     */
    public function scopedStudents(?int $schoolId)
    {
        return Student::with(['grade', 'classroom', 'section'])
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('id')
            ->get();
    }

    /**
     * Resolve stored paper-exam subject names against the live Subject
     * model so the API always returns names in the current locale.
     */
    protected function localizedExamSubjects(array $subjects): array
    {
        foreach ($subjects as &$subject) {
            if (isset($subject['subject_id'])) {
                $live = \App\Models\Subject::find($subject['subject_id']);
                if ($live) {
                    $subject['subject_name'] = $this->nameOf($live);
                }
            }
        }

        return $subjects;
    }

    /**
     * Resolve stored teacher-note subject names against the live Subject
     * model (falling back to whatever was persisted).
     */
    protected function localizedTeacherNotes(array $notes): array
    {
        foreach ($notes as &$note) {
            if (isset($note['subject_id'])) {
                $live = \App\Models\Subject::find($note['subject_id']);
                if ($live) {
                    $note['subject'] = $this->nameOf($live);
                }
            }
        }

        return $notes;
    }

    public function nameOf($entity, string $key = 'Name'): string
    {
        if (! $entity) {
            return '—';
        }

        $name = $entity->$key ?? null;

        if (is_array($name)) {
            return $this->pickTranslation($name);
        }

        // Some Spatie columns come back as JSON strings (raw query access).
        if (is_string($name) && str_starts_with(trim($name), '{')) {
            $decoded = json_decode($name, true);
            if (is_array($decoded)) {
                return $this->pickTranslation($decoded);
            }
        }

        // Spatie model attribute: resolve the current locale translation.
        if (method_exists($entity, 'getTranslations') && method_exists($entity, 'getTranslation')) {
            try {
                $translation = $entity->getTranslation($key, app()->getLocale(), false);
                if (is_string($translation) && $translation !== '') {
                    return $translation;
                }
            } catch (\Throwable $e) {
                // fall through to the raw value
            }
        }

        return (string) $name;
    }

    /**
     * Pick the translation that matches the current app locale, falling
     * back to the other available locale.
     */
    protected function pickTranslation(array $values): string
    {
        $isArabic = app()->getLocale() === 'ar';

        if ($isArabic) {
            return $values['ar'] ?? $values['en'] ?? '—';
        }

        return $values['en'] ?? $values['ar'] ?? '—';
    }

    private function previousPeriod(int $month, int $year): array
    {
        if ($month === 1) {
            return ['month' => 12, 'year' => $year - 1];
        }

        return ['month' => $month - 1, 'year' => $year];
    }

    private function sendFcm(Student $student, string $title, string $body, array $data): void
    {
        try {
            $fcmService = new FcmNotificationService();

            $targets = [
                ['id' => $student->id, 'type' => 'App\Models\Student'],
            ];

            if ($student->parent_id) {
                $targets[] = ['id' => $student->parent_id, 'type' => 'App\Models\My_Parent'];
            }

            $fcmService->sendToUsers($targets, $title, $body, $data);
        } catch (\Exception $e) {
            \Log::warning("StudentReport FCM send failed: {$e->getMessage()}");
        }
    }
}