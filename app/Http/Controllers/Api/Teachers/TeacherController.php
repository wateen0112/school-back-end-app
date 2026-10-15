<?php

namespace App\Http\Controllers\Api\Teachers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeachers;
use App\Models\Teacher;
use App\Models\WeeklyProgram;
use App\Services\Pending\PendingActionRegistry;
use App\Services\Pending\PendingRequestService;
use App\Services\Teachers\TeacherProfileService;
use App\Services\Teachers\TeacherSalaryPlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class TeacherController extends Controller
{
    protected TeacherProfileService $profiles;

    public function __construct()
    {
        $this->profiles = new TeacherProfileService();
    }

    public function index(Request $request)
    {
        try {
            $query = Teacher::with(['specializations', 'genders']);
            $user = $request->user() ?? Auth::guard('api')->user();
            if ($user instanceof \App\User && $user->school_id) {
                // Include legacy teachers created before school scoping (school_id IS NULL)
                $query->where(function ($q) use ($user) {
                    $q->where('school_id', $user->school_id)->orWhereNull('school_id');
                });
            }
            $teachers = $query->orderBy('Name')->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Teachers retrieved successfully',
                'data' => $teachers
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving teachers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(StoreTeachers $request)
    {
        try {
            $teacher = new Teacher();
            $teacher->Name = ['en' => $request->Name_en ?: $request->Name_ar, 'ar' => $request->Name_ar];
            $teacher->email = $request->Email;
            $teacher->password = Hash::make($request->Password);
            $teacher->Specialization_id = $request->Specialization_id;
            $teacher->Gender_id = $request->Gender_id;
            $teacher->Joining_Date = $request->Joining_Date;
            $teacher->Address = $request->Address;
            $schoolId = ($request->user() instanceof \App\User) ? $request->user()->school_id : ($request->filled('school_id') ? $request->school_id : null);
            $teacher->school_id = $schoolId;
            $teacher->save();

            // Salary contract (type / hour rate / custom installments).
            (new TeacherSalaryPlanService())->apply($teacher, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Teacher created successfully',
                'data' => $teacher->load(['specializations', 'genders', 'customInstallments'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $teacher = Teacher::with([
                'specializations',
                'genders',
                'customInstallments',
                'sectionSubjects.subject',
                'sectionSubjects.section.Grades',
                'sectionSubjects.section.My_classs',
            ])->findOrFail($id);

            $lessonCounts = WeeklyProgram::query()
                ->where('teacher_id', $teacher->id)
                ->whereNull('scenario_id')
                ->where('is_active', true)
                ->selectRaw('section_id, subject_id, count(*) as lessons')
                ->groupBy('section_id', 'subject_id')
                ->get()
                ->mapWithKeys(fn ($row) => [$row->section_id.'|'.$row->subject_id => (int) $row->lessons]);

            // Assignments can survive a reseed with their section or subject gone, so
            // only surface the ones that still resolve.
            $assignments = $teacher->sectionSubjects
                ->filter(fn ($assignment) => $assignment->section && $assignment->subject)
                ->map(function ($assignment) use ($lessonCounts) {
                    $section = $assignment->section;

                    return [
                        'subject_id'     => $assignment->subject_id,
                        'subject_name'   => $assignment->subject->getTranslations('Name'),
                        'section_id'     => $section->id,
                        'section_name'   => $section->getTranslations('Name_Section'),
                        'classroom_name' => $section->My_classs?->getTranslations('Name_Class'),
                        'grade_name'     => $section->Grades?->getTranslations('Name'),
                        'weekly_lessons' => $lessonCounts->get($section->id.'|'.$assignment->subject_id, 0),
                    ];
                })
                ->sortBy([
                    fn ($a, $b) => $this->arabicLabel($a['subject_name']) <=> $this->arabicLabel($b['subject_name']),
                    fn ($a, $b) => $this->arabicLabel($a['section_name']) <=> $this->arabicLabel($b['section_name']),
                ])
                ->values();

            return response()->json([
                'success' => true,
                'message' => 'Teacher retrieved successfully',
                'data'    => array_merge($teacher->toArray(), [
                    'teaching' => [
                        'subjects_count' => $assignments->pluck('subject_id')->unique()->count(),
                        'sections_count' => $assignments->pluck('section_id')->unique()->count(),
                        'weekly_lessons' => $assignments->sum('weekly_lessons'),
                        'assignments'    => $assignments,
                    ],
                ]),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Arabic label of a translatable name array, used only for ordering.
     */
    private function arabicLabel($translations): string
    {
        if (is_array($translations)) {
            return (string) ($translations['ar'] ?? $translations['en'] ?? '');
        }

        return (string) $translations;
    }

    public function update(StoreTeachers $request, $id = null)
    {
        try {
            $teacher = Teacher::findOrFail($request->input('id', $id));

            // Editing teacher master data is a sensitive action: anybody who is
            // not an approver queues the request instead of writing.
            if (PendingRequestService::requiresApproval($request->user(), PendingActionRegistry::UPDATE_TEACHER)) {
                $pending = PendingRequestService::defer(PendingActionRegistry::UPDATE_TEACHER, [
                    'entity'   => $teacher,
                    'title'    => 'Edit teacher data: '.($teacher->Name['en'] ?? $teacher->Name['ar'] ?? ('#'.$teacher->id)),
                    'title_ar' => 'تعديل بيانات معلم: '.($teacher->Name['ar'] ?? $teacher->Name['en'] ?? ('#'.$teacher->id)),
                    'description' => 'Update teacher master data (email, specialisation, joining date, address, salary).',
                    'description_ar' => 'تحديث بيانات المعلم (البريد الإلكتروني، التخصص، تاريخ الالتحاق، العنوان، الراتب).',
                    'payload'  => $request->safe()->except(['Password']),
                    'reason'   => $request->input('reason'),
                    'priority' => $request->input('priority'),
                ], $request->user());

                return PendingRequestService::deferredResponse($pending);
            }

            $teacher = $this->profiles->apply($teacher, $request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Teacher updated successfully',
                'data' => $teacher
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating teacher',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id = null)
    {
        try {
            $teacher = Teacher::findOrFail($request->input('id', $id));
            $teacher->delete();

            return response()->json([
                'success' => true,
                'message' => 'Teacher deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting teacher',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
