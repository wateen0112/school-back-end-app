<?php

namespace App\Http\Controllers\Api\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Evaluation;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class StudentDashboardController extends Controller
{
    public function show(Request $request)
    {
        try {
            $user = auth()->user();
            $student = null;

            if ($user instanceof Student) {
                $student = $user;
            } elseif ($request->filled('student_id')) {
                $student = Student::with(['grade', 'classroom', 'section'])->find($request->student_id);
            }

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found or unauthorized',
                ], 403);
            }

            $service = new DashboardService();
            $sectionId = $student->section_id ? (int) $student->section_id : null;
            $gradeId = $student->Grade_id ? (int) $student->Grade_id : null;

            $today = (int) date('w');

            // Existing evaluation-level summary (behavioural)
            $evaluations = Evaluation::with('subject')
                ->where('student_id', $student->id)
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(function ($e) use ($service) {
                    return [
                        'id'        => (int) $e->id,
                        'subject'   => $e->subject ? $service->nameOf($e->subject) : '—',
                        'level'     => $e->evaluation_level,
                        'month'     => (int) $e->month,
                        'year'      => (int) $e->year,
                    ];
                })->values();

            $data = [
                'student' => [
                    'id'             => (int) $student->id,
                    'name'           => $service->nameOf($student, 'name'),
                    'grade_name'     => $student->grade ? $service->nameOf($student->grade) : null,
                    'classroom_name' => $student->classroom ? $service->nameOf($student->classroom) : null,
                    'section_name'   => $student->section ? $service->nameOf($student->section, 'Name_Section') : null,
                ],
                'today' => [
                    'day_of_week' => $today,
                    'day_name'    => $service->dayName($today),
                    'schedule'    => $service->sectionSchedule($sectionId, $today),
                ],
                'week_schedule' => $service->sectionSchedule($sectionId),
                'attendance'    => $service->attendanceSummary($student->id),
                'grades'        => $service->paperExamGrades($student->id),
                'upcoming_exams'=> $service->upcomingExams((int) $sectionId),
                'recent_evaluations' => $evaluations,
                'learning_materials' => $service->learningMaterials((int) $sectionId, $gradeId),
                'notifications' => $service->notifications($student->id, Student::class),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Student dashboard retrieved successfully',
                'data'    => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving student dashboard',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}