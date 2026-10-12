<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Repository\StudentExamRepositoryInterface;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StudentExamController extends Controller
{
    protected $studentExamRepository;

    public function __construct(StudentExamRepositoryInterface $studentExamRepository)
    {
        $this->studentExamRepository = $studentExamRepository;
    }

    public function index()
    {
        try {
            $studentId = auth()->id();
            $exams = $this->studentExamRepository->listForStudent($studentId);

            return response()->json([
                'success' => true,
                'message' => 'Exams retrieved successfully',
                'data' => $exams,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving exams',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function teacherExams()
    {
        try {
            // auth()->id() alone is ambiguous: student, teacher and parent all
            // share one guard, so an id collision would return another actor's
            // exams. Require an actual Teacher.
            $teacher = auth()->user();

            if (! $teacher instanceof Teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only teachers can access this endpoint',
                ], 403);
            }

            $exams = $this->studentExamRepository->listForTeacher($teacher->id);

            return response()->json([
                'success' => true,
                'message' => 'Teacher exams retrieved successfully',
                'data' => $exams,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving teacher exams',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($quizzeId)
    {
        try {
            $studentId = auth()->id();
            $exam = $this->studentExamRepository->getWithQuestions($quizzeId, $studentId);

            return response()->json([
                'success' => true,
                'message' => 'Exam retrieved successfully',
                'data' => $exam,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving exam',
                'error' => $e->getMessage(),
            ], $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
        }
    }

    public function start($quizzeId)
    {
        try {
            $studentId = auth()->id();
            $result = $this->studentExamRepository->startExam($quizzeId, $studentId);

            if (! ($result['success'] ?? false)) {
                return ApiResponse::conflict($result['message'] ?? 'Could not start exam');
            }

            return ApiResponse::ok(
                $this->withoutSuccess($result),
                $result['message'] ?? 'Exam started successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::serverError('Error starting exam', $e->getMessage());
        }
    }

    public function submitAnswer(Request $request, $quizzeId)
    {
        $validator = Validator::make($request->all(), [
            'question_id' => 'required|exists:questions,id',
            'answer' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        try {
            $studentId = auth()->id();
            $result = $this->studentExamRepository->submitAnswer(
                $quizzeId,
                $studentId,
                (int) $request->question_id,
                $request->answer
            );

            if (! ($result['success'] ?? false)) {
                return ApiResponse::conflict($result['message'] ?? 'Could not submit answer');
            }

            return ApiResponse::ok(
                $this->withoutSuccess($result),
                $result['message'] ?? 'Answer submitted successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::serverError('Error submitting answer', $e->getMessage());
        }
    }

    public function submit($quizzeId)
    {
        try {
            $studentId = auth()->id();
            $result = $this->studentExamRepository->finishExam($quizzeId, $studentId);

            if (! ($result['success'] ?? false)) {
                return ApiResponse::conflict($result['message'] ?? 'Could not submit exam');
            }

            return ApiResponse::ok(
                $this->withoutSuccess($result),
                $result['message'] ?? 'Exam submitted successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::serverError('Error submitting exam', $e->getMessage());
        }
    }

    /** Strip the repository's own `success` flag so it cannot leak into `data`. */
    private function withoutSuccess(array $result): array
    {
        unset($result['success'], $result['message']);

        return $result;
    }

    public function result($quizzeId)
    {
        try {
            $studentId = auth()->id();
            $result = $this->studentExamRepository->getResult($quizzeId, $studentId);

            return response()->json([
                'success' => true,
                'message' => 'Exam result retrieved successfully',
                'data' => $result,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving exam result',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
