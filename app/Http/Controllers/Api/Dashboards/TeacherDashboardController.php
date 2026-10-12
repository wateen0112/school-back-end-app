<?php

namespace App\Http\Controllers\Api\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Section;
use App\Models\Subject;
use App\Models\WeeklyProgram;
use App\Models\Attendance;
use App\Models\Evaluation;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class TeacherDashboardController extends Controller
{
    public function show(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user instanceof Teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only teachers can access this endpoint',
                ], 403);
            }

            $service = new DashboardService();

            $sectionIds = $this->teacherSectionIds($user);

            // Today's classes
            $today = (int) date('w');
            $todayPrograms = WeeklyProgram::with(['subject', 'section.Grades', 'section.My_classs'])
                ->where('teacher_id', $user->id)
                ->where('is_active', true)
                ->where('day_of_week', $today)
                ->orderBy('start_time')
                ->get()
                ->map(function ($p) use ($service) {
                    return [
                        'id'           => (int) $p->id,
                        'section_id'   => (int) $p->section_id,
                        'section_name' => $p->section ? $service->nameOf($p->section, 'Name_Section') : null,
                        'grade_name'   => $p->section && $p->section->Grades
                            ? $service->nameOf($p->section->Grades, 'Name') : null,
                        'classroom_name' => $p->section && $p->section->My_classs
                            ? $service->classroomNameOf($p->section->My_classs) : null,
                        'start_time'   => substr((string) $p->start_time, 0, 5),
                        'end_time'     => substr((string) $p->end_time, 0, 5),
                        'subject_name' => $p->subject ? $service->nameOf($p->subject) : '—',
                        'location'     => $p->location,
                    ];
                })->values();

            // Weekly distribution of the teacher's load
            $weeklyPrograms = WeeklyProgram::where('teacher_id', $user->id)
                ->where('is_active', true)
                ->get(['day_of_week']);

            $totalPeriods = $weeklyPrograms->count();
            $perDay = [];
            for ($d = 0; $d <= 6; $d++) {
                $perDay[$d] = [
                    'day_name'  => $service->dayName($d),
                    'count'     => $weeklyPrograms->where('day_of_week', $d)->count(),
                ];
            }

            $studentsCount = !empty($sectionIds)
                ? Student::whereIn('section_id', $sectionIds)->count()
                : 0;

            $recentAttendance = Attendance::where('teacher_id', $user->id)
                ->whereDate('attendence_date', '>=', now()->subDays(30)->toDateString())
                ->get(['attendence_status']);

            $attendancePresent = $recentAttendance->where('attendence_status', true)->count();
            $attendanceAbsent = $recentAttendance->where('attendence_status', false)->count();

            $recentEvaluations = Evaluation::with('student', 'subject')
                ->where('teacher_id', $user->id)
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(function ($e) use ($service) {
                    return [
                        'id'        => (int) $e->id,
                        'student'   => $e->student ? $service->nameOf($e->student, 'name') : '—',
                        'subject'   => $e->subject ? $service->nameOf($e->subject) : '—',
                        'level'     => $e->evaluation_level,
                        'month'     => (int) $e->month,
                        'year'      => (int) $e->year,
                    ];
                })->values();

            $sections = !empty($sectionIds)
                ? Section::with(['My_classs', 'Grades'])->whereIn('id', $sectionIds)->get()
                    ->map(function ($s) use ($service) {
                        return [
                            'id'             => (int) $s->id,
                            'name'           => $service->nameOf($s, 'Name_Section'),
                            'grade_name'     => $s->Grades ? $service->nameOf($s->Grades, 'Name') : null,
                            'classroom_name' => $s->My_classs ? $service->classroomNameOf($s->My_classs) : null,
                            'students_count' => Student::where('section_id', $s->id)->count(),
                        ];
                    })->values()
                : [];

            $availability = $user->weeklySchedules()->orderBy('day_of_week')->get()->map(function ($row) use ($service) {
                return [
                    'id'           => (int) $row->id,
                    'day_of_week'  => (int) $row->day_of_week,
                    'day_name'     => $service->dayName((int) $row->day_of_week),
                    'start_time'   => substr((string) $row->start_time, 0, 5),
                    'end_time'     => substr((string) $row->end_time, 0, 5),
                    'is_available' => (bool) $row->is_available,
                    'is_preferred' => (bool) $row->is_preferred,
                ];
            })->values();
            $preference = $user->preference;

            $data = [
                'teacher' => [
                    'id'         => (int) $user->id,
                    'name'       => $service->nameOf($user),
                    'specialization' => $user->specializations ? $service->nameOf($user->specializations) : null,
                ],
                'today' => [
                    'day_of_week' => $today,
                    'day_name'    => $service->dayName($today),
                    'classes'     => $todayPrograms,
                ],
                'workload' => [
                    'total_periods' => $totalPeriods,
                    'per_day'       => array_values($perDay),
                ],
                'students_count' => $studentsCount,
                'attendance_30d' => [
                    'present' => $attendancePresent,
                    'absent'  => $attendanceAbsent,
                ],
                'recent_evaluations' => $recentEvaluations,
                'sections'   => $sections,
                'availability' => $availability,
                'preference' => $preference ? [
                    'preferred_consecutive' => $preference->preferred_consecutive,
                    'max_consecutive'       => $preference->max_consecutive,
                    'max_periods_per_day'   => $preference->max_periods_per_day,
                    'preferred_start_time'  => $preference->preferred_start_time
                        ? substr($preference->preferred_start_time, 0, 5) : null,
                    'preferred_end_time'    => $preference->preferred_end_time
                        ? substr($preference->preferred_end_time, 0, 5) : null,
                    'avoid_days'            => $preference->avoid_days,
                ] : null,
                'notifications' => $service->notifications($user->id, Teacher::class),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Teacher dashboard retrieved successfully',
                'data'    => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving teacher dashboard',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private function teacherSectionIds(Teacher $teacher): array
    {
        $directSectionIds = $teacher->Sections()->pluck('sections.id')->toArray();

        $subjectClassroomIds = Subject::where('teacher_id', $teacher->id)
            ->pluck('Classroom_id')
            ->unique()
            ->toArray();

        $subjectSectionIds = !empty($subjectClassroomIds)
            ? Section::whereIn('Class_id', $subjectClassroomIds)->pluck('id')->toArray()
            : [];

        return array_unique(array_merge($directSectionIds, $subjectSectionIds));
    }
}