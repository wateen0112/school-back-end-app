<?php

namespace App\Http\Controllers\Api\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\My_Parent;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class ParentDashboardController extends Controller
{
    public function show(Request $request)
    {
        try {
            $user = auth()->user();
            $parent = null;

            if ($user instanceof My_Parent) {
                $parent = $user;
            } elseif (strpos(get_class($user), 'My_Parent') !== false || strpos(get_class($user), 'Parent') !== false) {
                $parent = $user;
            } elseif ($request->filled('parent_id')) {
                $parent = My_Parent::find($request->parent_id);
            }

            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parent not found or unauthorized',
                ], 403);
            }

            $service = new DashboardService();

            $children = $parent->students()->with(['grade', 'classroom', 'section'])->get();

            if ($children->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Parent dashboard retrieved successfully',
                    'data'    => [
                        'parent'     => ['id' => (int) $parent->id, 'name' => $service->nameOf($parent, 'Name_Father')],
                        'children'   => [],
                        'summary'    => ['children_count' => 0, 'total_remaining' => 0, 'total_paid' => 0],
                        'notifications' => $service->notifications($parent->id, My_Parent::class),
                    ],
                ], 200);
            }

            $totalRemaining = 0;
            $totalPaid = 0;

            $childrenData = $children->map(function ($student) use ($service) {
                $sectionId = $student->section_id ? (int) $student->section_id : null;
                $finance = $service->financialSummary($student->id);
                $attendance = $service->attendanceSummary($student->id);

                return [
                    'id'                => (int) $student->id,
                    'name'              => $service->nameOf($student, 'name'),
                    'grade_name'        => $student->grade ? $service->nameOf($student->grade) : null,
                    'classroom_name'    => $student->classroom ? $service->nameOf($student->classroom) : null,
                    'section_name'      => $student->section ? $service->nameOf($student->section, 'Name_Section') : null,
                    'today_attendance'  => $attendance['today_status'],
                    'attendance'        => $attendance,
                    'grades'            => $service->paperExamGrades($student->id),
                    'upcoming_exams'    => $service->upcomingExams((int) $sectionId, 2),
                    'finance'           => $finance,
                ];
            })->values();

            foreach ($childrenData as $child) {
                $totalRemaining += $child['finance']['remaining'];
                $totalPaid += $child['finance']['paid'];
            }

            $data = [
                'parent' => [
                    'id'   => (int) $parent->id,
                    'name' => $service->nameOf($parent, 'Name_Father'),
                ],
                'children'   => $childrenData,
                'summary'    => [
                    'children_count' => $children->count(),
                    'total_remaining'=> round($totalRemaining, 2),
                    'total_paid'     => round($totalPaid, 2),
                ],
                'notifications' => $service->notifications($parent->id, My_Parent::class),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Parent dashboard retrieved successfully',
                'data'    => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving parent dashboard',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}