<?php

namespace App\Http\Controllers\Api\PaperExams;

use App\Http\Controllers\Controller;
use App\Models\PaperExam;
use App\Models\PaperExamMark;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\My_Parent;
use App\Services\Exams\PaperExamPublishingService;
use App\Services\Pending\PendingActionRegistry;
use App\Services\Pending\PendingRequestService;
use App\Services\TeacherAssignmentService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaperExamController extends Controller
{
    protected PaperExamPublishingService $publishing;

    public function __construct()
    {
        $this->publishing = new PaperExamPublishingService();
    }
    /**
     * List all paper exams (with filters).
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $query = PaperExam::with(['subject', 'teacher', 'grade', 'classroom', 'section'])
                ->withCount('marks');

            // Teachers see only their own exams
            if ($user instanceof Teacher) {
                $query->where('teacher_id', $user->id);
            }

            if ($request->filled('subject_id')) {
                $query->where('subject_id', $request->subject_id);
            }
            if ($request->filled('grade_id')) {
                $query->where('grade_id', $request->grade_id);
            }
            if ($request->filled('classroom_id')) {
                $query->where('classroom_id', $request->classroom_id);
            }
            if ($request->filled('section_id')) {
                $query->where('section_id', $request->section_id);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $exams = $query->orderBy('exam_date', 'desc')->get();

            return response()->json([
                'success' => true,
                'message' => 'Paper exams retrieved successfully',
                'data' => $exams,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving paper exams',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single paper exam with marks.
     */
    public function show($id)
    {
        try {
            $exam = PaperExam::with([
                'subject', 'teacher', 'grade', 'classroom', 'section',
                'marks.student.grade', 'marks.student.classroom', 'marks.student.section',
            ])->withCount('marks')->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Paper exam retrieved successfully',
                'data' => $exam,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paper exam not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Create a new paper exam.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'exam_date' => 'required|date',
                'subject_id' => 'required|exists:subjects,id',
                'teacher_id' => 'required|exists:teachers,id',
                'grade_id' => 'required|exists:grades,id',
                'classroom_id' => 'required|exists:classrooms,id',
                'section_id' => 'required|exists:sections,id',
                'max_score' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
            ]);

            $exam = PaperExam::create($validated);

            $exam->load(['subject', 'teacher', 'grade', 'classroom', 'section']);

            return response()->json([
                'success' => true,
                'message' => 'Paper exam created successfully',
                'data' => $exam,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating paper exam',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a paper exam.
     */
    public function update(Request $request, $id)
    {
        try {
            $exam = PaperExam::findOrFail($id);

            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'exam_date' => 'sometimes|date',
                'subject_id' => 'sometimes|exists:subjects,id',
                'teacher_id' => 'sometimes|exists:teachers,id',
                'grade_id' => 'sometimes|exists:grades,id',
                'classroom_id' => 'sometimes|exists:classrooms,id',
                'section_id' => 'sometimes|exists:sections,id',
                'max_score' => 'nullable|numeric|min:0',
                'status' => 'sometimes|in:draft,published',
                'notes' => 'nullable|string',
            ]);

            $exam->update($validated);
            $exam->load(['subject', 'teacher', 'grade', 'classroom', 'section']);

            return response()->json([
                'success' => true,
                'message' => 'Paper exam updated successfully',
                'data' => $exam,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating paper exam',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a paper exam (cascades marks).
     */
    public function destroy($id)
    {
        try {
            $exam = PaperExam::findOrFail($id);
            $exam->delete();

            return response()->json([
                'success' => true,
                'message' => 'Paper exam deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting paper exam',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get students enrolled in the subject/classroom of this exam.
     */
    public function students($id)
    {
        try {
            $exam = PaperExam::findOrFail($id);

            $students = Student::where('Grade_id', $exam->grade_id)
                ->where('Classroom_id', $exam->classroom_id)
                ->where('section_id', $exam->section_id)
                ->with(['grade', 'classroom', 'section'])
                ->orderBy('name')
                ->get();

            // Attach existing marks if any
            $existingMarks = PaperExamMark::where('paper_exam_id', $id)
                ->get()
                ->keyBy('student_id');

            $students = $students->map(function ($student) use ($existingMarks) {
                $mark = $existingMarks->get($student->id);
                $student->marks_obtained = $mark ? $mark->marks_obtained : null;
                $student->mark_notes = $mark ? $mark->notes : null;
                $student->mark_id = $mark ? $mark->id : null;
                return $student;
            });

            return response()->json([
                'success' => true,
                'message' => 'Students retrieved successfully',
                'data' => $students,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving students',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save marks for all students in this exam (bulk upsert).
     */
    public function saveMarks(Request $request, $id)
    {
        try {
            $exam = PaperExam::findOrFail($id);

            $validated = $request->validate([
                'marks' => 'required|array',
                'marks.*.student_id' => 'required|exists:students,id',
                'marks.*.marks_obtained' => 'required|numeric|min:0',
                'marks.*.notes' => 'nullable|string|max:500',
            ]);

            DB::beginTransaction();

            foreach ($request->marks as $markData) {
                PaperExamMark::updateOrCreate(
                    [
                        'paper_exam_id' => $exam->id,
                        'student_id' => $markData['student_id'],
                    ],
                    [
                        'marks_obtained' => $markData['marks_obtained'],
                        'notes' => $markData['notes'] ?? null,
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Marks saved successfully',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error saving marks',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Publish exam results and notify students/parents.
     */
    public function publish(Request $request, $id)
    {
        try {
            $exam = PaperExam::with([
                'subject', 'teacher', 'grade', 'classroom', 'section',
                'marks.student.parent',
            ])->findOrFail($id);

            // Publishing marks to students and parents is a sensitive action:
            // anybody who is not an approver queues the request instead.
            if (PendingRequestService::requiresApproval($request->user(), PendingActionRegistry::SEND_MARKS)) {
                $pending = PendingRequestService::defer(PendingActionRegistry::SEND_MARKS, [
                    'entity'    => $exam,
                    'title'     => 'Publish marks of '.$exam->title,
                    'title_ar'  => 'نشر درجات اختبار '.$exam->title,
                    'description' => 'Publish the marks of "'.$exam->title.'" ('
                        .($exam->subject->Name ?? '—').') to students and their parents.',
                    'description_ar' => 'نشر درجات اختبار "'.$exam->title
                        .'" ('.($exam->subject->Name ?? '—').') للطلاب وأولياء الأمور.',
                    'reason'    => $request->input('reason'),
                    'priority'  => $request->input('priority'),
                ], $request->user());

                return PendingRequestService::deferredResponse($pending);
            }

            $exam = $this->publishing->publish($exam, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Exam published and notifications sent successfully',
                'data' => $exam,
            ], 200);
        } catch (\Exception $e) {
            return ApiResponse::fromException($e, 'Error publishing exam');
        }
    }

    /**
     * Teacher: Get subjects assigned to the authenticated teacher.
     */
    public function teacherSubjects(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user instanceof Teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only teachers can access this endpoint',
                ], 403);
            }

            // Resolve subjects the same way as every other teacher route, so a
            // subject assigned through section_subject_teacher shows up here too
            // and is not restricted to subjects the teacher happens to own.
            $subjectIds = $this->teacherAssignments()->subjectIds($user);

            $subjects = Subject::whereIn('id', $subjectIds ?: [-1])
                ->with(['grade', 'classroom', 'teacher'])
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Teacher subjects retrieved successfully',
                'data' => $subjects,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving teacher subjects',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Teacher: Get teacher's paper exams for a specific subject.
     */
    public function teacherExamsBySubject(Request $request, $subjectId)
    {
        try {
            $user = auth()->user();
            if (!$user instanceof Teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only teachers can access this endpoint',
                ], 403);
            }

            // Guard the subject before filtering exams so an unassigned subject
            // returns nothing instead of silently falling back to every subject.
            if (! $this->teacherAssignments()->teachesSubject($user, (int) $subjectId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This subject is not assigned to you',
                ], 403);
            }

            $exams = PaperExam::with(['subject', 'grade', 'classroom', 'section'])
                ->withCount('marks')
                ->where('teacher_id', $user->id)
                ->where('subject_id', $subjectId)
                ->orderBy('exam_date', 'desc')
                ->get();

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

    /**
     * Student: Get my paper exam marks across all subjects.
     */
    public function myMarks(Request $request)
    {
        try {
            $user = auth()->user();
            $studentId = null;

            if ($user instanceof \App\Models\Student) {
                $studentId = $user->id;
            } elseif ($user instanceof My_Parent) {
                // For parents, show marks for all their children
                $childrenIds = $user->students()->pluck('id')->toArray();
                if (empty($childrenIds)) {
                    return response()->json([
                        'success' => true,
                        'message' => 'No children found',
                        'data' => [],
                    ], 200);
                }
                $marks = PaperExamMark::with([
                    'paperExam.subject', 'paperExam.teacher', 'paperExam.grade',
                ])->whereIn('student_id', $childrenIds)
                    ->whereHas('paperExam', function ($q) {
                        $q->where('status', 'published');
                    })
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->groupBy('student_id');

                return response()->json([
                    'success' => true,
                    'message' => 'Marks retrieved successfully',
                    'data' => $marks,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Only students and parents can access this endpoint',
                ], 403);
            }

            $marks = PaperExamMark::with([
                'paperExam.subject', 'paperExam.teacher', 'paperExam.grade',
            ])->where('student_id', $studentId)
                ->whereHas('paperExam', function ($q) {
                    $q->where('status', 'published');
                })
                ->orderBy('created_at', 'desc')
                ->get();

            // Group by subject
            $grouped = $marks->groupBy(function ($mark) {
                return $mark->paperExam->subject_id;
            });

            return response()->json([
                'success' => true,
                'message' => 'Marks retrieved successfully',
                'data' => $grouped,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving marks',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Student/Parent: Get marks for a specific subject.
     */
    public function myMarksBySubject(Request $request, $subjectId)
    {
        try {
            $user = auth()->user();
            $studentId = null;

            if ($user instanceof \App\Models\Student) {
                $studentId = $user->id;
            } elseif ($user instanceof My_Parent) {
                $childrenIds = $user->students()->pluck('id')->toArray();
                if (empty($childrenIds)) {
                    return response()->json([
                        'success' => true,
                        'message' => 'No children found',
                        'data' => [],
                    ], 200);
                }
                $marks = PaperExamMark::with([
                    'paperExam.subject', 'paperExam.teacher', 'paperExam.grade',
                ])->whereIn('student_id', $childrenIds)
                    ->whereHas('paperExam', function ($q) use ($subjectId) {
                        $q->where('status', 'published')->where('subject_id', $subjectId);
                    })
                    ->orderBy('created_at', 'desc')
                    ->get();

                return response()->json([
                    'success' => true,
                    'message' => 'Marks retrieved successfully',
                    'data' => $marks,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Only students and parents can access this endpoint',
                ], 403);
            }

            $marks = PaperExamMark::with([
                'paperExam.subject', 'paperExam.teacher', 'paperExam.grade',
            ])->where('student_id', $studentId)
                ->whereHas('paperExam', function ($q) use ($subjectId) {
                    $q->where('status', 'published')->where('subject_id', $subjectId);
                })
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Marks retrieved successfully',
                'data' => $marks,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving marks',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Shared assignment resolution so the teacher endpoints stay consistent.
     */
    private function teacherAssignments(): TeacherAssignmentService
    {
        return app(TeacherAssignmentService::class);
    }

    /**
     * Helper: Get authenticated user info.
     */
    private function getCurrentUserInfo()
    {
        $user = auth()->user();
        $class = get_class($user);

        if (strpos($class, 'Student') !== false) {
            return ['student', 'App\Models\Student'];
        }
        if (strpos($class, 'Teacher') !== false) {
            return ['teacher', 'App\Models\Teacher'];
        }
        if (strpos($class, 'My_Parent') !== false || strpos($class, 'Parent') !== false) {
            return ['parent', 'App\Models\My_Parent'];
        }
        return ['admin', 'App\User'];
    }

    /**
     * Admin/Teacher: Get all paper exam marks for a specific student.
     */
    public function marksByStudent($studentId)
    {
        try {
            $marks = PaperExamMark::with([
                'paperExam.subject', 'paperExam.teacher', 'paperExam.grade',
                'paperExam.classroom', 'paperExam.section',
            ])
                ->where('student_id', $studentId)
                ->orderBy('created_at', 'desc')
                ->get();

            // Group by subject for easier display
            $grouped = $marks->groupBy(function ($mark) {
                return $mark->paperExam ? $mark->paperExam->subject_id : 0;
            });

            return response()->json([
                'success' => true,
                'message' => 'Student marks retrieved successfully',
                'data' => $grouped,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving student marks',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
