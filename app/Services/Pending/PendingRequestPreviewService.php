<?php

namespace App\Services\Pending;

use App\Models\PendingRequest;
use App\Models\PaperExam;
use App\Models\SalaryPayment;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\StudentReportService;
use Illuminate\Support\Facades\DB;

/**
 * Builds the read-only "what exactly will this do?" view of a queued request.
 *
 * The approval queue used to show a title, a description and a free-text reason,
 * which is not enough to sign off on a bulk action: approving "send the monthly
 * reports" would fire notifications for 200 students that the approver never
 * saw. Every previewer here answers three questions instead:
 *
 *   - `summary`  the headline numbers, over the WHOLE affected set, not one page
 *   - `rows`     the per-record detail the approver actually reads, paginated
 *   - `warnings` the things that make this request a bad idea right now
 *
 * A previewer must never write. It reuses the same read paths the matching
 * executor uses so the numbers shown are the numbers that will be applied.
 */
class PendingRequestPreviewService
{
    /** Rows returned per page when the caller does not ask for a size. */
    public const DEFAULT_PER_PAGE = 25;

    public const MAX_PER_PAGE = 100;

    public function __construct(private StudentReportService $reports = new StudentReportService)
    {
    }

    /**
     * @param  array{page?: int, per_page?: int, q?: string|null}  $options
     * @return array<string, mixed>|null  null when the action has no preview
     */
    public function build(PendingRequest $request, array $options = []): ?array
    {
        return match ($request->action) {
            PendingActionRegistry::SEND_REPORT => $this->sendReport($request, $options),
            PendingActionRegistry::SEND_MARKS => $this->sendMarks($request, $options),
            PendingActionRegistry::PAY_SALARY => $this->paySalary($request, $options),
            PendingActionRegistry::UPDATE_TEACHER => $this->updateTeacher($request, $options),
            default => $this->generic($request),
        };
    }

    // ------------------------------------------------------------------
    // إرسال التقارير الشهرية
    // ------------------------------------------------------------------

    /**
     * Every report that approving this request would create, one row per student.
     *
     * Students with no data for the period are listed too, flagged as skipped:
     * `generateForStudent()` returns null for them, so an approver has to be able
     * to see that "200 students" does not mean "200 notifications".
     */
    private function sendReport(PendingRequest $request, array $options): array
    {
        $payload = (array) ($request->payload ?? []);
        $month = (int) ($payload['month'] ?? now()->month);
        $year = (int) ($payload['year'] ?? now()->year);
        $studentId = $payload['student_id'] ?? null;

        $schoolId = $request->school_id;
        $scope = $this->reports->resolveScope($schoolId, $month, $year);

        $students = $studentId
            ? Student::with(['grade', 'classroom', 'section'])->where('id', (int) $studentId)->get()
            : $this->reports->scopedStudents($schoolId);

        // Single-student requests must not leak: a queued report for another
        // school's student shows as unavailable rather than as their data.
        if ($studentId && ($students->isEmpty() || $students->first()->school_id != $schoolId)) {
            return [
                'summary'   => [],
                'columns'   => [],
                'rows'      => [],
                'meta'      => $this->meta(1, 1, 0),
                'warnings'  => [$this->warn('ar', 'الطالب المرتبط بالطلب غير موجود في هذه المدرسة.')],
                'available' => false,
            ];
        }

        $withData = $scope['with_data'];
        $total = $students->count();
        $willGenerate = $students->filter(fn (Student $s) => isset($withData[$s->id]))->count();
        $willSkip = $total - $willGenerate;

        $attendanceRows = $scope['attendance'];
        $rates = [];
        foreach ($students as $student) {
            if (isset($attendanceRows[$student->id]) && $attendanceRows[$student->id]['total'] > 0) {
                $rates[] = $attendanceRows[$student->id]['rate'];
            }
        }

        $search = trim((string) ($options['q'] ?? ''));
        $filtered = $search === '' ? $students : $students->filter(
            fn (Student $s) => mb_stripos($this->reports->nameOf($s, 'name') ?? '', $search) !== false
                || str_contains((string) $s->id, $search)
        )->values();

        $perPage = $this->perPage($options);
        $totalRows = $filtered->count();
        $lastPage = max(1, (int) ceil($totalRows / $perPage));
        $page = min(max(1, (int) ($options['page'] ?? 1)), $lastPage);

        // Only the visible page is rendered in full detail; the numbers above
        // already cover the whole set, so there is no need to build 200 reports.
        $rows = $filtered->forPage($page, $perPage)->map(function (Student $student) use ($month, $year, $withData) {
            $generates = isset($withData[$student->id]);
            $preview = $generates ? $this->reports->previewForStudent($student->id, $month, $year) : null;

            $attendance = $preview['attendance'] ?? ['total_days' => 0, 'present' => 0, 'absent' => 0, 'rate' => 0];
            $evaluations = $preview['evaluations'] ?? ['total' => 0, 'average_level' => 0, 'breakdown' => []];
            $exams = $preview['exams'] ?? ['overall_percentage' => 0, 'subjects' => []];
            $quizzes = $preview['quizzes'] ?? ['total' => 0, 'average_percentage' => 0];

            return [
                'student_id' => $student->id,
                'name'       => $this->reports->nameOf($student, 'name') ?? '—',
                'grade'      => $student->grade ? $this->reports->nameOf($student->grade) : '—',
                'section'    => $student->section ? $this->reports->nameOf($student->section, 'Name_Section') : '—',
                'will_send'  => $preview !== null,
                // Flat cells: what an approver scans across 200 rows. The
                // per-subject marks live in `details`, one click away, because
                // inlining them here would make the table unreadable.
                'attendance'  => [
                    'rate'       => $attendance['rate'],
                    'present'    => $attendance['present'],
                    'absent'     => $attendance['absent'],
                    'total_days' => $attendance['total_days'],
                ],
                'evaluations' => [
                    'total'         => $evaluations['total'],
                    'average_level' => $evaluations['average_level'],
                ],
                'exams'       => [
                    'overall_percentage' => $exams['overall_percentage'],
                    'subjects_count'     => count($exams['subjects'] ?? []),
                ],
                'quizzes' => [
                    'total'               => $quizzes['total'],
                    'average_percentage' => $quizzes['average_percentage'],
                ],
                'score' => $preview['progress_score'] ?? null,
                'trend' => $preview['trend'] ?? null,
                'details' => $preview === null ? null : [
                    'attendance'    => $attendance,
                    'evaluations'   => $evaluations,
                    'exams'         => $exams,
                    'quizzes'       => $quizzes,
                    'teacher_notes' => $preview['teacher_notes'] ?? [],
                ],
            ];
        })->values()->all();

        $warnings = [];
        if ($willSkip > 0) {
            $warnings[] = $this->warn(
                'ar',
                "سيتم تخطي {$willSkip} طالب لعدم وجود بيانات في هذه الفترة."
            );
        }
        $lowAttendance = collect($rates)->filter(fn ($rate) => $rate < 80)->count();
        if ($lowAttendance > 0) {
            $warnings[] = $this->warn(
                'ar',
                "نسبة حضور {$lowAttendance} طالب أقل من 80% في هذه الفترة."
            );
        }

        return [
            'summary'  => [
                ['key' => 'total', 'label_ar' => 'إجمالي الطلاب', 'value' => $total, 'tone' => 'neutral'],
                ['key' => 'will_send', 'label_ar' => 'سيتم إرسال تقرير لهم', 'value' => $willGenerate, 'tone' => 'success'],
                ['key' => 'skipped', 'label_ar' => 'سيتم تخطيهم', 'value' => $willSkip, 'tone' => $willSkip > 0 ? 'warning' : 'neutral'],
                [
                    'key'       => 'avg_attendance',
                    'label_ar'  => 'متوسط نسبة الحضور',
                    'value'     => $rates ? round(array_sum($rates) / count($rates), 1) : 0,
                    'suffix'    => '%',
                    'tone'      => 'neutral',
                ],
                [
                    'key'      => 'notifications',
                    'label_ar' => 'إشعارات ستُرسل',
                    'value'    => $willGenerate * 2,
                    'tone'     => 'neutral',
                ],
            ],
            'columns'  => [
                ['key' => 'name', 'label_ar' => 'الطالب'],
                ['key' => 'grade', 'label_ar' => 'الصف'],
                ['key' => 'section', 'label_ar' => 'القسم'],
                [
                    'key'      => 'attendance',
                    'label_ar' => 'الحضور',
                    'align'    => 'center',
                    'labels'   => [
                        'rate'       => 'النسبة',
                        'present'    => 'حضور',
                        'absent'     => 'غياب',
                        'total_days' => 'أيام',
                    ],
                ],
                [
                    'key'      => 'evaluations',
                    'label_ar' => 'التقييمات',
                    'align'    => 'center',
                    'labels'   => ['total' => 'عدد', 'average_level' => 'المتوسط'],
                ],
                [
                    'key'      => 'exams',
                    'label_ar' => 'الامتحانات',
                    'align'    => 'center',
                    'labels'   => ['overall_percentage' => 'النسبة', 'subjects_count' => 'مواد'],
                ],
                [
                    'key'      => 'quizzes',
                    'label_ar' => 'الاختبارات',
                    'align'    => 'center',
                    'labels'   => ['total' => 'عدد', 'average_percentage' => 'المتوسط'],
                ],
                ['key' => 'score', 'label_ar' => 'التقدّم', 'align' => 'center'],
            ],
            'rows'     => $rows,
            'meta'     => $this->meta($page, $lastPage, $totalRows),
            'warnings' => $warnings,
            'available' => true,
        ];
    }

    // ------------------------------------------------------------------
    // نشر درجات الامتحانات الورقية
    // ------------------------------------------------------------------

    /**
     * The exact mark each student would be shown.
     */
    private function sendMarks(PendingRequest $request, array $options): array
    {
        $exam = $request->entity_type === PaperExam::class
            ? PaperExam::with(['subject', 'grade', 'classroom', 'section', 'teacher'])->find($request->entity_id)
            : null;

        if (! $exam) {
            return $this->unavailable('الاختبار غير موجود أو تم حذفه.');
        }

        $marks = DB::table('paper_exam_marks as m')
            ->join('students as s', 's.id', '=', 'm.student_id')
            ->leftJoin('sections as sec', 'sec.id', '=', 's.section_id')
            ->where('m.paper_exam_id', $exam->id)
            ->selectRaw('m.student_id, m.marks_obtained, s.name as student_name, sec.Name_Section as section_name')
            ->get();

        $max = (float) ($exam->max_score ?: 100);
        $perPage = $this->perPage($options);
        $totalRows = $marks->count();
        $lastPage = max(1, (int) ceil($totalRows / $perPage));
        $page = min(max(1, (int) ($options['page'] ?? 1)), $lastPage);

        $rows = $marks->forPage($page, $perPage)->map(function ($row) use ($max) {
            $percentage = $max > 0 ? round(((float) $row->marks_obtained / $max) * 100, 1) : 0;

            return [
                'student_id' => (int) $row->student_id,
                'name'       => $this->translateName($row->student_name) ?? '—',
                'section'    => $this->translateName($row->section_name) ?? '—',
                'marks'      => (float) $row->marks_obtained,
                'max_score'  => $max,
                'percentage' => $percentage,
                'result'     => $this->gradeLabel($percentage),
            ];
        })->values()->all();

        $percentages = $marks->map(fn ($row) => $max > 0 ? ((float) $row->marks_obtained / $max) * 100 : 0);
        $average = $percentages->isEmpty() ? 0 : round($percentages->avg(), 1);

        $warnings = [];
        if ($totalRows === 0) {
            $warnings[] = $this->warn('ar', 'لا توجد درجات مسجلة لهذا الاختبار، لن يتم إرسال أي إشعار.');
        }
        if ($exam->status === 'published') {
            $warnings[] = $this->warn('ar', 'هذا الاختبار منشور بالفعل، قد تتكرر الإشعارات عند الموافقة.');
        }

        return [
            'summary'  => [
                ['key' => 'exam', 'label_ar' => 'الاختبار', 'value' => $this->translateName($exam->title) ?? '—'],
                ['key' => 'subject', 'label_ar' => 'المادة', 'value' => $exam->subject ? ($this->translateName($exam->subject->Name) ?? '—') : '—'],
                ['key' => 'marks', 'label_ar' => 'عدد الدرجات', 'value' => $totalRows, 'tone' => 'neutral'],
                ['key' => 'average', 'label_ar' => 'متوسط النسبة', 'value' => $average, 'suffix' => '%'],
                [
                    'key'       => 'notifications',
                    'label_ar'  => 'إشعارات للطلاب وأولياء الأمور',
                    'value'     => $totalRows * 2,
                    'tone'      => 'neutral',
                ],
            ],
            'columns'  => [
                ['key' => 'name', 'label_ar' => 'الطالب'],
                ['key' => 'section', 'label_ar' => 'القسم'],
                ['key' => 'marks', 'label_ar' => 'الدرجة', 'align' => 'center'],
                ['key' => 'percentage', 'label_ar' => 'النسبة', 'align' => 'center'],
                ['key' => 'result', 'label_ar' => 'التقدير', 'align' => 'center'],
            ],
            'rows'     => $rows,
            'meta'     => $this->meta($page, $lastPage, $totalRows),
            'warnings' => $warnings,
            'available' => true,
        ];
    }

    // ------------------------------------------------------------------
    // صرف راتب
    // ------------------------------------------------------------------

    /**
     * The payment being released, exactly as SalaryPaymentService::pay() will
     * apply it: the amount leaves the fund and the entry is marked paid.
     */
    private function paySalary(PendingRequest $request): array
    {
        $payment = $request->entity_type === SalaryPayment::class
            ? SalaryPayment::with(['employee', 'fundEntry'])->find($request->entity_id)
            : null;

        if (! $payment) {
            return $this->unavailable('سجل الراتب غير موجود أو تم حذفه.');
        }

        $amount = (float) ($payment->amount ?? 0);
        $method = (string) ($payment->payment_method ?? '');
        $notes = (string) ($payment->notes ?? '');
        $employee = $payment->employee;
        // `employee_name` is the model's own accessor and already knows whether
        // the employee is a teacher (translatable `Name`) or a user (`name`).
        $employeeName = $this->translateName($payment->employee_name) ?? '—';

        $warnings = [];
        // Both of these make SalaryPaymentService::pay() throw, so approving is
        // a dead end the approver has to see before signing.
        if ($payment->status === 'paid') {
            $warnings[] = $this->warn('ar', 'هذا الراتب مصروف بالفعل، الموافقة ستفشل.');
        }
        if ($payment->status === 'cancelled') {
            $warnings[] = $this->warn('ar', 'هذا الراتب ملغى، الموافقة ستفشل.');
        }
        if ($amount <= 0) {
            $warnings[] = $this->warn('ar', 'المبلغ صفر أو أقل، لن يتم صرف أي مبلغ.');
        }
        if ($employee === null) {
            $warnings[] = $this->warn('ar', 'الموظف المرتبط بالراتب غير موجود.');
        }

        $rows = [
            ['label' => 'الموظف', 'value' => $employeeName],
            ['label' => 'الفترة', 'value' => $this->periodLabel((int) $payment->period_month, (int) $payment->period_year)],
            ['label' => 'المبلغ المصروف', 'value' => $amount],
            ['label' => 'تاريخ الاستحقاق', 'value' => $payment->due_date ? $payment->due_date->toDateString() : '—'],
            ['label' => 'طريقة الصرف', 'value' => $method !== '' ? $method : '—'],
            ['label' => 'ملاحظات', 'value' => $notes !== '' ? $notes : '—'],
            ['label' => 'الحالة الحالية', 'value' => $payment->status],
        ];

        return [
            'summary'  => [
                ['key' => 'employee', 'label_ar' => 'الموظف', 'value' => $employeeName],
                ['key' => 'amount', 'label_ar' => 'المبلغ المصروف', 'value' => $amount, 'tone' => 'success'],
                [
                    'key'      => 'period',
                    'label_ar' => 'الفترة',
                    'value'    => $this->periodLabel((int) $payment->period_month, (int) $payment->period_year),
                ],
                ['key' => 'status', 'label_ar' => 'الحالة', 'value' => $payment->status, 'tone' => $payment->status === 'pending' ? 'neutral' : 'warning'],
            ],
            'columns'  => [
                ['key' => 'label', 'label_ar' => 'البند'],
                ['key' => 'value', 'label_ar' => 'القيمة', 'align' => 'center'],
            ],
            'rows'     => $rows,
            'meta'     => $this->meta(1, 1, count($rows)),
            'warnings' => $warnings,
            'available' => true,
        ];
    }

    // ------------------------------------------------------------------
    // تعديل بيانات معلم
    // ------------------------------------------------------------------

    /**
     * A field-by-field before/after diff.
     *
     * The payload only carries the submitted values, so "before" is read live
     * from the record. That is exactly why the preview is worth opening: a
     * silently overwritten salary or email is invisible in the queue row.
     */
    private function updateTeacher(PendingRequest $request): array
    {
        $teacher = $request->entity_type === Teacher::class
            ? Teacher::with(['specializations', 'genders'])->find($request->entity_id)
            : null;

        if (! $teacher) {
            return $this->unavailable('سجل المعلم غير موجود أو تم حذفه.');
        }

        $labels = [
            'Name_ar'          => 'الاسم',
            'Email'            => 'البريد الإلكتروني',
            'Specialization_id' => 'التخصص',
            'Gender_id'        => 'النوع',
            'Joining_Date'     => 'تاريخ التعيين',
            'Address'          => 'العنوان',
            'Salary'           => 'الراتب',
        ];

        $payload = (array) ($request->payload ?? []);
        // `_endpoint` is traceability metadata added by defer(), never a field.
        unset($payload['_endpoint']);

        $before = [
            'Name_ar'          => $this->translateName($teacher->Name),
            'Email'            => $teacher->email,
            'Specialization_id' => $teacher->specializations ? ($this->translateName($teacher->specializations->Name) ?? '—') : '—',
            'Gender_id'        => $teacher->genders ? ($this->translateName($teacher->genders->Name) ?? '—') : '—',
            'Joining_Date'     => $teacher->Joining_Date ? (string) $teacher->Joining_Date : null,
            'Address'          => $teacher->Address,
            'Salary'           => $teacher->salary,
        ];

        $rows = [];
        foreach ($payload as $field => $after) {
            if (! array_key_exists($field, $labels)) {
                continue;
            }

            $old = $before[$field] ?? null;
            // Numbers arrive as strings from a form; compare on value, not type.
            $changed = is_numeric($old) && is_numeric($after)
                ? (float) $old !== (float) $after
                : (string) ($old ?? '') !== (string) ($after ?? '');

            $rows[] = [
                'field'    => $field,
                'label_ar' => $labels[$field],
                'before'   => $old === null || $old === '' ? '—' : $old,
                'after'    => $after === null || $after === '' ? '—' : $after,
                'changed'  => $changed,
            ];
        }

        $changedCount = count(array_filter($rows, fn ($row) => $row['changed']));
        $warnings = [];
        if ($changedCount === 0) {
            $warnings[] = $this->warn('ar', 'لا توجد قيم مختلفة عن현재، الموافقة لن تغيّر شيئاً.');
        }
        if (data_get(collect($rows)->firstWhere('field', 'Salary'), 'changed') === true) {
            $warnings[] = $this->warn('ar', 'تغيير الراتب يؤثر مباشرة على كشف الرواتب.');
        }

        return [
            'summary'  => [
                ['key' => 'teacher', 'label_ar' => 'المعلم', 'value' => $this->translateName($teacher->Name) ?? '—'],
                ['key' => 'fields', 'label_ar' => 'عدد الحقول المرسلة', 'value' => count($rows)],
                ['key' => 'changed', 'label_ar' => 'حقول ستتغير فعلياً', 'value' => $changedCount, 'tone' => $changedCount > 0 ? 'warning' : 'neutral'],
            ],
            'columns'  => [
                ['key' => 'label_ar', 'label_ar' => 'الحقل'],
                ['key' => 'before', 'label_ar' => 'القيمة الحالية'],
                ['key' => 'after', 'label_ar' => 'القيمة الجديدة', 'align' => 'center'],
            ],
            'rows'     => $rows,
            'meta'     => $this->meta(1, 1, count($rows)),
            'warnings' => $warnings,
            'available' => true,
        ];
    }

    // ------------------------------------------------------------------
    // Fallback
    // ------------------------------------------------------------------

    /**
     * Unknown / newly registered action: show what was asked for, and be honest
     * that no preview exists rather than rendering an empty table.
     */
    private function generic(PendingRequest $request): array
    {
        $payload = $request->payload ?? [];

        return [
            'summary'   => [
                ['key' => 'action', 'label_ar' => 'الإجراء', 'value' => $request->actionLabel()],
                ['key' => 'created', 'label_ar' => 'تاريخ الطلب', 'value' => $request->created_at?->toDateTimeString() ?? '—'],
            ],
            'columns'   => [],
            'rows'      => $payload === [] ? [] : [
                ['field' => 'key', 'label_ar' => 'الحقل', 'value' => is_scalar($payload) ? $payload : json_encode($payload, JSON_UNESCAPED_UNICODE)],
            ],
            'meta'      => $this->meta(1, 1, $payload === [] ? 0 : 1),
            'warnings'  => [$this->warn('ar', 'لا توجد معاينة تفصيلية لهذا الإجراء.')],
            'available' => true,
        ];
    }

    private function unavailable(string $messageAr): array
    {
        return [
            'summary'   => [],
            'columns'   => [],
            'rows'      => [],
            'meta'      => $this->meta(1, 1, 0),
            'warnings'  => [$this->warn('ar', $messageAr)],
            'available' => false,
        ];
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * @return array{page: int, last_page: int, total: int}
     */
    private function meta(int $page, int $lastPage, int $total): array
    {
        return [
            'page'      => $page,
            'last_page' => $lastPage,
            'total'     => $total,
        ];
    }

    private function perPage(array $options): int
    {
        $perPage = (int) ($options['per_page'] ?? self::DEFAULT_PER_PAGE);

        return min(max($perPage, 1), self::MAX_PER_PAGE);
    }

    private function warn(string $locale, string $message): array
    {
        return ['locale' => $locale, 'message' => $message];
    }

    /**
     * Spatie-translatable columns come back as JSON or as an array depending on
     * the driver, so normalise both before showing them.
     */
    private function translateName($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value) && str_starts_with(trim($value), '{')) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : $value;
        }

        if (is_array($value)) {
            return $value['ar'] ?? $value['en'] ?? null;
        }

        return (string) $value;
    }

    private function gradeLabel(float $percentage): string
    {
        return match (true) {
            $percentage >= 90 => 'ممتاز',
            $percentage >= 80 => 'جيد جداً',
            $percentage >= 70 => 'جيد',
            $percentage >= 50 => 'مقبول',
            default           => 'ضعيف',
        };
    }

    /**
     * Months in Arabic, for period labels.
     */
    public function periodLabel(int $month, int $year): string
    {
        $months = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل', 5 => 'مايو', 6 => 'يونيو',
            7 => 'يوليو', 8 => 'أغسطس', 9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];

        return ($months[$month] ?? "شهر {$month}")." {$year}";
    }
}