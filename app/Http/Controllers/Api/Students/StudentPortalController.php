<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StudentPortalController extends Controller
{
    public function schedule(Request $request)
    {
        $student = $this->resolveStudent($request);
        if (!$student) {
            return $this->unauthorized();
        }

        try {
            $service = new DashboardService();
            $schedule = array_map(function (array $row) {
                $row['title'] = trim($row['day_name'] . ' ' . $row['start_time'] . ' - ' . $row['end_time']);
                return $row;
            }, $service->sectionSchedule((int) $student->section_id));

            return response()->json([
                'success' => true,
                'message' => 'My schedule retrieved successfully',
                'meta'    => ['day_count' => count($schedule)],
                'data'    => array_values($schedule),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving my schedule',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function grades(Request $request)
    {
        $student = $this->resolveStudent($request);
        if (!$student) {
            return $this->unauthorized();
        }

        try {
            $service = new DashboardService();
            $grades = $service->paperExamGrades($student->id);
            $subjects = array_map(function (array $row) {
                $row['name'] = $row['subject_name'];
                return $row;
            }, $grades['subjects']);

            return response()->json([
                'success' => true,
                'message' => 'My grades retrieved successfully',
                'meta'    => ['overall_percentage' => $grades['overall_percentage']],
                'data'    => array_values($subjects),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving my grades',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function attendance(Request $request)
    {
        $student = $this->resolveStudent($request);
        if (!$student) {
            return $this->unauthorized();
        }

        try {
            $records = Attendance::where('student_id', $student->id)
                ->orderByDesc('attendence_date')
                ->orderByDesc('id')
                ->get(['id', 'attendence_date', 'attendence_status'])
                ->map(function ($a) {
                    $date = $a->attendence_date
                        ? Carbon::parse($a->attendence_date)->format('Y-m-d')
                        : null;
                    return [
                        'id'     => (int) $a->id,
                        'title'  => $date,
                        'date'   => $date,
                        'status' => (bool) $a->attendence_status ? 'present' : 'absent',
                    ];
                })->values()->all();

            return response()->json([
                'success' => true,
                'message' => 'My attendance retrieved successfully',
                'meta'    => (new DashboardService())->attendanceSummary($student->id),
                'data'    => $records,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving my attendance',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private function resolveStudent(Request $request): ?Student
    {
        $user = auth()->user();

        if ($user instanceof Student) {
            return $user;
        }

        return $request->filled('student_id')
            ? Student::find($request->student_id)
            : null;
    }

    private function unauthorized()
    {
        return response()->json([
            'success' => false,
            'message' => 'Student not found or unauthorized',
        ], 403);
    }
}