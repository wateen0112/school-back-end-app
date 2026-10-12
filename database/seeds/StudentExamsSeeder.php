<?php

use App\Models\Degree;
use App\Models\Question;
use App\Models\Quizze;
use App\Models\Student;
use App\Models\StudentAnswer;
use App\Models\Subject;
use App\Repository\StudentExamRepository;
use Illuminate\Database\Seeder;

class StudentExamsSeeder extends Seeder
{
    /**
     * Seed completed exams for a demo student so their exam list/results
     * have realistic data. Defaults to student1@school.local.
     *
     * Ensures the student's class has a quiz for each subject (creating
     * quizzes + questions when missing), then simulates a finished exam
     * for every quiz assigned to the student.
     *
     * Usage: php artisan db:seed --class=StudentExamsSeeder
     *
     * @return void
     */
    public function run()
    {
        $email = $this->command->hasArgument('student_email')
            ? $this->command->argument('student_email')
            : 'student1@school.local';

        $student = Student::where('email', $email)->first();

        if (! $student) {
            $this->command->error("Student not found with email: {$email}");
            return;
        }

        $subjects = Subject::where('Grade_id', $student->Grade_id)
            ->where('Classroom_id', $student->Classroom_id)
            ->get();

        if ($subjects->isEmpty()) {
            $this->command->error("No subjects found for student {$email} (grade {$student->Grade_id}, classroom {$student->Classroom_id}).");
            return;
        }

        // Make sure every subject of the student's class has a quiz in the
        // student's section, so the student always has exams to take.
        $created = 0;
        foreach ($subjects as $subject) {
            $quiz = Quizze::where('subject_id', $subject->id)
                ->where('section_id', $student->section_id)
                ->first();

            if (! $quiz) {
                $quiz = $this->createQuiz($subject, $student);
                $created++;
            }
        }

        $quizzes = Quizze::with('questions')
            ->where('grade_id', $student->Grade_id)
            ->where('classroom_id', $student->Classroom_id)
            ->where('section_id', $student->section_id)
            ->get();

        // Wipe any previous exam data for this student so the seeder is idempotent.
        $quizIds = $quizzes->pluck('id');
        Degree::where('student_id', $student->id)->whereIn('quizze_id', $quizIds)->delete();
        StudentAnswer::where('student_id', $student->id)->whereIn('quizze_id', $quizIds)->delete();

        $repository = new StudentExamRepository();

        foreach ($quizzes as $quiz) {
            $questions = $quiz->questions->sortBy('id')->values();
            if ($questions->isEmpty()) {
                continue;
            }

            $started = $repository->startExam($quiz->id, $student->id);
            if (! $started['success']) {
                continue;
            }

            foreach ($questions as $index => $question) {
                // Deterministic mix: answer correctly except every 3rd question.
                $answer = $index % 3 === 2
                    ? $this->wrongAnswer($question)
                    : $question->right_answer;

                $repository->submitAnswer($quiz->id, $student->id, $question->id, $answer);
            }

            $repository->finishExam($quiz->id, $student->id, 'submitted');
        }

        $names = $quizzes->map(fn (Quizze $quiz) => $quiz->getTranslation('name', 'en') ?? $quiz->name)->implode(', ');
        $this->command->info("Seeded {$quizzes->count()} completed exam(s) for {$email} ({$created} quiz(zes) created): {$names}");
    }

    private function createQuiz(Subject $subject, Student $student): Quizze
    {
        $name = $subject->getTranslation('Name', 'en') ?? $subject->Name;
        $name = is_array($name) ? ($name['en'] ?? $name['ar'] ?? 'Subject') : $name;

        $quiz = Quizze::create([
            'name' => ['en' => "{$name} Quiz", 'ar' => "اختبار {$name}"],
            'subject_id' => $subject->id,
            'grade_id' => $student->Grade_id,
            'classroom_id' => $student->Classroom_id,
            'section_id' => $student->section_id,
            'teacher_id' => $subject->teacher_id,
            'time_limit' => 15,
        ]);

        foreach ($this->questionsFor($name) as $question) {
            Question::create([
                'title' => $question['title'],
                'answers' => $question['answers'],
                'right_answer' => $question['right_answer'],
                'score' => $question['score'],
                'quizze_id' => $quiz->id,
            ]);
        }

        return $quiz;
    }

    private function questionsFor(string $subjectName): array
    {
        return [
            [
                'title' => "What is the main topic of {$subjectName}?",
                'answers' => 'Option A - Option B - Option C - Option D',
                'right_answer' => 'Option A',
                'score' => 10,
            ],
            [
                'title' => "Choose the best definition related to {$subjectName}.",
                'answers' => 'Definition 1 - Definition 2 - Definition 3 - Definition 4',
                'right_answer' => 'Definition 2',
                'score' => 10,
            ],
            [
                'title' => "Which statement about {$subjectName} is correct?",
                'answers' => 'Statement 1 - Statement 2 - Statement 3 - Statement 4',
                'right_answer' => 'Statement 3',
                'score' => 10,
            ],
        ];
    }

    private function wrongAnswer(Question $question): string
    {
        $answers = array_values(array_filter(
            array_map('trim', preg_split('/\s*-\s*/', (string) $question->answers)),
            fn ($item) => $item !== '' && strcmp(trim($item), trim($question->right_answer)) !== 0
        ));

        return $answers[0] ?? $question->right_answer;
    }
}