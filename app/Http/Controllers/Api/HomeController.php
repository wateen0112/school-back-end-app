<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Grade;
use App\Models\Classroom;
use App\Models\Section;
use App\Models\Teacher;
use App\Models\Subject;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function dashboard(Request $request)
    {
        try {
            $user = $request->user();
            
            $stats = [
                'students_count' => Student::count(),
                'grades_count' => Grade::count(),
                'classrooms_count' => Classroom::count(),
                'sections_count' => Section::count(),
                'teachers_count' => Teacher::count(),
                'subjects_count' => Subject::count(),
            ];

            $recent_students = Student::with(['grade', 'classroom', 'section'])
                ->latest()
                ->take(5)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Dashboard data retrieved successfully',
                'user' => $user,
                'stats' => $stats,
                'recent_students' => $recent_students
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function publicInfo()
    {
        try {
            $public_stats = [
                'grades_count' => Grade::count(),
                'classrooms_count' => Classroom::count(),
                'sections_count' => Section::count(),
                'teachers_count' => Teacher::count(),
                'subjects_count' => Subject::count(),
            ];

            $grades = Grade::select('id', 'Name')->get();
            $subjects = Subject::select('id', 'Name')->get();

            return response()->json([
                'success' => true,
                'message' => 'Public information retrieved successfully',
                'stats' => $public_stats,
                'grades' => $grades,
                'subjects' => $subjects
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving public information',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
