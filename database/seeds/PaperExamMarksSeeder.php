<?php

use App\Models\PaperExam;
use App\Models\PaperExamMark;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaperExamMarksSeeder extends Seeder
{
    /**
     * Seed published paper exams with marks for every current student.
     *
     * For each student a published exam (Midterm + Final) is created for
     * every subject of their classroom, with a deterministic realistic mark.
     * Existing paper exams/marks are wiped so the seeder is idempotent.
     *
     * Usage: php artisan db:seed --class=PaperExamMarksSeeder
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('paper_exam_marks')->delete();
        DB::table('paper_exams')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $students = Student::all();
        if ($students->isEmpty()) {
            $this->command->warn('No students found; skipping paper exam marks seeding.');
            return;
        }

        $fallbackTeacher = Teacher::first();
        $events = [
            ['en' => 'Midterm Exam', 'ar' => 'اختبار منتصف الفصل'],
            ['en' => 'Final Exam', 'ar' => 'الاختبار النهائي'],
        ];

        $createdMarks = 0;

        foreach ($students as $student) {
            $subjects = Subject::where('Grade_id', $student->Grade_id)
                ->where('Classroom_id', $student->Classroom_id)
                ->get();

            if ($subjects->isEmpty()) {
                continue;
            }

            foreach ($subjects as $subject) {
                foreach ($events as $eventIndex => $event) {
                    $exam = PaperExam::create([
                        'title' => $this->subjectName($subject) . ' - ' . $event['en'],
                        'exam_date' => now()->subDays(45 - $eventIndex * 15)->toDateString(),
                        'subject_id' => $subject->id,
                        'teacher_id' => $subject->teacher_id ?: ($fallbackTeacher ? $fallbackTeacher->id : 1),
                        'grade_id' => $student->Grade_id,
                        'classroom_id' => $student->Classroom_id,
                        'section_id' => $student->section_id,
                        'max_score' => 100,
                        'status' => 'published',
                    ]);

                    PaperExamMark::create([
                        'paper_exam_id' => $exam->id,
                        'student_id' => $student->id,
                        'marks_obtained' => $this->marksFor($student->id, $subject->id, $eventIndex),
                        'notes' => 'Finalized mid-year grading.',
                    ]);
                    $createdMarks++;
                }
            }
        }

        $this->command->info("Seeded {$createdMarks} paper exam mark(s) for {$students->count()} student(s).");
    }

    private function subjectName(Subject $subject): string
    {
        $name = $subject->getTranslation('Name', 'en') ?? $subject->Name;
        return is_array($name) ? ($name['en'] ?? $name['ar'] ?? 'Subject') : $name;
    }

    private function marksFor(int $studentId, int $subjectId, int $examIndex): float
    {
        // Deterministic realistic spread: 55 - 89 out of 100.
        return 55 + (($studentId * 7 + $subjectId * 13 + $examIndex * 11) % 35);
    }
}