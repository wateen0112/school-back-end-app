<?php

namespace App\Repository;

use App\Models\Degree;
use App\Models\Question;
use App\Models\Quizze;
use App\Models\Student;
use App\Models\StudentAnswer;
use Illuminate\Support\Facades\DB;

class StudentExamRepository implements StudentExamRepositoryInterface
{
    public function listForStudent(int $studentId): array
    {
        $student = Student::findOrFail($studentId);

        $quizzes = Quizze::with(['subject', 'grade', 'classroom', 'section'])
            ->where('grade_id', $student->Grade_id)
            ->where('classroom_id', $student->Classroom_id)
            ->where('section_id', $student->section_id)
            ->get();

        return $quizzes->map(function (Quizze $quiz) use ($studentId) {
            $degree = $quiz->degree()->where('student_id', $studentId)->first();

            return [
                'id' => $quiz->id,
                'name' => $quiz->getTranslation('name', app()->getLocale()) ?? $quiz->name,
                'subject' => $quiz->subject?->getTranslation('Name', app()->getLocale()) ?? $quiz->subject?->Name,
                'grade' => $quiz->grade?->getTranslation('Name', app()->getLocale()) ?? $quiz->grade?->Name,
                'classroom' => $this->classroomName($quiz->classroom),
                'section' => $quiz->section?->getTranslation('Name_Section', app()->getLocale()) ?? $quiz->section?->Name_Section,
                'time_limit' => (int) $quiz->time_limit,
                'questions_count' => $quiz->questions()->count(),
                'status' => $degree?->status ?? 'not_started',
                'score' => $degree?->status === 'submitted' ? (float) $degree->score : null,
                'started_at' => $degree?->started_at?->toDateTimeString(),
                'submitted_at' => $degree?->submitted_at?->toDateTimeString(),
            ];
        })->toArray();
    }

    public function listForTeacher(int $teacherId): array
    {
        $quizzes = Quizze::with(['subject', 'grade', 'classroom', 'section'])
            ->where('teacher_id', $teacherId)
            ->get();

        return $quizzes->map(function (Quizze $quiz) {
            $attempted = $quiz->degree()->where('status', 'submitted')->count();

            return [
                'id' => $quiz->id,
                'name' => $quiz->getTranslation('name', app()->getLocale()) ?? $quiz->name,
                'subject' => $quiz->subject?->getTranslation('Name', app()->getLocale()) ?? $quiz->subject?->Name,
                'grade' => $quiz->grade?->getTranslation('Name', app()->getLocale()) ?? $quiz->grade?->Name,
                'classroom' => $this->classroomName($quiz->classroom),
                'section' => $quiz->section?->getTranslation('Name_Section', app()->getLocale()) ?? $quiz->section?->Name_Section,
                'teacher_id' => (int) $quiz->teacher_id,
                'time_limit' => (int) $quiz->time_limit,
                'questions_count' => $quiz->questions()->count(),
                'status' => $attempted > 0 ? 'submitted' : 'not_started',
                'score' => null,
                'started_at' => null,
                'submitted_at' => null,
            ];
        })->toArray();
    }

    public function getWithQuestions(int $quizzeId, ?int $studentId = null): array
    {
        $quiz = Quizze::with(['subject', 'grade', 'classroom', 'section', 'questions'])->findOrFail($quizzeId);

        $degree = $studentId
            ? $quiz->degree()->where('student_id', $studentId)->first()
            : null;

        return [
            'id' => $quiz->id,
            'name' => $quiz->getTranslation('name', app()->getLocale()) ?? $quiz->name,
            'subject' => $quiz->subject?->getTranslation('Name', app()->getLocale()) ?? $quiz->subject?->Name,
            'grade' => $quiz->grade?->getTranslation('Name', app()->getLocale()) ?? $quiz->grade?->Name,
            'classroom' => $this->classroomName($quiz->classroom),
            'section' => $quiz->section?->getTranslation('Name_Section', app()->getLocale()) ?? $quiz->section?->Name_Section,
            'time_limit' => (int) $quiz->time_limit,
            'status' => $degree?->status ?? 'not_started',
            'score' => $degree?->status === 'submitted' ? (float) $degree->score : null,
            'questions' => $quiz->questions->map(function (Question $question) {
                return [
                    'id' => $question->id,
                    'title' => $question->title,
                    'answers' => $this->parseAnswers($question->answers),
                    'score' => (float) $question->score,
                ];
            })->values()->toArray(),
        ];
    }

    public function startExam(int $quizzeId, int $studentId): array
    {
        $quiz = Quizze::findOrFail($quizzeId);
        $student = Student::findOrFail($studentId);

        $this->ensureStudentMatchesQuiz($quiz, $student);

        $existing = Degree::where('quizze_id', $quizzeId)
            ->where('student_id', $studentId)
            ->first();

        if ($existing && in_array($existing->status, ['submitted', 'timeout', 'abuse'], true)) {
            return [
                'success' => false,
                'message' => 'exam.already_finished',
                'degree' => $this->mapDegree($existing),
            ];
        }

        $degree = $existing ?: new Degree();
        $degree->quizze_id = $quizzeId;
        $degree->student_id = $studentId;
        $degree->academic_year_id = $quiz->academic_year_id;
        $degree->question_id = null;
        $degree->score = 0;
        $degree->abuse = '0';
        $degree->date = now()->format('Y-m-d');
        $degree->started_at = now();
        $degree->status = 'started';
        $degree->save();

        return [
            'success' => true,
            'degree' => $this->mapDegree($degree),
        ];
    }

    public function submitAnswer(int $quizzeId, int $studentId, int $questionId, string $answer): array
    {
        $quiz = Quizze::findOrFail($quizzeId);
        $question = Question::where('quizze_id', $quizzeId)->findOrFail($questionId);
        $student = Student::findOrFail($studentId);

        $this->ensureStudentMatchesQuiz($quiz, $student);

        $degree = Degree::where('quizze_id', $quizzeId)
            ->where('student_id', $studentId)
            ->first();

        if (! $degree || $degree->status !== 'started') {
            return [
                'success' => false,
                'message' => 'exam.not_started',
            ];
        }

        if ($this->isTimedOut($degree, $quiz)) {
            $this->finishExam($quizzeId, $studentId, 'timeout');
            return [
                'success' => false,
                'message' => 'exam.time_is_up',
                'status' => 'timeout',
            ];
        }

        if ($degree->question_id >= $questionId) {
            $degree->score = 0;
            $degree->abuse = '1';
            $degree->status = 'abuse';
            $degree->submitted_at = now();
            $degree->save();

            return [
                'success' => false,
                'message' => 'exam.abuse_detected',
                'status' => 'abuse',
            ];
        }

        $isCorrect = strcmp(trim($answer), trim($question->right_answer)) === 0;
        $score = $isCorrect ? (float) $question->score : 0;

        DB::transaction(function () use ($quizzeId, $studentId, $questionId, $answer, $isCorrect, $score, $degree, $question) {
            StudentAnswer::updateOrCreate(
                [
                    'quizze_id' => $quizzeId,
                    'student_id' => $studentId,
                    'question_id' => $questionId,
                ],
                [
                    'selected_answer' => $answer,
                    'is_correct' => $isCorrect,
                    'score' => $score,
                ]
            );

            $degree->question_id = $questionId;
            $degree->score += $score;
            $degree->save();
        });

        return [
            'success' => true,
            'is_correct' => $isCorrect,
            'score' => $score,
            'degree' => $this->mapDegree($degree->fresh()),
        ];
    }

    public function finishExam(int $quizzeId, int $studentId, ?string $status = null): array
    {
        $degree = Degree::where('quizze_id', $quizzeId)
            ->where('student_id', $studentId)
            ->first();

        if (! $degree) {
            return [
                'success' => false,
                'message' => 'exam.not_started',
            ];
        }

        if (in_array($degree->status, ['submitted', 'timeout', 'abuse'], true)) {
            return [
                'success' => false,
                'message' => 'exam.already_finished',
                'degree' => $this->mapDegree($degree),
            ];
        }

        $quiz = Quizze::findOrFail($quizzeId);
        $finalStatus = $status ?: ($this->isTimedOut($degree, $quiz) ? 'timeout' : 'submitted');

        $degree->submitted_at = now();
        $degree->time_spent_seconds = (int) $degree->started_at->diffInSeconds(now());
        $degree->status = $finalStatus;
        $degree->save();

        return [
            'success' => true,
            'degree' => $this->mapDegree($degree),
        ];
    }

    public function getResult(int $quizzeId, int $studentId): array
    {
        $quiz = Quizze::with('questions')->findOrFail($quizzeId);
        $degree = Degree::where('quizze_id', $quizzeId)
            ->where('student_id', $studentId)
            ->firstOrFail();

        $studentAnswers = StudentAnswer::where('quizze_id', $quizzeId)
            ->where('student_id', $studentId)
            ->get()
            ->keyBy('question_id');

        $maxScore = $quiz->questions->sum('score');

        return [
            'quiz' => [
                'id' => $quiz->id,
                'name' => $quiz->getTranslation('name', app()->getLocale()) ?? $quiz->name,
                'subject' => $quiz->subject?->getTranslation('Name', app()->getLocale()) ?? $quiz->subject?->Name,
                'time_limit' => (int) $quiz->time_limit,
            ],
            'total_score' => (float) $degree->score,
            'max_score' => (float) $maxScore,
            'percentage' => $maxScore > 0 ? round(($degree->score / $maxScore) * 100, 2) : 0,
            'time_spent_seconds' => (int) $degree->time_spent_seconds,
            'status' => $degree->status,
            'questions' => $quiz->questions->map(function (Question $question) use ($studentAnswers) {
                $studentAnswer = $studentAnswers->get($question->id);

                return [
                    'id' => $question->id,
                    'title' => $question->title,
                    'answers' => $this->parseAnswers($question->answers),
                    'right_answer' => $question->right_answer,
                    'score' => (float) $question->score,
            'student_answer' => $studentAnswer?->selected_answer,
                    'is_correct' => $studentAnswer?->is_correct ?? false,
                    'student_score' => (float) ($studentAnswer?->score ?? 0),
                ];
            })->values()->toArray(),
        ];
    }

    private function ensureStudentMatchesQuiz(Quizze $quiz, Student $student): void
    {
        if (
            $quiz->grade_id !== $student->Grade_id ||
            $quiz->classroom_id !== $student->Classroom_id ||
            $quiz->section_id !== $student->section_id
        ) {
            abort(403, 'exam.not_allowed');
        }
    }

    private function isTimedOut(Degree $degree, Quizze $quiz): bool
    {
        if (! $degree->started_at || ! $quiz->time_limit) {
            return false;
        }

        $deadline = $degree->started_at->copy()->addMinutes((int) $quiz->time_limit);
        return now()->greaterThan($deadline);
    }

    private function parseAnswers(?string $answers): array
    {
        if (empty($answers)) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', preg_split('/\s*-\s*/', $answers)),
            fn ($item) => $item !== ''
        ));
    }

    private function classroomName($classroom): ?string
    {
        if (! $classroom) {
            return null;
        }

        $name = $classroom->getTranslation('Name_Class', app()->getLocale()) ?? $classroom->Name_Class;
        return is_array($name) ? ($name[app()->getLocale()] ?? $name['en'] ?? reset($name)) : $name;
    }

    private function mapDegree(Degree $degree): array
    {
        return [
            'id' => $degree->id,
            'score' => (float) $degree->score,
            'status' => $degree->status,
            'abuse' => $degree->abuse,
            'started_at' => $degree->started_at?->toDateTimeString(),
            'submitted_at' => $degree->submitted_at?->toDateTimeString(),
            'time_spent_seconds' => (int) $degree->time_spent_seconds,
        ];
    }
}
