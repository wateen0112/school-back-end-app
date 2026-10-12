<?php

namespace App\Repository;

interface StudentExamRepositoryInterface
{
    /**
     * List quizzes available for a student (matching grade/classroom/section)
     * with their current attempt status and score.
     */
    public function listForStudent(int $studentId): array;

    /**
     * List quizzes created by a teacher (scoped to the logged-in teacher).
     */
    public function listForTeacher(int $teacherId): array;

    /**
     * Get a single quiz with its questions for the student.
     */
    public function getWithQuestions(int $quizzeId, ?int $studentId = null): array;

    /**
     * Start a new exam attempt for the student.
     */
    public function startExam(int $quizzeId, int $studentId): array;

    /**
     * Submit an answer for a single question.
     */
    public function submitAnswer(int $quizzeId, int $studentId, int $questionId, string $answer): array;

    /**
     * Finish the exam and calculate final stats.
     */
    public function finishExam(int $quizzeId, int $studentId): array;

    /**
     * Get the final result with questions, correct answers and student answers.
     */
    public function getResult(int $quizzeId, int $studentId): array;
}
