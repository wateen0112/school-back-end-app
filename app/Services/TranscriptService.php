<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\GradeTranscriptSetting;
use App\Models\PaperExam;
use App\Models\PaperExamMark;
use App\Models\Student;
use App\Models\SubjectGradeSetting;

/**
 * حساب الجلاء المدرسي للطالب وفق إعدادات صفه (GradeTranscriptSetting):
 * - direct: المحصلة تُدخل مباشرة دون تقسيم (الصفوف 1-4).
 * - coursework_exam: درجة أعمال (الشفهي + الوظائف + المذاكرات) من وزنها +
 *   درجة الامتحان من وزنها = محصلة المادة من 100.
 *
 * قواعد العلامات:
 * - الصفر: للطالب الغائب تماماً عن الدوام أو الامتحان.
 * - الواحد (1): عامل تميز للحاضر الذي استحق صفراً علمياً.
 */
class TranscriptService
{
    /**
     * إنشاء بيانات الجلاء لطالب.
     *
     * @param Student $student
     * @param int|null $academicTermId الفصل المطلوب، وإلا الفصل الحالي ثم السنة كاملة
     * @return array
     */
    public function generate(Student $student, $academicTermId = null)
    {
        $student->loadMissing(['grade', 'classroom', 'section', 'myparent', 'gender']);

        // المدرسة النشطة (تُعرض في ترويسة الجلاء)
        $school = \App\Models\School::query()->where('status', 'active')->orderBy('id')->first();

        // الصف الذي ينتقل إليه الطالب (التالي في ترتيب صفوف المرحلة)
        $nextClassroom = \App\Models\Classroom::query()
            ->where('Grade_id', $student->Grade_id)
            ->where('id', '>', $student->Classroom_id)
            ->orderBy('id')
            ->first();

        $term = $academicTermId
            ? AcademicTerm::find($academicTermId)
            : AcademicTerm::where('is_current', true)->latest('id')->first();

        // إعدادات الصف الفعلي (Classroom) أولاً، ثم المرحلة كاحتياطي
        $settings = GradeTranscriptSetting::active()
            ->where('classroom_id', $student->Classroom_id)
            ->orWhere(function ($q) use ($student) {
                $q->whereNull('classroom_id')->where('grade_id', $student->Grade_id);
            })
            ->orderByRaw('classroom_id IS NULL')
            ->first() ?: new GradeTranscriptSetting([
                'calculation_mode' => GradeTranscriptSetting::MODE_DIRECT,
                'pass_percentage' => 50,
                'show_grade_level' => true,
            ]);

        $marks = $this->getSubjectMarks($student, $term);
        $details = $this->getSubjectExamDetails($student, $term);
        $attendance = $this->getAttendanceSummary($student, $term);

        $subjects = [];
        foreach ($marks as $subjectId => $data) {
            $subject = $data['subject'];
            $failMark = $this->failMarkFor($student, $subjectId);
            $maxMark = $this->maxMarkFor($student, $subjectId);

            if ($settings->calculation_mode === GradeTranscriptSetting::MODE_DIRECT) {
                // إدخال مباشر: مجموع العلامات كما هي (المحصلة من 100)
                $obtained = $data['obtained'];
                $max = $data['max'] ?: 100;
                $final = $max > 0 ? round($obtained / $max * 100, 2) : 0;
                $coursework = null;
                $exam = null;
            } else {
                // الأعمال (60) = شفهي + وظائف + مذاكرات، والامتحان (40)
                $coursework = $this->scaleComponent(
                    $data['components']['oral']['obtained'] ?? 0,
                    $data['components']['oral']['max'] ?? 0,
                    (float) $settings->oral_max
                ) + $this->scaleComponent(
                    $data['components']['homework']['obtained'] ?? 0,
                    $data['components']['homework']['max'] ?? 0,
                    (float) $settings->homework_max
                ) + $this->scaleComponent(
                    $data['components']['quizzes']['obtained'] ?? 0,
                    $data['components']['quizzes']['max'] ?? 0,
                    (float) $settings->quizzes_max
                );
                $coursework = min(round($coursework, 2), (float) $settings->coursework_max);

                $exam = $this->scaleComponent(
                    $data['components']['exam']['obtained'] ?? 0,
                    $data['components']['exam']['max'] ?? 0,
                    (float) $settings->exam_max
                );

                $final = round(min($coursework, (float) $settings->coursework_max)
                    + min($exam, (float) $settings->exam_max), 2);
            }

            $subjects[] = [
                'subject_id' => $subjectId,
                'subject_name' => $subject ? $subject->Name : null,
                'coursework' => $coursework,
                'exam' => $exam,
                'final' => $final,
                'grade_level' => $settings->show_grade_level ? $this->gradeLevel($final) : null,
                'passed' => $final >= $failMark,
                'fail_mark' => $failMark,
                'max_mark' => $maxMark,
                // تفصيل علامات كل اختبار (شفهي / مذاكرات / أعمال / امتحان)
                'detail' => $details[$subjectId] ?? [],
            ];
        }

        $average = count($subjects)
            ? round(array_sum(array_column($subjects, 'final')) / count($subjects), 2)
            : 0;

        $failedSubjects = array_values(array_filter($subjects, fn ($s) => ! $s['passed']));
        $passed = count($failedSubjects) === 0 && $average >= (float) $settings->pass_percentage;

        $genderName = optional($student->gender)->Name;
        if (is_array($genderName)) {
            $genderName = $genderName['ar'] ?? $genderName['en'] ?? null;
        }
        $isFemale = $genderName !== null && in_array(mb_strtolower(trim((string) $genderName)), ['female', 'انثي', 'أنثى', 'أنثي', 'فتاة'], true);
        $fatherName = optional($student->myparent)->Name_Father;
        $relation = $isFemale ? 'بنت' : 'بن';

        $rank = $this->classRank($student, $term);

        return [
            'student' => [
                'id' => $student->id,
                'name' => $student->name ?? null,
                'second_name' => $student->second_name ?? null,
                'grade' => optional($student->grade)->Name,
                'classroom' => optional($student->classroom)->Name_Class,
                'section' => optional($student->section)->Name_Section,
                'father_name' => $fatherName,
                'father_relation' => $relation,
                'mother_name' => optional($student->myparent)->Name_Mother,
                'gender' => $genderName,
                'gender_id' => $student->gender_id,
                'date_of_birth' => $student->Date_Birth ?? null,
                'general_record_number' => $student->general_record_number ?? null,
                'rank' => $rank['rank'],
                'class_size' => $rank['class_size'],
                'school' => $school ? $school->getTranslations('name') : null,
                'school_address' => $school?->address,
                'school_phone' => $school?->phone,
                'school_governorate' => $school?->governorate
                    ?: \App\Models\Setting::where('key', 'school_governorate')->value('value'),
                'school_principal' => $school?->principal_name
                    ?: \App\Models\Setting::where('key', 'school_principal')->value('value'),
                'next_classroom' => optional($nextClassroom)->Name_Class,
                'academic_year' => $term ? optional($term->academicYear)->name : null,
                'term' => $term ? $term->name : null,
            ],
            'settings' => [
                'calculation_mode' => $settings->calculation_mode,
                'coursework_max' => (float) $settings->coursework_max,
                'exam_max' => (float) $settings->exam_max,
            ],
            'subjects' => $subjects,
            'attendance' => $attendance,
            'average' => $average,
            'average_grade_level' => $settings->show_grade_level ? $this->gradeLevel($average) : null,
            'passed' => $passed,
            'failed_subjects' => $failedSubjects,
        ];
    }

    /**
     * الجلاء السنوي: يولّد جلاء كل فصل (الأول والثاني) على حدة ثم يجمع
     * المحصلة النهائية للعام (معدل الفصلين معاً + النتيجة النهائية).
     *
     * @return array{terms: array, combined: array}
     */
    public function generateYearly(Student $student)
    {
        $terms = AcademicTerm::query()
            ->whereIn('term_code', [AcademicTerm::CODE_TERM_1, AcademicTerm::CODE_TERM_2])
            ->orderBy('term_code')
            ->get();

        $termTranscripts = [];
        foreach ($terms as $term) {
            $termTranscripts[] = $this->generate($student, $term->id);
        }

        $withMarks = array_filter($termTranscripts, fn ($t) => count($t['subjects']) > 0);
        $average = count($withMarks)
            ? round(array_sum(array_column($termTranscripts, 'average')) / count($withMarks), 2)
            : 0;

        $allFailed = [];
        foreach ($termTranscripts as $t) {
            foreach ($t['failed_subjects'] as $f) {
                $name = $f['subject_name'];
                if (is_string($name) && str_starts_with(trim($name), '{')) {
                    $decoded = json_decode($name, true);
                    $name = $decoded['ar'] ?? $decoded['en'] ?? $name;
                } elseif (is_array($name)) {
                    $name = $name['ar'] ?? $name['en'];
                }
                $allFailed[] = $t['student']['term'].' — '.$name;
            }
        }

        $passed = count($withMarks) > 0 && count($allFailed) === 0
            && collect($withMarks)->every(fn ($t) => $t['passed']);

        return [
            'terms' => $termTranscripts,
            'combined' => [
                'average' => $average,
                'average_grade_level' => $this->gradeLevel($average),
                'passed' => $passed,
                'failed_subjects' => $allFailed,
            ],
        ];
    }

    /**
     * ترتيب الطالب بين زملائه في نفس الصف (وفقاً لمعدله في نفس الفصل).
     * يرجع ['rank' => int|null, 'class_size' => int].
     */
    protected function classRank(Student $student, ?AcademicTerm $term): array
    {
        $classmates = Student::where('Classroom_id', $student->Classroom_id)
            ->whereNull('deleted_at')
            ->pluck('id');

        if ($classmates->count() === 0) {
            return ['rank' => null, 'class_size' => 0];
        }

        $query = PaperExamMark::query()
            ->join('paper_exams', 'paper_exams.id', '=', 'paper_exam_marks.paper_exam_id')
            ->whereIn('paper_exam_marks.student_id', $classmates)
            ->where('paper_exams.classroom_id', $student->Classroom_id)
            ->groupBy('paper_exam_marks.student_id', 'paper_exams.subject_id')
            ->select('paper_exam_marks.student_id', 'paper_exams.subject_id')
            ->selectRaw('SUM(paper_exam_marks.marks_obtained) as obtained')
            ->selectRaw('SUM(paper_exams.max_score) as max');

        if ($term) {
            $query->where('paper_exams.academic_term_id', $term->id);
        }

        // متوسط النسب المئوية لكل طالب عبر المواد
        $totals = [];
        foreach ($query->get() as $row) {
            $max = (float) $row->max;
            if ($max <= 0) {
                continue;
            }
            $totals[$row->student_id]['sum'] = ($totals[$row->student_id]['sum'] ?? 0)
                + ((float) $row->obtained / $max) * 100;
            $totals[$row->student_id]['count'] = ($totals[$row->student_id]['count'] ?? 0) + 1;
        }

        $averages = [];
        foreach ($totals as $id => $t) {
            if ($t['count'] > 0) {
                $averages[$id] = $t['sum'] / $t['count'];
            }
        }

        if (! isset($averages[$student->id])) {
            return ['rank' => null, 'class_size' => $classmates->count()];
        }

        arsort($averages);

        $rank = 1;
        foreach ($averages as $id => $avg) {
            if ((int) $id === (int) $student->id) {
                break;
            }
            $rank++;
        }

        return ['rank' => $rank, 'class_size' => $classmates->count()];
    }

    /**
     * سجل غياب الطالب للفصل: مبرر / غير مبرر.
     */
    protected function getAttendanceSummary(Student $student, ?AcademicTerm $term)
    {
        $summary = ['justified' => 0, 'unjustified' => 0];

        if (! $term) {
            return $summary;
        }

        \App\Models\Attendance::query()
            ->where('student_id', $student->id)
            ->where('attendence_status', 0)
            ->whereBetween('attendence_date', [$term->start_date, $term->end_date])
            ->selectRaw('is_justified, COUNT(*) as c')
            ->groupBy('is_justified')
            ->get()
            ->each(function ($row) use (&$summary) {
                if ($row->is_justified) {
                    $summary['justified'] = (int) $row->c;
                } else {
                    $summary['unjustified'] = (int) $row->c;
                }
            });

        return $summary;
    }

    /**
     * تقدير العلامة (ممتاز / جيد جداً / ...).
     */
    public function gradeLevel($mark)
    {
        if ($mark >= 90) return 'ممتاز';
        if ($mark >= 80) return 'جيد جداً';
        if ($mark >= 70) return 'جيد';
        if ($mark >= 60) return 'متوسط';
        if ($mark >= 50) return 'مقبول';
        return 'ضعيف';
    }

    /**
     * علامة الرسوب للمادة: إعداد الصف أولاً ثم المرحلة ثم 50 افتراضياً.
     */
    protected function failMarkFor(Student $student, $subjectId)
    {
        $setting = SubjectGradeSetting::where('subject_id', $subjectId)
            ->where(function ($q) use ($student) {
                $q->where('classroom_id', $student->Classroom_id)
                    ->orWhere(function ($q2) use ($student) {
                        $q2->whereNull('classroom_id')->where('grade_id', $student->Grade_id);
                    });
            })
            ->orderByRaw('classroom_id IS NULL')
            ->first();

        return (float) ($setting->fail_mark ?? 50);
    }

    /**
     * العلامة العظمى للمادة: إعداد الصف أولاً ثم المرحلة ثم 100 افتراضياً.
     */
    protected function maxMarkFor(Student $student, $subjectId)
    {
        $setting = SubjectGradeSetting::where('subject_id', $subjectId)
            ->where(function ($q) use ($student) {
                $q->where('classroom_id', $student->Classroom_id)
                    ->orWhere(function ($q2) use ($student) {
                        $q2->whereNull('classroom_id')->where('grade_id', $student->Grade_id);
                    });
            })
            ->orderByRaw('classroom_id IS NULL')
            ->first();

        return (float) ($setting->max_mark ?? 100);
    }

    /**
     * تجميع علامات الطالب حسب المادة مع تقسيمها لمكوناتها
     * (شفهي / وظائف / مذاكرات / امتحان).
     */
    protected function getSubjectMarks(Student $student, ?AcademicTerm $term)
    {
        $query = PaperExamMark::query()
            ->join('paper_exams', 'paper_exams.id', '=', 'paper_exam_marks.paper_exam_id')
            ->leftJoin('subjects', 'subjects.id', '=', 'paper_exams.subject_id')
            ->where('paper_exams.classroom_id', $student->Classroom_id)
            ->where('paper_exam_marks.student_id', $student->id)
            ->select(
                'paper_exams.subject_id',
                'paper_exams.exam_type',
                'subjects.Name as subject_name',
                'paper_exams.max_score'
            )
            ->selectRaw('SUM(paper_exam_marks.marks_obtained) as obtained')
            ->selectRaw('SUM(paper_exams.max_score) as max')
            ->groupBy('paper_exams.subject_id', 'paper_exams.exam_type', 'subjects.Name', 'paper_exams.max_score');

        if ($term) {
            $query->where('paper_exams.academic_term_id', $term->id);
        }

        $grouping = [
            'oral' => ['oral'],
            'homework' => ['coursework'],
            'quizzes' => ['written_review_1', 'written_review_2'],
            'exam' => ['final_exam'],
        ];

        $marks = [];
        foreach ($query->get() as $row) {
            $subjectId = $row->subject_id;
            $marks[$subjectId] = $marks[$subjectId] ?? [
                'subject' => (object) ['Name' => $row->subject_name],
                'obtained' => 0,
                'max' => 0,
                'components' => [],
            ];
            $marks[$subjectId]['obtained'] += (float) $row->obtained;
            $marks[$subjectId]['max'] += (float) $row->max;

            foreach ($grouping as $component => $types) {
                if (in_array($row->exam_type, $types)) {
                    $marks[$subjectId]['components'][$component]['obtained'] =
                        ($marks[$subjectId]['components'][$component]['obtained'] ?? 0) + (float) $row->obtained;
                    $marks[$subjectId]['components'][$component]['max'] =
                        ($marks[$subjectId]['components'][$component]['max'] ?? 0) + (float) $row->max;
                }
            }
        }

        return $marks;
    }

    /**
     * تفصيل علامات الطالب لكل مادة حسب نوع الاختبار
     * (شفهي / مذاكرة أولى / مذاكرة ثانية / درجة أعمال / امتحان)
     * مع العلامة العظمى لكل اختبار — لعرضها في الجلاء بشكل مفصل.
     */
    protected function getSubjectExamDetails(Student $student, ?AcademicTerm $term)
    {
        $query = PaperExam::query()
            ->join('paper_exam_marks', 'paper_exam_marks.paper_exam_id', '=', 'paper_exams.id')
            ->where('paper_exam_marks.student_id', $student->id)
            ->where('paper_exams.classroom_id', $student->Classroom_id)
            ->select(
                'paper_exams.subject_id',
                'paper_exams.exam_type',
                'paper_exams.max_score'
            )
            ->selectRaw('SUM(paper_exam_marks.marks_obtained) as obtained');

        if ($term) {
            $query->where('paper_exams.academic_term_id', $term->id);
        }

        $details = [];
        foreach ($query->groupBy('paper_exams.subject_id', 'paper_exams.exam_type', 'paper_exams.max_score')->get() as $row) {
            $details[$row->subject_id][$row->exam_type]['obtained'] =
                ($details[$row->subject_id][$row->exam_type]['obtained'] ?? 0) + (float) $row->obtained;
            $details[$row->subject_id][$row->exam_type]['max'] =
                ($details[$row->subject_id][$row->exam_type]['max'] ?? 0) + (float) $row->max_score;
        }

        return $details;
    }

    /**
     * تحويل علامة مكوّن من علامته العظمى إلى سقفه المحدد في الإعدادات.
     */
    protected function scaleComponent($obtained, $max, $cap)
    {
        if ($cap <= 0) return 0;
        return $max > 0 ? ($obtained / $max) * $cap : 0;
    }
}
