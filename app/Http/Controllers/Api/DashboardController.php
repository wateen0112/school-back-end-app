<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Section;
use App\Models\My_Parent;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $user = auth()->user();
            
            // Get real counts from database
            $stats = [
                'students_count' => Student::count(),
                'teachers_count' => Teacher::count(),
                'parents_count' => My_Parent::count(),
                'grades_count' => Grade::count(),
                'classrooms_count' => Classroom::count(),
                'classes_count' => Classroom::count(),
                'sections_count' => Section::count(),
                'subjects_count' => Subject::count(),
            ];

            // Get recent students
            $recent_students = Student::with(['grade', 'classroom', 'section'])
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            // Get students by grade distribution
            $students_by_grade = Student::select('Grades.Name as grade_name', DB::raw('count(*) as count'))
                ->join('Grades', 'students.Grade_id', '=', 'Grades.id')
                ->groupBy('Grades.id', 'Grades.Name')
                ->get();

            // Get gender distribution
            $gender_distribution = Student::select('genders.Name as gender_name', DB::raw('count(*) as count'))
                ->join('genders', 'students.gender_id', '=', 'genders.id')
                ->groupBy('genders.id', 'genders.Name')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Dashboard data retrieved successfully',
                'user' => $user,
                'stats' => $stats,
                'data' => [
                    'stats' => $stats,
                    'recent_students' => $recent_students,
                    'students_by_grade' => $students_by_grade,
                    'gender_distribution' => $gender_distribution,
                ],
                'recent_students' => $recent_students,
                'students_by_grade' => $students_by_grade,
                'gender_distribution' => $gender_distribution
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function stats()
    {
        try {
            // Get detailed statistics
            $stats = [
                'total_students' => Student::count(),
                'total_teachers' => Teacher::count(),
                'total_parents' => My_Parent::count(),
                'total_grades' => Grade::count(),
                'total_classrooms' => Classroom::count(),
                'total_classes' => Classroom::count(),
                'total_sections' => Section::count(),
                'total_subjects' => Subject::count(),
            ];

            // Get active students (created in last 30 days)
            $active_students = Student::where('created_at', '>=', now()->subDays(30))->count();

            // Get students per grade
            $students_per_grade = Grade::select('Grades.*', DB::raw('COALESCE(student_counts.count, 0) as students_count'))
                ->leftJoin(DB::raw('(SELECT Grade_id, COUNT(*) as count FROM students GROUP BY Grade_id) as student_counts'), 'Grades.id', '=', 'student_counts.Grade_id')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Statistics retrieved successfully',
                'stats' => $stats,
                'active_students' => $active_students,
                'students_per_grade' => $students_per_grade
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
