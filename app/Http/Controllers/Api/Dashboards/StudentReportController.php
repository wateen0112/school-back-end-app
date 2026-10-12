<?php

namespace App\Http\Controllers\Api\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\My_Parent;
use App\Models\Student;
use App\Models\StudentMonthlyReport;
use App\Services\Pending\PendingActionRegistry;
use App\Services\Pending\PendingRequestService;
use App\Services\Scoping\SchoolContext;
use App\Services\StudentReportService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentReportController extends Controller
{
    protected StudentReportService $service;

    public function __construct()
    {
        $this->service = new StudentReportService();
    }

    /**
     * Admin: list reports with optional filters.
     */
    public function index(Request $request)
    {
        $reports = StudentMonthlyReport::with(['student.grade', 'student.classroom', 'student.section'])
            ->when($request->filled('month'), fn ($query) => $query->where('month', $request->integer('month')))
            ->when($request->filled('year'), fn ($query) => $query->where('year', $request->integer('year')))
            ->when($request->filled('grade_id'), function ($query) use ($request) {
                $query->whereHas('student', fn ($studentQuery) => $studentQuery->where('Grade_id', $request->integer('grade_id')));
            })
            ->when($request->filled('classroom_id'), function ($query) use ($request) {
                $query->whereHas('student', fn ($studentQuery) => $studentQuery->where('Classroom_id', $request->integer('classroom_id')));
            })
            ->when($request->filled('student_id'), fn ($query) => $query->where('student_id', $request->integer('student_id')))
            ->withCount('student as students_count')
            ->latest('year')
            ->latest('month')
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Student monthly reports retrieved successfully',
            'data' => [
                'reports' => collect($reports->items())->map(fn ($report) => $this->service->present($report)),
                'pagination' => [
                    'current_page' => $reports->currentPage(),
                    'last_page' => $reports->lastPage(),
                    'per_page' => $reports->perPage(),
                    'total' => $reports->total(),
                ],
            ],
        ], 200);
    }

    /**
     * History of a single student's reports.
     */
    public function show(Request $request, $studentId)
    {
        $user = auth()->user();
        $studentOfInterest = Student::with(['grade', 'classroom', 'section'])->findOrFail($studentId);

        $guard = $this->assertCanView($user, $studentOfInterest);
        if ($guard) {
            return $guard;
        }

        $reports = StudentMonthlyReport::where('student_id', $studentId)
            ->when($request->filled('month'), fn ($query) => $query->where('month', $request->integer('month')))
            ->when($request->filled('year'), fn ($query) => $query->where('year', $request->integer('year')))
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->limit(24)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Student report history retrieved successfully',
            'data' => [
                'student' => [
                    'id' => $studentOfInterest->id,
                    'name' => $this->service->nameOf($studentOfInterest, 'name'),
                    'grade_name' => $studentOfInterest->grade ? $this->service->nameOf($studentOfInterest->grade) : null,
                    'classroom_name' => $studentOfInterest->classroom ? $this->service->nameOf($studentOfInterest->classroom) : null,
                    'section_name' => $studentOfInterest->section ? $this->service->nameOf($studentOfInterest->section, 'Name_Section') : null,
                ],
                'reports' => $reports->map(fn ($report) => $this->service->present($report)),
            ],
        ], 200);
    }

    /**
     * A single monthly report detail by student + month + year.
     */
    public function detail(Request $request, $studentId, $month, $year)
    {
        $user = auth()->user();

        $report = StudentMonthlyReport::where('student_id', $studentId)
            ->where('month', (int) $month)
            ->where('year', (int) $year)
            ->first();

        if (! $report) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found for this month/year',
            ], 404);
        }

        $studentOfInterest = Student::with(['grade', 'classroom', 'section'])->find($report->student_id);
        $guard = $this->assertCanView($user, $studentOfInterest);
        if ($guard) {
            return $guard;
        }

        return response()->json([
            'success' => true,
            'message' => 'Student monthly report retrieved successfully',
            'data' => $this->service->present($report),
        ], 200);
    }

    /**
     * Parent: latest report for each of their children.
     */
    public function parent()
    {
        $user = auth()->user();

        if (! $user instanceof My_Parent) {
            return response()->json([
                'success' => false,
                'message' => 'Only parents can access this endpoint',
            ], 403);
        }

        $children = $user->students()->with(['grade', 'classroom', 'section'])->get();

        $childrenData = $children->map(function ($student) {
            $latest = StudentMonthlyReport::where('student_id', $student->id)
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            return [
                'id' => $student->id,
                'name' => $this->service->nameOf($student, 'name'),
                'grade_name' => $student->grade ? $this->service->nameOf($student->grade) : null,
                'classroom_name' => $student->classroom ? $this->service->nameOf($student->classroom) : null,
                'section_name' => $student->section ? $this->service->nameOf($student->section, 'Name_Section') : null,
                'latest_report' => $latest ? $this->service->present($latest) : null,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Parent reports retrieved successfully',
            'data' => $childrenData,
        ], 200);
    }

    /**
     * Admin: manually trigger report generation for one student or all.
     */
    public function generate(Request $request)
    {
        $schoolId = SchoolContext::schoolIdFrom($request->user());

        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
            // Scoped to the caller's school: queueing a report for another
            // school's student must fail here, not at approval time.
            'student_id' => [
                'nullable',
                'integer',
                Rule::exists('students', 'id')->when(
                    $schoolId !== null,
                    fn ($rule) => $rule->where('school_id', $schoolId)
                ),
            ],
        ]);

        $month = $request->integer('month');
        $year = $request->integer('year');
        $studentId = $request->input('student_id');

        try {
            // Sending a monthly report to students/parents is a sensitive
            // action: anybody who is not an approver queues the request.
            if (PendingRequestService::requiresApproval($request->user(), PendingActionRegistry::SEND_REPORT)) {
                $student = $studentId ? Student::find($studentId) : null;

                $pending = PendingRequestService::defer(PendingActionRegistry::SEND_REPORT, [
                    'entity'   => $student,
                    'entity_type' => $student ? Student::class : null,
                    'entity_id' => $studentId ? (int) $studentId : null,
                    'title'    => $student
                        ? 'Send monthly report of '.$student->name
                        : 'Send monthly reports of '.$month.'/'.$year,
                    'title_ar' => $student
                        ? 'إرسال التقرير الشهري للطالب '.$student->name
                        : 'إرسال التقارير الشهرية لـ '.$month.'/'.$year,
                    'description' => 'Generate and send the monthly report for '
                        .($student ? $student->name : 'every student')
                        .' ('.$month.'/'.$year.') to students and their parents.',
                    'description_ar' => 'إنشاء وإرسال التقرير الشهري لـ '
                        .($student ? $student->name : 'جميع الطلاب')
                        .' ('.$month.'/'.$year.') للطلاب وأولياء الأمور.',
                    'payload'  => ['month' => $month, 'year' => $year, 'student_id' => $studentId],
                    'reason'   => $request->input('reason'),
                    'priority' => $request->input('priority'),
                ], $request->user());

                return PendingRequestService::deferredResponse($pending);
            }

            if ($studentId) {
                $report = $this->service->generateForStudent((int) $studentId, $month, $year);

                return response()->json([
                    'success' => true,
                    'message' => $report
                        ? 'Report generated successfully'
                        : 'No data to generate a report from',
                    'data' => $report ? $this->service->present($report) : null,
                ], 200);
            }

            $result = $this->service->generateForAll(
                $month,
                $year,
                true,
                $schoolId
            );

            return response()->json([
                'success' => true,
                'message' => 'Monthly reports generated',
                'data' => $result,
            ], 200);
        } catch (\Exception $e) {
            return ApiResponse::fromException($e, 'Error generating reports');
        }
    }

    /**
     * Export matching reports as a CSV file (UTF-8 with BOM so Arabic
     * renders correctly in Excel). Honors the same filters as index().
     */
    public function export(Request $request)
    {
        $reports = StudentMonthlyReport::with(['student.grade', 'student.classroom', 'student.section'])
            ->when($request->filled('month'), fn ($query) => $query->where('month', $request->integer('month')))
            ->when($request->filled('year'), fn ($query) => $query->where('year', $request->integer('year')))
            ->when($request->filled('grade_id'), function ($query) use ($request) {
                $query->whereHas('student', fn ($studentQuery) => $studentQuery->where('Grade_id', $request->integer('grade_id')));
            })
            ->when($request->filled('classroom_id'), function ($query) use ($request) {
                $query->whereHas('student', fn ($studentQuery) => $studentQuery->where('Classroom_id', $request->integer('classroom_id')));
            })
            ->when($request->filled('student_id'), fn ($query) => $query->where('student_id', $request->integer('student_id')))
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $year = $request->integer('year') ?: 'all';
        $month = $request->integer('month') ?: 'all';
        $filename = "student-reports-{$year}-{$month}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache',
        ];

        return new StreamedResponse(function () use ($reports) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

            fputcsv($handle, [
                'الطالب',
                'المرحلة',
                'الفصل',
                'الشهر',
                'نسبة الحضور',
                'أيام الحضور/الدوام',
                'متوسط التقييمات',
                'الاختبارات الورقية',
                'الاختبارات الإلكترونية',
                'نسبة التقدم',
                'الاتجاه',
            ]);

            foreach ($reports as $report) {
                $present = $this->service->present($report);
                $student = $present['student'] ?? null;
                $attendance = $present['attendance'] ?? [];
                $evaluations = $present['evaluations'] ?? [];
                $exams = $present['exams'] ?? [];
                $quizzes = $present['quizzes'] ?? [];

                fputcsv($handle, [
                    $student['name'] ?? '—',
                    $student['grade_name'] ?? '',
                    $student['classroom_name'] ?? '',
                    ($present['month_name'] ?? '').' '.($present['year'] ?? ''),
                    $this->fmt((float) ($attendance['rate'] ?? 0)).'%',
                    ($attendance['present'] ?? 0).'/'.($attendance['total_days'] ?? 0),
                    $this->fmt((float) ($evaluations['average_level'] ?? 0)).'/4',
                    $this->fmt((float) ($exams['overall_percentage'] ?? 0)).'%',
                    $this->fmt((float) ($quizzes['average_percentage'] ?? 0)).'%',
                    $this->fmt((float) ($present['progress_score'] ?? 0)).'%',
                    $this->trendLabel($present['trend'] ?? ''),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Progress trend series for a single student across all months.
     */
    public function trends($studentId)
    {
        $user = auth()->user();
        $studentOfInterest = Student::findOrFail($studentId);

        $guard = $this->assertCanView($user, $studentOfInterest);
        if ($guard) {
            return $guard;
        }

        $reports = StudentMonthlyReport::where('student_id', $studentId)
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Student report trends retrieved successfully',
            'data' => [
                'student_id' => (int) $studentId,
                'points' => $reports->map(fn ($report) => [
                    'month' => $report->month,
                    'year' => $report->year,
                    'month_name' => $this->service->present($report)['month_name'],
                    'score' => (float) $report->progress_score,
                    'trend' => $report->trend,
                ])->values(),
            ],
        ], 200);
    }

    private function trendLabel(string $trend): string
    {
        return match ($trend) {
            'improved' => 'تحسّن',
            'declined' => 'تراجع',
            'stable' => 'مستقر',
            'new' => 'جديد',
            default => $trend,
        };
    }

    private function fmt(float $value): string
    {
        $clean = rtrim(rtrim(number_format($value, 2), '0'), '.');

        return $clean === '' ? '0' : $clean;
    }

    /**
     * Role-based access check. Returns an error JSON response when denied,
     * otherwise null (allowed).
     */
    private function assertCanView($user, ?Student $student)
    {
        if (! $user || ! $student) {
            return null;
        }

        $userClass = get_class($user);

        $isParent = strpos($userClass, 'My_Parent') !== false || strpos($userClass, 'Parent') !== false;
        $isStudent = strpos($userClass, 'Student') !== false;
        $isAdmin = ! $isParent && ! $isStudent;

        if ($isAdmin) {
            return null;
        }

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

        return null;
    }
}