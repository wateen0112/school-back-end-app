<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Section;
use App\Models\WeeklyProgram;
use App\Models\Attendance;
use App\Models\PaperExam;
use App\Models\NotificationRecipient;
use App\Models\Library;
use App\Models\StudentInstallment;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function nameOf($entity, $key = 'Name'): string
    {
        if (!$entity) return '—';

        $name = $entity->$key ?? null;

        if (is_array($name)) {
            return $this->pickTranslation($name);
        }

        // Relations resolved through Spatie return a locale string; reach for
        // the raw ar/en pair so we can honour the requested locale exactly.
        if ($name && method_exists($entity, 'getTranslations')) {
            $translations = $entity->getTranslations($key);
            if (is_array($translations) && $translations) {
                return $this->pickTranslation($translations);
            }
        }

        return (string) $name;
    }

    /**
     * Classroom names live under the translatable Name_Class key (not Name),
     * so resolve them explicitly.
     */
    public function classroomNameOf($classroom): string
    {
        if (!$classroom) return '—';

        $name = $classroom->getTranslation('Name_Class', app()->getLocale());

        return (string) ($name ?: ($classroom->Name_Class ?: '—'));
    }

    /**
     * Pick the arabic/english value matching the resolved app locale
     * (set per-request via the SetApiLocale middleware from ?locale=).
     */
    protected function pickTranslation(array $values): string
    {
        $ar = $values['ar'] ?? null;
        $en = $values['en'] ?? null;

        if (app()->getLocale() === 'ar') {
            return $ar ?: ($en ?: '—');
        }

        return $en ?: ($ar ?: '—');
    }

    /**
     * Same as pickTranslation but tolerant of raw JSON strings (e.g. values
     * selected through a query-builder join instead of an Eloquent model).
     */
    protected function pickValue(?string $value): string
    {
        if ($value === null || $value === '') return '—';

        if (str_starts_with($value, '{')) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $this->pickTranslation($decoded);
            }
        }

        return $value;
    }

    public function dayName(int $day): string
    {
        $ar = [0 => 'الأحد', 1 => 'الاثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء', 4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت'];
        $en = [0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];

        $names = app()->getLocale() === 'ar' ? $ar : $en;

        return $names[$day] ?? (app()->getLocale() === 'ar' ? "اليوم {$day}" : "Day {$day}");
    }

    /**
     * Formatted weekly programs for a section (optional day filter).
     */
    public function sectionSchedule(?int $sectionId, ?int $day = null): array
    {
        if (!$sectionId) return [];

        $query = WeeklyProgram::with(['subject', 'teacher', 'section'])
            ->where('section_id', $sectionId)
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time');

        if ($day !== null) {
            $query->where('day_of_week', $day);
        }

        return $query->get()->map(function ($p) {
            return [
                'id'           => (int) $p->id,
                'section_id'   => (int) $p->section_id,
                'day_of_week'  => (int) $p->day_of_week,
                'day_name'     => $this->dayName((int) $p->day_of_week),
                'start_time'   => substr((string) $p->start_time, 0, 5),
                'end_time'     => substr((string) $p->end_time, 0, 5),
                'subject_name' => $p->subject ? $this->nameOf($p->subject) : '—',
                'subject_id'   => $p->subject_id ? (int) $p->subject_id : null,
                'teacher_name' => $p->teacher ? $this->nameOf($p->teacher) : null,
                'teacher_id'   => $p->teacher_id ? (int) $p->teacher_id : null,
                'location'     => $p->location,
            ];
        })->values()->all();
    }

    public function attendanceSummary(int $studentId): array
    {
        $rows = Attendance::where('student_id', $studentId)->get(['attendence_date', 'attendence_status']);
        $total = $rows->count();
        $present = $rows->where('attendence_status', true)->count();
        $absent = $rows->where('attendence_status', false)->count();

        $today = Attendance::where('student_id', $studentId)
            ->whereDate('attendence_date', now()->toDateString())
            ->first(['attendence_date', 'attendence_status']);

        return [
            'total_days'      => $total,
            'present'         => $present,
            'absent'          => $absent,
            'present_rate'    => $total > 0 ? round(($present / $total) * 100, 1) : 0,
            'last_date'       => $rows->max('attendence_date'),
            'today_status'    => $today ? (bool) $today->attendence_status : null,
        ];
    }

    /**
     * Average paper-exam marks per subject (published exams only).
     */
    public function paperExamGrades(int $studentId): array
    {
        $rows = DB::table('paper_exam_marks as m')
            ->join('paper_exams as e', 'e.id', '=', 'm.paper_exam_id')
            ->join('subjects as s', 's.id', '=', 'e.subject_id')
            ->where('m.student_id', $studentId)
            ->where('e.status', 'published')
            ->selectRaw('e.subject_id as subject_id, s.Name as subject_name, AVG(m.marks_obtained) as avg_score, MAX(e.max_score) as max_score, COUNT(*) as exams_count')
            ->groupBy('e.subject_id', 's.Name')
            ->orderBy('avg_score', 'desc')
            ->get();

        $subject = [];
        $totalPct = 0;
        $weighted = 0;
        foreach ($rows as $row) {
            $raw = $row->subject_name;
            $name = is_array($raw)
                ? $this->pickTranslation($raw)
                : $this->pickValue(is_string($raw) ? $raw : '—');
            $max = (float) $row->max_score ?: 100;
            $avg = (float) $row->avg_score;
            $pct = $max > 0 ? round(($avg / $max) * 100, 1) : 0;
            $subject[] = [
                'subject_id'   => (int) $row->subject_id,
                'subject_name' => $name,
                'avg_score'    => round($avg, 1),
                'max_score'    => (float) $max,
                'percentage'   => $pct,
                'exams_count'  => (int) $row->exams_count,
            ];
            $totalPct += $pct;
            $weighted++;
        }

        return [
            'overall_percentage' => $weighted > 0 ? round($totalPct / $weighted, 1) : 0,
            'subjects'           => $subject,
        ];
    }

    public function upcomingExams(int $sectionId, int $limit = 5): array
    {
        if (!$sectionId) return [];

        return PaperExam::with(['subject', 'teacher'])
            ->where('section_id', $sectionId)
            ->where('status', 'published')
            ->whereDate('exam_date', '>=', now()->toDateString())
            ->orderBy('exam_date')
            ->limit($limit)
            ->get()
            ->map(function ($e) {
                return [
                    'id'           => (int) $e->id,
                    'title'        => $e->title,
                    'exam_date'    => $e->exam_date ? $e->exam_date->format('Y-m-d') : null,
                    'max_score'    => (float) $e->max_score,
                    'subject_name' => $e->subject ? $this->nameOf($e->subject) : '—',
                    'teacher_name' => $e->teacher ? $this->nameOf($e->teacher) : null,
                ];
            })->all();
    }

    public function notifications(int $recipientId, string $recipientType, int $limit = 5): array
    {
        $query = NotificationRecipient::with('notification')
            ->where('recipient_id', $recipientId)
            ->where('recipient_type', $recipientType);

        $unread = (clone $query)->where('is_read', false)->count();

        $recent = (clone $query)->orderBy('id', 'desc')->limit($limit)->get()->map(function ($r) {
            $n = $r->notification;
            return [
                'id'        => $r->id,
                'is_read'   => (bool) $r->is_read,
                'title'     => $n ? \App\Models\Notification::localizeText($n->title) : '—',
                'body'      => $n ? \App\Models\Notification::localizeText($n->body) : '',
                'created_at'=> $r->created_at ? $r->created_at->format('Y-m-d H:i') : null,
            ];
        })->all();

        return ['unread_count' => $unread, 'recent' => $recent];
    }

    public function learningMaterials(int $sectionId, ?int $gradeId, int $limit = 5): array
    {
        $query = Library::with('teacher')
            ->where(function ($q) use ($sectionId, $gradeId) {
                $q->where('section_id', $sectionId);
                if ($gradeId) {
                    $q->orWhere('Grade_id', $gradeId);
                }
            })
            ->latest()
            ->limit($limit);

        return $query->get()->map(function ($l) {
            return [
                'id'           => (int) $l->id,
                'title'        => $l->title,
                'file_name'    => $l->file_name,
                'teacher_name' => $l->teacher ? $this->nameOf($l->teacher) : null,
            ];
        })->all();
    }

    public function financialSummary(int $studentId): array
    {
        $rows = StudentInstallment::where('student_id', $studentId)->get();
        $totalAmount = (float) $rows->sum('amount');
        $paid = (float) $rows->sum('paid_amount');
        $remaining = (float) $rows->sum('remaining_amount');

        $sorted = $rows->filter(function ($r) {
            return $r->remaining_amount > 0 && $r->due_date && $r->due_date >= now()->toDateString();
        })->sortBy('due_date');

        return [
            'total_amount'     => round($totalAmount, 2),
            'paid'             => round($paid, 2),
            'remaining'        => round($remaining, 2),
            'installments'     => $rows->count(),
            'next_due'         => $sorted->first()
                ? [
                    'due_date'  => $sorted->first()->due_date->format('Y-m-d'),
                    'remaining' => round((float) $sorted->first()->remaining_amount, 2),
                    'title'     => $sorted->first()->installment ? $sorted->first()->installment->title : null,
                ]
                : null,
        ];
    }
}