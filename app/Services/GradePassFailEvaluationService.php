<?php

namespace App\Services;

use App\Models\GradePassFailRule;
use App\Models\PaperExamMark;
use App\Models\Student;
use App\Models\SubjectGradeSetting;

/**
 * تقييم نجاح/رسوب طالب وفق إعدادات الصف الخاصة به (GradePassFailRule):
 * - مادة مرسبة: علامة الطالب فيها أقل من الحد الأدنى المحدد.
 * - مادتان كمجموع: إذا كانت نسبة مجموع علامتيهما من مجموع العلامة العظمى
 *   أقل من النسبة المئوية الدنيا، تُحتسبان مادتين مرسبتين.
 */
class GradePassFailEvaluationService
{
    /**
     * @param Student $student
     * @param int|null $classroomId تحديد صف (Classroom)، وإلا يُستخدم صف الطالب الفعلي
     * @return array{passed: bool, failed_subjects: array, rules: array}
     */
    public function evaluate(Student $student, $classroomId = null)
    {
        $classroomId = $classroomId ?? $student->Classroom_id;
        $gradeId = $student->Grade_id;

        // قواعد الصف الفعلي أولاً، ثم قواعد المرحلة كاحتياطي
        $rules = GradePassFailRule::active()
            ->with(['subject', 'secondSubject'])
            ->where(function ($q) use ($student, $classroomId) {
                $q->where('classroom_id', $classroomId)
                    ->orWhere(function ($q2) use ($student) {
                        $q2->whereNull('classroom_id')->where('grade_id', $student->Grade_id);
                    });
            })
            ->orderByRaw('classroom_id IS NULL')
            ->get();

        // مجموع علامات الطالب لكل مادة (من الاختبارات الورقية لصفه)
        $marks = $this->getStudentSubjectMarks($student, $classroomId);

        $failedSubjects = [];
        $appliedRules = [];

        foreach ($rules as $rule) {
            if ($rule->rule_type === 'single_subject') {
                $mark = $marks[$rule->subject_id]['obtained'] ?? 0;
                $failed = $mark < (float) $rule->subject_min_mark;

                $appliedRules[] = [
                    'rule_id' => $rule->id,
                    'rule_type' => 'single_subject',
                    'subject' => $rule->subject->Name ?? $rule->subject_id,
                    'obtained' => $mark,
                    'min_required' => (float) $rule->subject_min_mark,
                    'failed' => $failed,
                ];

                if ($failed) {
                    $failedSubjects[$rule->subject_id] = [
                        'subject_id' => $rule->subject_id,
                        'subject' => $rule->subject->Name ?? null,
                        'obtained' => $mark,
                        'reason' => 'below_subject_min_mark',
                    ];
                }
            } elseif ($rule->rule_type === 'combined_subjects') {
                $minPercentage = (float) $rule->combined_min_percentage;

                // زوج محدد (legacy) أو أي مادتين من مواد الطالب مع استثناءات
                if ($rule->subject_id && $rule->second_subject_id) {
                    $pairs = [[$rule->subject_id, $rule->second_subject_id]];
                } else {
                    $excluded = $rule->excluded_subject_ids ?? [];
                    $candidates = array_keys(array_filter($marks, fn ($m, $id) => ! in_array($id, $excluded), ARRAY_FILTER_USE_BOTH));
                    $pairs = [];
                    $count = count($candidates);
                    for ($i = 0; $i < $count; $i++) {
                        for ($j = $i + 1; $j < $count; $j++) {
                            $pairs[] = [$candidates[$i], $candidates[$j]];
                        }
                    }
                }

                foreach ($pairs as [$firstId, $secondId]) {
                    $first = $marks[$firstId]['obtained'] ?? 0;
                    $second = $marks[$secondId]['obtained'] ?? 0;
                    $maxSum = ($marks[$firstId]['max'] ?? 0) + ($marks[$secondId]['max'] ?? 0);

                    $percentage = $maxSum > 0
                        ? round((($first + $second) / $maxSum) * 100, 2)
                        : 0;

                    $failed = $percentage < $minPercentage;

                    $appliedRules[] = [
                        'rule_id' => $rule->id,
                        'rule_type' => 'combined_subjects',
                        'subjects' => [
                            optional(\App\Models\Subject::find($firstId))->Name ?? $firstId,
                            optional(\App\Models\Subject::find($secondId))->Name ?? $secondId,
                        ],
                        'combined_obtained' => $first + $second,
                        'combined_max' => $maxSum,
                        'percentage' => $percentage,
                        'min_required_percentage' => $minPercentage,
                        'failed' => $failed,
                    ];

                    if ($failed) {
                        foreach ([$firstId, $secondId] as $subjectId) {
                            $failedSubjects[$subjectId] = [
                                'subject_id' => $subjectId,
                                'subject' => null,
                                'obtained' => $marks[$subjectId]['obtained'] ?? 0,
                                'reason' => 'below_combined_percentage',
                            ];
                        }
                    }
                }
            }
        }

        // تعبئة أسماء المواد المرسبة
        foreach ($failedSubjects as $subjectId => &$info) {
            if ($info['subject'] === null) {
                $info['subject'] = optional(\App\Models\Subject::find($subjectId))->Name;
            }
        }

        return [
            'student_id' => $student->id,
            'classroom_id' => $classroomId,
            'grade_id' => $gradeId,
            'passed' => count($failedSubjects) === 0,
            'failed_subjects' => array_values($failedSubjects),
            'rules' => $appliedRules,
        ];
    }

    /**
     * مجموع علامات الطالب والعلامة العظمى لكل مادة.
     */
    protected function getStudentSubjectMarks(Student $student, $classroomId)
    {
        $rows = PaperExamMark::query()
            ->join('paper_exams', 'paper_exams.id', '=', 'paper_exam_marks.paper_exam_id')
            ->where('paper_exams.classroom_id', $classroomId)
            ->where('paper_exam_marks.student_id', $student->id)
            ->select('paper_exams.subject_id')
            ->selectRaw('SUM(paper_exam_marks.marks_obtained) as obtained')
            ->selectRaw('SUM(paper_exams.max_score) as max')
            ->groupBy('paper_exams.subject_id')
            ->get();

        $marks = [];
        foreach ($rows as $row) {
            $marks[$row->subject_id] = [
                'obtained' => (float) $row->obtained,
                // العلامة العظمى: إعداد الصف أولاً ثم المرحلة
                'max' => (float) $row->max > 0
                    ? (float) $row->max
                    : $this->maxMarkFor($student, $row->subject_id),
            ];
        }

        return $marks;
    }

    /**
     * العلامة العظمى للمادة: إعداد الصف الفعلي أولاً ثم المرحلة ثم 100.
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
}
