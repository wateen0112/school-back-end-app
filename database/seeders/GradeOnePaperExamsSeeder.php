<?php

namespace Database\Seeders;

use App\Models\AcademicTerm;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\PaperExam;
use App\Models\PaperExamMark;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeds paper exams and marks for "Grade 1" (the first classroom of the primary
 * stage) for BOTH semesters (TERM_1 and TERM_2), covering ALL subjects, so the
 * school transcript (الجلاء المدرسي) can be generated with real ناجح/راسب results.
 *
 * For every subject × section × term it creates the full exam set:
 *   - oral              (شفهي,        max 10)
 *   - written_review_1  (مذاكرة أولى,  max 10)
 *   - written_review_2  (مذاكرة ثانية, max 10)
 *   - coursework        (درجة أعمال,   max 20)
 *   - final_exam        (امتحان,       max 100)
 * and enters marks for every student enrolled in that section. Marks are
 * deterministic (seeded from student/subject ids): most students score
 * 65–95%, while every 3rd student fails some subjects (35–45%) so both
 * ناجح and راسب outcomes are represented.
 *
 * Idempotent: exams are matched by (term, section, subject, exam_type) and
 * marks by (exam, student), so re-running never duplicates anything.
 *
 * Usage:
 *   php artisan db:seed --class="Database\Seeders\GradeOnePaperExamsSeeder"
 */
class GradeOnePaperExamsSeeder extends Seeder
{
    /** exam_type => [ Arabic title suffix, max score ] */
    private const EXAM_TEMPLATE = [
        PaperExam::TYPE_ORAL             => ['شفهي', 10],
        PaperExam::TYPE_WRITTEN_REVIEW_1 => ['مذاكرة أولى', 10],
        PaperExam::TYPE_WRITTEN_REVIEW_2 => ['مذاكرة ثانية', 10],
        PaperExam::TYPE_COURSEWORK       => ['درجة أعمال', 20],
        PaperExam::TYPE_FINAL_EXAM       => ['امتحان', 100],
    ];

    public function run(): void
    {
        $grade = $this->resolvePrimaryGrade();
        $classroom = $this->resolveGradeOneClassroom($grade);
        $sections = Section::where('Class_id', $classroom->id)->get();

        if ($sections->isEmpty()) {
            throw new \RuntimeException("No sections found in classroom #{$classroom->id}.");
        }

        $terms = $this->resolveTerms();
        $subjects = $this->resolveSubjects();
        $teacher = Teacher::query()->orderBy('id')->first();

        if (! $teacher) {
            throw new \RuntimeException('No teacher exists yet — seed teachers first.');
        }

        $this->command?->info("Grade: {$grade->getTranslation('Name', 'en')} #{$grade->id}, Classroom #{$classroom->id}, Sections: {$sections->pluck('id')->implode(', ')}");
        $this->command?->info('Subjects: '.$subjects->map(fn ($s) => $s->getTranslation('Name', 'en') ?? $s->id)->implode(', '));

        $examCount = 0;
        $markCount = 0;

        foreach ($terms as $term) {
            $termStart = Carbon::parse($term->start_date ?? now());

            foreach ($sections as $section) {
                $students = Student::where('section_id', $section->id)
                    ->where('Classroom_id', $classroom->id)
                    ->get();

                foreach ($subjects as $subject) {
                    $subjectName = $subject->getTranslation('Name', 'ar')
                        ?? $subject->getTranslation('Name', 'en')
                        ?? ('مادة #'.$subject->id);

                    foreach (self::EXAM_TEMPLATE as $examType => [$suffix, $maxScore]) {
                        $exam = PaperExam::query()->updateOrCreate(
                            [
                                'academic_term_id' => $term->id,
                                'section_id'       => $section->id,
                                'subject_id'       => $subject->id,
                                'exam_type'        => $examType,
                            ],
                            [
                                'academic_year_id' => $term->academic_year_id,
                                'title'            => "{$subjectName} — {$suffix} — الفصل ".($term->getTranslation('name', 'ar') ?? $term->id),
                                'exam_date'        => $termStart->copy()->addDays($this->offsetFor($examType))->toDateString(),
                                'teacher_id'       => $teacher->id,
                                'grade_id'         => $grade->id,
                                'classroom_id'     => $classroom->id,
                                'max_score'        => $maxScore,
                                'status'           => 'published',
                            ]
                        );
                        $examCount++;

                        foreach ($students as $student) {
                            $percentage = $this->percentageFor($student->id, $subject->id, $term->id, $examType);
                            $mark = round($maxScore * $percentage / 100, 2);

                            PaperExamMark::query()->updateOrCreate(
                                ['paper_exam_id' => $exam->id, 'student_id' => $student->id],
                                ['marks_obtained' => $mark]
                            );
                            $markCount++;
                        }
                    }
                }
            }
        }

        $this->command?->info("Done. Exams touched: {$examCount}, marks upserted: {$markCount}.");
    }

    /** Primary stage grade (falls back to the first grade). */
    private function resolvePrimaryGrade(): Grade
    {
        $grade = Grade::query()->get()->first(fn ($g) =>
            str_contains(strtolower((string) $g->getTranslation('Name', 'en')), 'primary')
            || str_contains((string) $g->getTranslation('Name', 'ar'), 'ابتدائي'));

        return $grade ?? Grade::query()->orderBy('id')->firstOrFail();
    }

    /** The "Grade 1" classroom of the primary stage (falls back to its first classroom). */
    private function resolveGradeOneClassroom(Grade $grade): Classroom
    {
        $classroom = Classroom::where('Grade_id', $grade->id)->get()->first(fn ($c) =>
            str_contains(strtolower((string) $c->getTranslation('Name_Class', 'en')), 'grade 1')
            || str_contains((string) $c->getTranslation('Name_Class', 'ar'), 'الأول'));

        return $classroom
            ?? Classroom::where('Grade_id', $grade->id)->orderBy('id')->firstOrFail();
    }

    /** Both semesters; if a term is missing it is created on the current academic year. */
    private function resolveTerms(): array
    {
        $terms = AcademicTerm::query()
            ->whereIn('term_code', [AcademicTerm::CODE_TERM_1, AcademicTerm::CODE_TERM_2])
            ->orderBy('term_code')
            ->get();

        foreach ([AcademicTerm::CODE_TERM_1, AcademicTerm::CODE_TERM_2] as $i => $code) {
            if ($terms->firstWhere('term_code', $code)) {
                continue;
            }

            $year = \App\Models\AcademicYear::query()
                ->where('is_current', true)
                ->orWhere('status', 'active')
                ->orderByDesc('is_current')
                ->orderBy('id')
                ->first();

            if (! $year) {
                throw new \RuntimeException('No academic year exists — activate a year first.');
            }

            $base = now()->startOfYear()->addMonths($i * 6);
            $term = AcademicTerm::create([
                'academic_year_id' => $year->id,
                'name'             => ['ar' => $code === AcademicTerm::CODE_TERM_1 ? 'الفصل الأول' : 'الفصل الثاني', 'en' => $code === AcademicTerm::CODE_TERM_1 ? 'First Term' : 'Second Term'],
                'term_code'        => $code,
                'start_date'       => $base->copy()->startOfMonth()->toDateString(),
                'end_date'         => $base->copy()->addMonths(5)->endOfMonth()->toDateString(),
                'status'           => 'active',
                'is_current'       => $code === AcademicTerm::CODE_TERM_1,
            ]);

            $terms->push($term);
        }

        return $terms->sortBy('term_code')->values()->all();
    }

    /** All subjects, de-duplicated by their English name (first occurrence wins). */
    private function resolveSubjects()
    {
        $seen = [];
        $subjects = collect();

        foreach (Subject::query()->orderBy('id')->get() as $subject) {
            $key = strtolower((string) ($subject->getTranslation('Name', 'en') ?? $subject->id));
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $subjects->push($subject);
            }
        }

        return $subjects;
    }

    /** Spread exam dates across the term. */
    private function offsetFor(string $examType): int
    {
        return [
            PaperExam::TYPE_ORAL             => 14,
            PaperExam::TYPE_WRITTEN_REVIEW_1 => 30,
            PaperExam::TYPE_COURSEWORK       => 45,
            PaperExam::TYPE_WRITTEN_REVIEW_2 => 60,
            PaperExam::TYPE_FINAL_EXAM       => 75,
        ][$examType] ?? 30;
    }

    /**
     * Deterministic percentage (35–45% = failing, 65–95% = passing) so the
     * transcript shows a mix of ناجح / راسب results.
     */
    private function percentageFor(int $studentId, int $subjectId, int $termId, string $examType): float
    {
        $seed = ($studentId * 31 + $subjectId * 17 + $termId * 7 + crc32($examType)) % 100;
        $failsSomeSubjects = ($studentId % 3) === 0; // every 3rd student fails some subjects
        $isWeakSubject = ($seed % 4) === 0;

        if ($failsSomeSubjects && $isWeakSubject) {
            return 35 + ($seed % 11); // 35–45%
        }

        return 65 + ($seed % 31); // 65–95%
    }
}
