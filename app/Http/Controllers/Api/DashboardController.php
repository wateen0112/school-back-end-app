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
use App\Models\Attendance;
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

            // Get students per section (with its class + level for the sections chart)
            $students_by_section = Section::query()
                ->select(
                    'sections.id',
                    'sections.Name_Section',
                    'Classrooms.Name_Class',
                    'Grades.Name as grade_name',
                    DB::raw('COALESCE(student_counts.count, 0) as count')
                )
                ->leftJoin('Classrooms', 'sections.Class_id', '=', 'Classrooms.id')
                ->leftJoin('Grades', 'sections.Grade_id', '=', 'Grades.id')
                ->leftJoin(
                    DB::raw('(SELECT section_id, COUNT(*) as count FROM students GROUP BY section_id) as student_counts'),
                    'sections.id',
                    '=',
                    'student_counts.section_id'
                )
                ->orderBy('sections.Grade_id')
                ->orderBy('sections.Class_id')
                ->orderBy('sections.id')
                ->get();

            // Get gender distribution
            $gender_distribution = Student::select('genders.Name as gender_name', DB::raw('count(*) as count'))
                ->join('genders', 'students.gender_id', '=', 'genders.id')
                ->groupBy('genders.id', 'genders.Name')
                ->get();

            // Get absence count per stage (level)
            $absence_by_grade = Attendance::select('Grades.Name as grade_name', DB::raw('count(*) as count'))
                ->join('Grades', 'attendances.grade_id', '=', 'Grades.id')
                ->where('attendence_status', 0)
                ->groupBy('Grades.id', 'Grades.Name')
                ->orderBy('Grades.id')
                ->get();

            // Get attendance / absence summary
            $present_count = Attendance::where('attendence_status', 1)->count();
            $absent_count = Attendance::where('attendence_status', 0)->count();
            $attendance_total = $present_count + $absent_count;
            $attendance_summary = [
                'present' => $present_count,
                'absent' => $absent_count,
                'total' => $attendance_total,
                'present_rate' => $attendance_total > 0 ? round(($present_count / $attendance_total) * 100) : 0,
                'absent_rate' => $attendance_total > 0 ? round(($absent_count / $attendance_total) * 100) : 0,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Dashboard data retrieved successfully',
                'user' => $user,
                'stats' => $stats,
                'data' => [
                    'stats' => $stats,
                    'recent_students' => $recent_students,
                    'students_by_grade' => $students_by_grade,
                    'students_by_section' => $students_by_section,
                    'gender_distribution' => $gender_distribution,
                    'absence_by_grade' => $absence_by_grade,
                    'attendance_summary' => $attendance_summary,
                ],
                'recent_students' => $recent_students,
                'students_by_grade' => $students_by_grade,
                'students_by_section' => $students_by_section,
                'gender_distribution' => $gender_distribution,
                'absence_by_grade' => $absence_by_grade,
                'attendance_summary' => $attendance_summary
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
