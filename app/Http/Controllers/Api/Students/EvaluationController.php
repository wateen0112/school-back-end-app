<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Grade;
use App\Models\Classroom;
use App\Models\Section;
use App\Services\TeacherAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EvaluationController extends Controller
{
    /**
     * Display a listing of evaluations with filtering.
     */
    public function index(Request $request)
    {
        try {
            $evaluations = Evaluation::with(['student', 'teacher', 'subject', 'grade', 'classroom', 'section'])
                ->when($request->filled('student_id'), function ($query) use ($request) {
                    $query->where('student_id', $request->student_id);
                })
                ->when($request->filled('teacher_id'), function ($query) use ($request) {
                    $query->where('teacher_id', $request->teacher_id);
                })
                ->when($request->filled('subject_id'), function ($query) use ($request) {
                    $query->where('subject_id', $request->subject_id);
                })
                ->when($request->filled('grade_id'), function ($query) use ($request) {
                    $query->where('grade_id', $request->grade_id);
                })
                ->when($request->filled('classroom_id'), function ($query) use ($request) {
                    $query->where('classroom_id', $request->classroom_id);
                })
                ->when($request->filled('section_id'), function ($query) use ($request) {
                    $query->where('section_id', $request->section_id);
                })
                ->when($request->filled('month'), function ($query) use ($request) {
                    $query->where('month', $request->month);
                })
                ->when($request->filled('year'), function ($query) use ($request) {
                    $query->where('year', $request->year);
                })
                ->when($request->filled('evaluation_level'), function ($query) use ($request) {
                    $query->where('evaluation_level', $request->evaluation_level);
                })
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->orderBy('id', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Evaluations retrieved successfully',
                'data' => $evaluations
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving evaluations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created evaluation.
     */
    public function store(Request $request)
    {
        // Auto-fill teacher_id from authenticated user before validation
        if (!$request->has('teacher_id') && auth()->check()) {
            $request->merge(['teacher_id' => auth()->id()]);
        }

        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'teacher_id' => 'required|exists:teachers,id',
            'subject_id' => 'required|exists:subjects,id',
            'grade_id' => 'required|exists:Grades,id',
            'classroom_id' => 'required|exists:Classrooms,id',
            'section_id' => 'required|exists:sections,id',
            'evaluation_level' => 'required|in:bad,good,very_good,excellent',
            'notes' => 'nullable|string|max:1000',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $scopeError = $this->assertTeacherCanEvaluate($request);
        if ($scopeError) {
            return $scopeError;
        }

        try {
            $data = $request->all();

            // Prevent duplicates: update if an evaluation already exists for the
            // same student, subject, month and year.
            $existing = Evaluation::where('student_id', $data['student_id'])
                ->where('subject_id', $data['subject_id'])
                ->where('month', $data['month'])
                ->where('year', $data['year'])
                ->first();

            if ($existing) {
                $existing->update([
                    'teacher_id' => $data['teacher_id'],
                    'grade_id' => $data['grade_id'],
                    'classroom_id' => $data['classroom_id'],
                    'section_id' => $data['section_id'],
                    'evaluation_level' => $data['evaluation_level'],
                    'notes' => $data['notes'] ?? $existing->notes,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Evaluation updated successfully',
                    'data' => $existing->load(['student', 'teacher', 'subject', 'grade', 'classroom', 'section'])
                ], 200);
            }

            $evaluation = Evaluation::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Evaluation created successfully',
                'data' => $evaluation->load(['student', 'teacher', 'subject', 'grade', 'classroom', 'section'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating evaluation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk store evaluations for multiple students at once.
     */
    public function bulkStore(Request $request)
    {
        // Auto-fill teacher_id for each evaluation from authenticated user before validation
        if ($request->has('evaluations') && is_array($request->evaluations)) {
            $evaluations = $request->evaluations;
            foreach ($evaluations as $key => $evalData) {
                if ((!isset($evalData['teacher_id']) || empty($evalData['teacher_id'])) && auth()->check()) {
                    $evaluations[$key]['teacher_id'] = auth()->id();
                }
            }
            $request->merge(['evaluations' => $evaluations]);
        }

        $validator = Validator::make($request->all(), [
            'evaluations' => 'required|array',
            'evaluations.*.student_id' => 'required|exists:students,id',
            'evaluations.*.teacher_id' => 'required|exists:teachers,id',
            'evaluations.*.subject_id' => 'required|exists:subjects,id',
            'evaluations.*.grade_id' => 'required|exists:Grades,id',
            'evaluations.*.classroom_id' => 'required|exists:Classrooms,id',
            'evaluations.*.section_id' => 'required|exists:sections,id',
            'evaluations.*.evaluation_level' => 'required|in:bad,good,very_good,excellent',
            'evaluations.*.notes' => 'nullable|string|max:1000',
            'evaluations.*.month' => 'required|integer|min:1|max:12',
            'evaluations.*.year' => 'required|integer|min:2000|max:2100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        foreach ($request->evaluations as $evalData) {
            $scopeError = $this->assertTeacherCanEvaluate(new Request($evalData));
            if ($scopeError) {
                return $scopeError;
            }
        }

        try {
            $created = [];
            $updated = [];
            $errors = [];

            foreach ($request->evaluations as $evalData) {
                try {
                    $existing = Evaluation::where('student_id', $evalData['student_id'])
                        ->where('subject_id', $evalData['subject_id'])
                        ->where('month', $evalData['month'])
                        ->where('year', $evalData['year'])
                        ->first();

                    if ($existing) {
                        $existing->update([
                            'evaluation_level' => $evalData['evaluation_level'],
                            'notes' => $evalData['notes'] ?? $existing->notes,
                            'teacher_id' => $evalData['teacher_id'],
                            'grade_id' => $evalData['grade_id'],
                            'classroom_id' => $evalData['classroom_id'],
                            'section_id' => $evalData['section_id'],
                        ]);
                        $updated[] = $existing->load(['student', 'teacher', 'subject', 'grade', 'classroom', 'section']);
                        continue;
                    }

                    $evaluation = Evaluation::create($evalData);
                    $created[] = $evaluation->load(['student', 'teacher', 'subject', 'grade', 'classroom', 'section']);
                } catch (\Exception $e) {
                    $errors[] = "Error processing student {$evalData['student_id']}: " . $e->getMessage();
                }
            }

            $total = count($created) + count($updated);
            return response()->json([
                'success' => true,
                'message' => $total > 0
                    ? 'Evaluations saved successfully'
                    : 'No evaluations were saved',
                'data' => [
                    'created_count' => count($created),
                    'updated_count' => count($updated),
                    'evaluations' => array_merge($created, $updated),
                    'errors' => $errors
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing bulk evaluations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified evaluation.
     */
    public function show($id)
    {
        try {
            $evaluation = Evaluation::with(['student', 'teacher', 'subject', 'grade', 'classroom', 'section'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Evaluation retrieved successfully',
                'data' => $evaluation
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Evaluation not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified evaluation.
     */
    public function update(Request $request, $id = null)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'sometimes|required|exists:students,id',
            'teacher_id' => 'sometimes|required|exists:teachers,id',
            'subject_id' => 'sometimes|required|exists:subjects,id',
            'grade_id' => 'sometimes|required|exists:Grades,id',
            'classroom_id' => 'sometimes|required|exists:Classrooms,id',
            'section_id' => 'sometimes|required|exists:sections,id',
            'evaluation_level' => 'sometimes|required|in:bad,good,very_good,excellent',
            'notes' => 'nullable|string|max:1000',
            'month' => 'sometimes|required|integer|min:1|max:12',
            'year' => 'sometimes|required|integer|min:2000|max:2100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $evaluation = Evaluation::findOrFail($request->input('id', $id));

            // Check for duplicate if month/year/subject/student changed
            if (
                ($request->has('student_id') && $request->student_id != $evaluation->student_id) ||
                ($request->has('subject_id') && $request->subject_id != $evaluation->subject_id) ||
                ($request->has('month') && $request->month != $evaluation->month) ||
                ($request->has('year') && $request->year != $evaluation->year)
            ) {
                $studentId = $request->input('student_id', $evaluation->student_id);
                $subjectId = $request->input('subject_id', $evaluation->subject_id);
                $month = $request->input('month', $evaluation->month);
                $year = $request->input('year', $evaluation->year);

                $existing = Evaluation::where('student_id', $studentId)
                    ->where('subject_id', $subjectId)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->where('id', '!=', $evaluation->id)
                    ->first();

                if ($existing) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Evaluation already exists for this student in this subject for the selected month and year',
                        'data' => $existing
                    ], 409);
                }
            }

            $evaluation->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Evaluation updated successfully',
                'data' => $evaluation->fresh(['student', 'teacher', 'subject', 'grade', 'classroom', 'section'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating evaluation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified evaluation.
     */
    public function destroy(Request $request, $id = null)
    {
        try {
            $evaluation = Evaluation::findOrFail($request->input('id', $id));
            $evaluation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Evaluation deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting evaluation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ensure a teacher only evaluates students/sections they actually teach.
     * Admins and other roles bypass this check.
     */
    private function assertTeacherCanEvaluate(Request $request)
    {
        $user = auth()->user();
        if (!$user instanceof Teacher) {
            return null;
        }

        $teacher = $user;
        $sectionId = $request->input('section_id');
        $subjectId = $request->input('subject_id');
        $studentId = $request->input('student_id');

        // Same resolution the listing endpoints use, so a teacher can never save
        // an evaluation for a section/subject pair they could not have loaded.
        $assignments = app(TeacherAssignmentService::class);

        if ($subjectId && ! $assignments->teachesSubject($teacher, (int) $subjectId)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned to the selected subject'
            ], 403);
        }

        if ($sectionId && ! $assignments->teachesSection(
            $teacher,
            (int) $sectionId,
            $subjectId ? (int) $subjectId : null
        )) {
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned to the selected section'
            ], 403);
        }

        if ($studentId && $sectionId) {
            $studentBelongs = Student::where('id', $studentId)
                ->where('section_id', $sectionId)
                ->exists();
            if (!$studentBelongs) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected student does not belong to the selected section'
                ], 409);
            }
        }

        return null;
    }

    // =====================
    // REPORT METHODS
    // =====================

    /**
     * Get evaluation report by student.
     */
    public function reportByStudent($studentId, Request $request)
    {
        try {
            $student = Student::with(['grade', 'classroom', 'section'])->findOrFail($studentId);

            $user = auth()->user();
            if ($user) {
                $userClass = get_class($user);
                $isParent = strpos($userClass, 'My_Parent') !== false || strpos($userClass, 'Parent') !== false;
                $isStudent = strpos($userClass, 'Student') !== false;
                if ($isParent && (int) $student->parent_id !== (int) $user->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized to view this student',
                    ], 403);
                }
                if ($isStudent && (int) $student->id !== (int) $user->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized to view this student',
                    ], 403);
                }
            }

            $evaluations = Evaluation::where('student_id', $studentId)
                ->with(['teacher', 'subject', 'grade', 'classroom', 'section'])
                ->when($request->filled('month'), function ($query) use ($request) {
                    $query->where('month', $request->month);
                })
                ->when($request->filled('year'), function ($query) use ($request) {
                    $query->where('year', $request->year);
                })
                ->when($request->filled('subject_id'), function ($query) use ($request) {
                    $query->where('subject_id', $request->subject_id);
                })
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();

            $stats = $this->calculateStats($evaluations);

            $payload = [
                'success' => true,
                'message' => 'Student evaluation report retrieved successfully',
                'data' => [
                    'student' => $student,
                    'evaluations' => $evaluations->map(function ($evaluation) {
                        return $this->compactEvaluation($evaluation);
                    })->values()->all(),
                    'statistics' => $stats
                ]
            ];

            return $this->jsonResponse($payload);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving student evaluation report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get evaluation report by teacher.
     */
    public function reportByTeacher($teacherId, Request $request)
    {
        try {
            $teacher = Teacher::findOrFail($teacherId);

            $evaluations = Evaluation::where('teacher_id', $teacherId)
                ->with(['student', 'subject', 'grade', 'classroom', 'section'])
                ->when($request->filled('month'), function ($query) use ($request) {
                    $query->where('month', $request->month);
                })
                ->when($request->filled('year'), function ($query) use ($request) {
                    $query->where('year', $request->year);
                })
                ->when($request->filled('subject_id'), function ($query) use ($request) {
                    $query->where('subject_id', $request->subject_id);
                })
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();

            $stats = $this->calculateStats($evaluations);

            return response()->json([
                'success' => true,
                'message' => 'Teacher evaluation report retrieved successfully',
                'data' => [
                    'teacher' => $teacher,
                    'evaluations' => $evaluations,
                    'statistics' => $stats
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving teacher evaluation report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get evaluation report by subject.
     */
    public function reportBySubject($subjectId, Request $request)
    {
        try {
            $subject = Subject::with(['grade', 'classroom', 'teacher'])->findOrFail($subjectId);

            $evaluations = Evaluation::where('subject_id', $subjectId)
                ->with(['student', 'teacher', 'grade', 'classroom', 'section'])
                ->when($request->filled('month'), function ($query) use ($request) {
                    $query->where('month', $request->month);
                })
                ->when($request->filled('year'), function ($query) use ($request) {
                    $query->where('year', $request->year);
                })
                ->when($request->filled('teacher_id'), function ($query) use ($request) {
                    $query->where('teacher_id', $request->teacher_id);
                })
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();

            $stats = $this->calculateStats($evaluations);

            return response()->json([
                'success' => true,
                'message' => 'Subject evaluation report retrieved successfully',
                'data' => [
                    'subject' => $subject,
                    'evaluations' => $evaluations,
                    'statistics' => $stats
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving subject evaluation report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get evaluation report by classroom.
     */
    public function reportByClassroom($classroomId, Request $request)
    {
        try {
            $classroom = Classroom::with('grade')->findOrFail($classroomId);

            $evaluations = Evaluation::where('classroom_id', $classroomId)
                ->with(['student', 'teacher', 'subject', 'grade', 'section'])
                ->when($request->filled('month'), function ($query) use ($request) {
                    $query->where('month', $request->month);
                })
                ->when($request->filled('year'), function ($query) use ($request) {
                    $query->where('year', $request->year);
                })
                ->when($request->filled('subject_id'), function ($query) use ($request) {
                    $query->where('subject_id', $request->subject_id);
                })
                ->when($request->filled('teacher_id'), function ($query) use ($request) {
                    $query->where('teacher_id', $request->teacher_id);
                })
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();

            $stats = $this->calculateStats($evaluations);

            // Group by student
            $byStudent = $evaluations->groupBy('student_id')->map(function ($items) {
                return [
                    'student' => $items->first()->student,
                    'evaluations_count' => $items->count(),
                    'average_level' => round($items->avg('level_value'), 2),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Classroom evaluation report retrieved successfully',
                'data' => [
                    'classroom' => $classroom,
                    'evaluations' => $evaluations,
                    'by_student' => $byStudent,
                    'statistics' => $stats
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving classroom evaluation report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get evaluation report by grade.
     */
    public function reportByGrade($gradeId, Request $request)
    {
        try {
            $grade = Grade::findOrFail($gradeId);

            $evaluations = Evaluation::where('grade_id', $gradeId)
                ->with(['student', 'teacher', 'subject', 'classroom', 'section'])
                ->when($request->filled('month'), function ($query) use ($request) {
                    $query->where('month', $request->month);
                })
                ->when($request->filled('year'), function ($query) use ($request) {
                    $query->where('year', $request->year);
                })
                ->when($request->filled('subject_id'), function ($query) use ($request) {
                    $query->where('subject_id', $request->subject_id);
                })
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();

            $stats = $this->calculateStats($evaluations);

            // Group by classroom
            $byClassroom = $evaluations->groupBy('classroom_id')->map(function ($items) {
                return [
                    'classroom' => $items->first()->classroom,
                    'evaluations_count' => $items->count(),
                    'average_level' => round($items->avg('level_value'), 2),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Grade evaluation report retrieved successfully',
                'data' => [
                    'grade' => $grade,
                    'evaluations' => $evaluations,
                    'by_classroom' => $byClassroom,
                    'statistics' => $stats
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving grade evaluation report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get evaluation report for parent - all their children.
     */
    public function reportForParent(Request $request)
    {
        \Log::info('[reportForParent] Request received', [
            'ip' => $request->ip(),
            'user_id' => auth()->id(),
            'user_class' => auth()->user() ? get_class(auth()->user()) : null,
            'month' => $request->month,
            'year' => $request->year,
        ]);

        try {
            $parent = auth()->user();

            if (!$parent) {
                \Log::warning('[reportForParent] No authenticated user');
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Get all children of this parent
            $students = Student::where('parent_id', $parent->id)
                ->with(['grade', 'classroom', 'section'])
                ->get();

            if ($students->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No children found for this parent',
                    'data' => []
                ], 200);
            }

            $studentIds = $students->pluck('id')->toArray();

            $evaluations = Evaluation::whereIn('student_id', $studentIds)
                ->with(['student', 'teacher', 'subject', 'grade', 'classroom', 'section'])
                ->when($request->filled('month'), function ($query) use ($request) {
                    $query->where('month', $request->month);
                })
                ->when($request->filled('year'), function ($query) use ($request) {
                    $query->where('year', $request->year);
                })
                ->when($request->filled('student_id'), function ($query) use ($request) {
                    $query->where('student_id', $request->student_id);
                })
                ->when($request->filled('subject_id'), function ($query) use ($request) {
                    $query->where('subject_id', $request->subject_id);
                })
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();

            // Group by student
            $byStudent = $evaluations->groupBy('student_id')->map(function ($items) {
                $student = $items->first()->student;
                $stats = $this->calculateStats($items);
                return [
                    'student' => $student,
                    'evaluations' => $items,
                    'statistics' => $stats
                ];
            });

            $overallStats = $this->calculateStats($evaluations);

            $payload = [
                'success' => true,
                'message' => 'Parent evaluation report retrieved successfully',
                'data' => [
                    'children' => $students,
                    'evaluations_by_student' => $byStudent,
                    'overall_statistics' => $overallStats
                ]
            ];

            // Ensure any buffered output is discarded so it cannot corrupt the JSON.
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            $json = json_encode($payload, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            return response($json, 200)->header('Content-Type', 'application/json');

        } catch (\Exception $e) {
            \Log::error('[reportForParent] Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving parent evaluation report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get monthly summary report.
     */
    public function monthlySummary(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'month' => 'required|integer|min:1|max:12',
                'year' => 'required|integer|min:2000|max:2100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors()
                ], 422);
            }

            $evaluations = Evaluation::where('month', $request->month)
                ->where('year', $request->year)
                ->with(['student', 'teacher', 'subject', 'grade', 'classroom', 'section'])
                ->when($request->filled('grade_id'), function ($query) use ($request) {
                    $query->where('grade_id', $request->grade_id);
                })
                ->when($request->filled('classroom_id'), function ($query) use ($request) {
                    $query->where('classroom_id', $request->classroom_id);
                })
                ->when($request->filled('teacher_id'), function ($query) use ($request) {
                    $query->where('teacher_id', $request->teacher_id);
                })
                ->get();

            $stats = $this->calculateStats($evaluations);

            // Group by subject
            $bySubject = $evaluations->groupBy('subject_id')->map(function ($items) {
                return [
                    'subject' => $items->first()->subject,
                    'evaluations_count' => $items->count(),
                    'average_level' => round($items->avg('level_value'), 2),
                ];
            });

            // Group by grade
            $byGrade = $evaluations->groupBy('grade_id')->map(function ($items) {
                return [
                    'grade' => $items->first()->grade,
                    'evaluations_count' => $items->count(),
                    'average_level' => round($items->avg('level_value'), 2),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Monthly summary report retrieved successfully',
                'data' => [
                    'month' => $request->month,
                    'year' => $request->year,
                    'evaluations' => $evaluations,
                    'by_subject' => $bySubject,
                    'by_grade' => $byGrade,
                    'statistics' => $stats
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving monthly summary report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper to calculate statistics from a collection of evaluations.
     */
    private function calculateStats($evaluations)
    {
        $total = $evaluations->count();
        if ($total === 0) {
            return [
                'total' => 0,
                'bad' => 0,
                'good' => 0,
                'very_good' => 0,
                'excellent' => 0,
                'bad_percentage' => 0,
                'good_percentage' => 0,
                'very_good_percentage' => 0,
                'excellent_percentage' => 0,
                'average_level' => 0,
            ];
        }

        $bad = $evaluations->where('evaluation_level', 'bad')->count();
        $good = $evaluations->where('evaluation_level', 'good')->count();
        $veryGood = $evaluations->where('evaluation_level', 'very_good')->count();
        $excellent = $evaluations->where('evaluation_level', 'excellent')->count();

        return [
            'total' => $total,
            'bad' => $bad,
            'good' => $good,
            'very_good' => $veryGood,
            'excellent' => $excellent,
            'bad_percentage' => round(($bad / $total) * 100, 2),
            'good_percentage' => round(($good / $total) * 100, 2),
            'very_good_percentage' => round(($veryGood / $total) * 100, 2),
            'excellent_percentage' => round(($excellent / $total) * 100, 2),
            'average_level' => round($evaluations->avg('level_value'), 2),
        ];
    }

    /**
     * Build a compact evaluation row. Keeps every field the app and admin
     * dashboard rely on while dropping the heavy nested models (and accidental
     * secrets like teacher password hashes). The full payload above ~95KB gets
     * truncated mid-transfer on mobile, which broke the evaluations page.
     */
    private function compactEvaluation(Evaluation $evaluation): array
    {
        $compact = function ($model, string $key): ?array {
            if (!$model) {
                return null;
            }

            return [
                'id' => (int) $model->id,
                $key => $model->getTranslations($key),
            ];
        };

        return [
            'id' => (int) $evaluation->id,
            'student_id' => (int) $evaluation->student_id,
            'teacher_id' => (int) $evaluation->teacher_id,
            'subject_id' => (int) $evaluation->subject_id,
            'grade_id' => (int) $evaluation->grade_id,
            'classroom_id' => (int) $evaluation->classroom_id,
            'section_id' => (int) $evaluation->section_id,
            'evaluation_level' => $evaluation->evaluation_level,
            'notes' => $evaluation->notes,
            'month' => (int) $evaluation->month,
            'year' => (int) $evaluation->year,
            'student' => $compact($evaluation->student, 'name'),
            'teacher' => $compact($evaluation->teacher, 'Name'),
            'subject' => $compact($evaluation->subject, 'Name'),
            'grade' => $compact($evaluation->grade, 'Name'),
            'classroom' => $compact($evaluation->classroom, 'Name_Class'),
            'section' => $compact($evaluation->section, 'Name_Section'),
        ];
    }

    /**
     * Build a framed JSON response with an explicit Content-Length and the body
     * optionally gzip-compressed (when the client advertises Accept-Encoding:
     * gzip). The PHP dev server is single-threaded and leaves large bodies
     * close-delimited, truncating them mid-transfer over keep-alive
     * connections (observed in the mobile app); gzip shrinks the payload so it
     * arrives reliably over slow/flaky links while keeping a correct framing.
     */
    private function jsonResponse(array $payload, int $status = 200)
    {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        $json = json_encode($payload, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $acceptsGzip = str_contains(request()->header('Accept-Encoding', ''), 'gzip');
        if ($acceptsGzip) {
            $compressed = gzencode($json, 6);

            return response($compressed, $status)
                ->header('Content-Type', 'application/json')
                ->header('Content-Encoding', 'gzip')
                ->header('Content-Length', (string) strlen($compressed))
                ->header('Vary', 'Accept-Encoding');
        }

        return response($json, $status)
            ->header('Content-Type', 'application/json')
            ->header('Content-Length', (string) strlen($json));
    }
}
