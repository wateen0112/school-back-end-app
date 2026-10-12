<?php

namespace App\Http\Controllers\Api\WeeklyPrograms;

use App\Http\Controllers\Controller;
use App\Models\WeeklyProgram;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\My_Parent;
use App\Models\Notification;
use App\Support\CacheVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class WeeklyProgramController extends Controller
{
    private $relations = ['section.Grades', 'section.My_classs.grade', 'subject', 'teacher'];

    /**
     * Resolve the teacher for a subject in a specific section.
     * Uses the section_subject_teacher pivot first, then falls back to subject.teacher_id.
     */
    private function resolveTeacherForSection(int $subjectId, int $sectionId): ?int
    {
        $assignment = \App\Models\SectionSubjectTeacher::where('section_id', $sectionId)
            ->where('subject_id', $subjectId)
            ->first();

        if ($assignment && $assignment->teacher_id) {
            return (int) $assignment->teacher_id;
        }

        return Subject::find($subjectId)->teacher_id ?? null;
    }

    /**
     * تنسيق بيانات الحصة لإرسالها للواجهات.
     */
    private function formatProgram($program)
    {
        $section = $program->section;
        $classroom = $section ? $section->My_classs : null;
        $grade = $section ? $section->Grades : null;

        return [
            'id'           => (int) $program->id,
            'section_id'   => (int) $program->section_id,
            'day_of_week'  => (int) $program->day_of_week,
            'start_time'   => $program->start_time,
            'end_time'     => $program->end_time,
            'subject_id'   => (int) $program->subject_id,
            'teacher_id'   => $program->teacher_id ? (int) $program->teacher_id : null,
            'location'     => $program->location,
            'notes'        => $program->notes,
            'is_active'    => (bool) $program->is_active,
            'section'      => $section ? [
                'id'             => (int) $section->id,
                'name'           => $section->Name_Section,
                'class_id'       => $section->Class_id,
                'grade_id'       => $section->Grade_id,
                'classroom_name' => $classroom ? $classroom->Name_Class : null,
                'grade_name'     => $grade ? $grade->Name : null,
            ] : null,
            'subject'      => $program->subject ? [
                'id'   => (int) $program->subject->id,
                'name' => $program->subject->Name,
            ] : null,
            'teacher'      => $program->teacher ? [
                'id'   => (int) $program->teacher->id,
                'name' => $program->teacher->getTranslations('Name'),
            ] : null,
        ];
    }

    /**
     * عرض البرنامج الأسبوعي مع إمكانية التصفية.
     */
    public function index(Request $request)
    {
        try {
            $query = WeeklyProgram::with($this->relations)
                ->where('is_active', true)
                ->orderBy('day_of_week')
                ->orderBy('start_time');

            if ($request->filled('section_id')) {
                $query->where('section_id', $request->section_id);
            }
            if ($request->filled('grade_id')) {
                $sectionIds = Section::where('Grade_id', $request->grade_id)->pluck('id');
                $query->whereIn('section_id', $sectionIds);
            }
            if ($request->filled('classroom_id')) {
                $sectionIds = Section::where('Class_id', $request->classroom_id)->pluck('id');
                $query->whereIn('section_id', $sectionIds);
            }
            if ($request->filled('day_of_week')) {
                $query->where('day_of_week', $request->day_of_week);
            }
            if ($request->filled('teacher_id')) {
                $query->where('teacher_id', $request->teacher_id);
            }

            $programs = $query->get();

            return response()->json([
                'success' => true,
                'message' => 'Weekly programs retrieved successfully',
                'data'    => $programs->map(function ($program) {
                    return $this->formatProgram($program);
                }),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving weekly programs',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * عرض حصة واحدة.
     */
    public function show(Request $request, $id = null)
    {
        try {
            $id = $request->input('id', $id);
            $program = WeeklyProgram::with($this->relations)->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Weekly program retrieved successfully',
                'data'    => $this->formatProgram($program),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Weekly program not found',
                'error'   => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * إضافة حصة جديدة إلى البرنامج الأسبوعي.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'section_id'  => 'required|exists:sections,id',
                'day_of_week' => 'required|integer|between:0,6',
                'start_time'  => 'required|date_format:H:i',
                'end_time'    => 'required|date_format:H:i|after:start_time',
                'subject_id'  => 'required|exists:subjects,id',
                'teacher_id'  => 'nullable|exists:teachers,id',
                'scenario_id' => 'nullable|exists:timetable_scenarios,id',
                'location'    => 'nullable|string|max:255',
                'notes'       => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            if ($this->findSlotConflict(
                (int) $request->section_id,
                (int) $request->day_of_week,
                $request->start_time . ':00',
                $request->scenario_id
            )) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا الموعد محجوز لحصة أخرى في نفس القسم والسيناريو',
                    'error'   => 'weekly_programs_schedule_slot_unique',
                ], 409);
            }

            $dayLimit = $this->dayLimitExceeded(
                (int) $request->section_id,
                (int) $request->day_of_week,
                $request->scenario_id ? (int) $request->scenario_id : null
            );
            if ($dayLimit !== null) {
                return response()->json([
                    'success' => false,
                    'message' => "تم بلوغ الحد الأقصى للحصص في هذا اليوم ({$dayLimit})",
                    'error'   => 'weekly_programs_day_limit',
                ], 409);
            }

            $program = WeeklyProgram::create([
                'section_id'  => $request->section_id,
                'day_of_week' => (int) $request->day_of_week,
                'start_time'  => $request->start_time . ':00',
                'end_time'    => $request->end_time . ':00',
                'subject_id'  => $request->subject_id,
                'teacher_id'  => $request->teacher_id ?: $this->resolveTeacherForSection($request->subject_id, $request->section_id),
                'scenario_id' => $request->scenario_id,
                'location'    => $request->location,
                'notes'       => $request->notes,
                'is_active'   => true,
            ]);
            CacheVersion::bump('weekly_program');

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الحصة إلى البرنامج الأسبوعي بنجاح',
                'data'    => $this->formatProgram($program->load($this->relations)),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating weekly program',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

/**
     * Check whether a slot is already occupied by another program in the same
     * scenario scope (scenario_id, or the live schedule when NULL), mirroring
     * the weekly_programs_schedule_slot_unique index:
     * (COALESCE(scenario_id, 0), section_id, day_of_week, start_time).
     */
    private function findSlotConflict($sectionId, $dayOfWeek, $startTime, $scenarioId, $excludeId = null)
    {
        $query = WeeklyProgram::where('section_id', $sectionId)
            ->where('day_of_week', (int) $dayOfWeek)
            ->where('start_time', $startTime);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        if ($scenarioId) {
            $query->where('scenario_id', $scenarioId);
        } else {
            $query->whereNull('scenario_id');
        }

        return $query->exists();
    }

    /**
     * O1O�O O-O�Oc U?US OU,O"O�U+OU.O� OU,O�O3O"U^O1US.
     */
    public function update(Request $request, $id = null)
    {
        try {
            $id = $request->input('id', $id);
            $program = WeeklyProgram::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'section_id'  => 'sometimes|exists:sections,id',
                'day_of_week' => 'sometimes|integer|between:0,6',
                'start_time'  => 'sometimes|date_format:H:i',
                'end_time'    => 'sometimes|date_format:H:i',
                'subject_id'  => 'sometimes|exists:subjects,id',
                'teacher_id'  => 'nullable|exists:teachers,id',
                'location'    => 'nullable|string|max:255',
                'notes'       => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $fields = ['section_id', 'day_of_week', 'subject_id', 'teacher_id', 'location', 'notes'];
            foreach ($fields as $field) {
                if ($request->has($field)) {
                    $program->$field = $request->$field;
                }
            }

            if ($request->has('start_time')) {
                $program->start_time = $request->start_time . ':00';
            }
            if ($request->has('end_time')) {
                $program->end_time = $request->end_time . ':00';
            }

            if ($request->has('teacher_id') && ! $request->teacher_id) {
                $program->teacher_id = null;
            }

            if ($this->findSlotConflict(
                $program->section_id,
                (int) $program->day_of_week,
                $program->start_time,
                $program->scenario_id,
                $program->id
            )) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا الموعد محجوز لحصة أخرى في نفس القسم والسيناريو',
                    'error'   => 'weekly_programs_schedule_slot_unique',
                ], 409);
            }

            $dayLimit = $this->dayLimitExceeded(
                (int) $program->section_id,
                (int) $program->day_of_week,
                $program->scenario_id ? (int) $program->scenario_id : null,
                $program->id
            );
            if ($dayLimit !== null) {
                return response()->json([
                    'success' => false,
                    'message' => "تم بلوغ الحد الأقصى للحصص في هذا اليوم ({$dayLimit})",
                    'error'   => 'weekly_programs_day_limit',
                ], 409);
            }

            $program->save();
            CacheVersion::bump('weekly_program');

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الحصة في البرنامج الأسبوعي بنجاح',
                'data'    => $this->formatProgram($program->fresh()->load($this->relations)),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating weekly program',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * حذف حصة من البرنامج الأسبوعي.
     */
    /**
     * Maximum lessons allowed per day for a section/scenario, derived from
     * the grade's weekly requirements spread over the school's working days
     * (same rule the generator uses). Returns null when under the limit,
     * otherwise the limit that the new lesson would exceed.
     */
    private function dayLimitExceeded(int $sectionId, int $dayOfWeek, ?int $scenarioId, ?int $excludeId = null)
    {
        $section = \App\Models\Section::find($sectionId);
        if (!$section) return null;

        $requirements = \App\Models\SubjectWeeklyRequirement::query()
            ->where('grade_id', $section->Grade_id)
            ->where(function ($q) use ($section) {
                $q->where('classroom_id', $section->Class_id)->orWhereNull('classroom_id');
            })
            ->get();

        if ($requirements->isEmpty()) return null;

        $settings = \App\Models\SchoolScheduleSetting::first();
        $days = ($settings && is_array($settings->days) && count($settings->days) > 0)
            ? count($settings->days)
            : 5;
        $days = max(1, $days);

        $maxPerDay = (int) ceil($requirements->sum('periods_per_week') / $days) + 2;

        $query = WeeklyProgram::where('section_id', $sectionId)
            ->where('day_of_week', $dayOfWeek);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        if ($scenarioId) {
            $query->where('scenario_id', $scenarioId);
        } else {
            $query->whereNull('scenario_id');
        }

        if ($query->count() + 1 > $maxPerDay) {
            return $maxPerDay;
        }

        return null;
    }

    public function destroy(Request $request, $id = null)
    {
        try {
            $id = $request->input('id', $id);
            $program = WeeklyProgram::findOrFail($id);
            $program->delete();
            CacheVersion::bump('weekly_program');

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الحصة من البرنامج الأسبوعي بنجاح',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting weekly program',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function bulkDestroy(Request $request)
    {
        try {
            $validated = $request->validate([
                'ids'   => 'required|array|min:1',
                'ids.*' => 'integer|exists:weekly_programs,id',
            ]);

            $count = WeeklyProgram::whereIn('id', $validated['ids'])->delete();
            CacheVersion::bump('weekly_program');

            return response()->json([
                'success' => true,
                'message' => "تم حذف {$count} حصة من البرنامج الأسبوعي بنجاح",
                'deleted' => $count,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for bulk delete',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting weekly programs',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * حصص اليوم لكل قسم: الحصة الحالية والحصص القادمة، متزامنة مع الوقت الحالي.
     */
    public function today(Request $request)
    {
        try {
            $nowWeekDay = (int) date('w'); // 0 = Sunday
            $nowTime    = date('H:i:s');

            // The server clock may run in UTC while the school day runs in local
            // time — trust the browser's wall-clock (validated) when provided.
            if (is_string($request->input('client_date')) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $request->input('client_date'))) {
                $clientDate = \DateTime::createFromFormat('Y-m-d', $request->input('client_date'));
                if ($clientDate !== false) {
                    $nowWeekDay = (int) $clientDate->format('w');
                }
            }
            if (is_string($request->input('client_time')) && preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $request->input('client_time'))) {
                $nowTime = strlen($request->input('client_time')) === 5
                    ? $request->input('client_time') . ':00'
                    : $request->input('client_time');
            }

            $programs = WeeklyProgram::with($this->relations)
                ->where('is_active', true)
                ->where('day_of_week', $nowWeekDay)
                ->orderBy('start_time')
                ->get();

            $sections = $programs->groupBy('section_id');

            $result = $sections->map(function ($programs, $sectionId) use ($nowTime) {
                $first = $programs->first();
                $section = $first->section;

                $current = null;
                $upcoming = [];
                $finished = [];

                foreach ($programs as $program) {
                    if ($program->start_time <= $nowTime && $nowTime < $program->end_time) {
                        $current = $program;
                    } elseif ($program->start_time > $nowTime) {
                        $upcoming[] = $program;
                    } else {
                        $finished[] = $program;
                    }
                }

                return [
                    'section_id'      => (int) $sectionId,
                    'section_name'    => $section ? $section->Name_Section : null,
                    'classroom_name'  => $section && $section->My_classs ? $section->My_classs->Name_Class : null,
                    'grade_name'      => $section && $section->Grades ? $section->Grades->Name : null,
                    'current'         => $current ? $this->formatProgram($current) : null,
                    'upcoming'        => array_map(function ($p) {
                        return $this->formatProgram($p);
                    }, array_slice($upcoming, 0, 4)),
                    'finished'        => array_map(function ($p) {
                        return $this->formatProgram($p);
                    }, $finished),
                ];
            })->values();

            return response()->json([
                'success' => true,
                'message' => 'Today schedule retrieved successfully',
                'data'    => [
                    'date'         => date('Y-m-d'),
                    'day_of_week'  => $nowWeekDay,
                    'server_time'  => $nowTime,
                    'sections'     => $result,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving today schedule',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * حصصي (لتطبيق الموبايل): معلم / طالب / ولي أمر.
     */
    public function mySchedule()
    {
        try {
            $user = auth()->user();
            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            $class = get_class($user);
            $role = 'admin';
            if (strpos($class, 'Teacher') !== false) {
                $role = 'teacher';
            } elseif (strpos($class, 'Student') !== false) {
                $role = 'student';
            } elseif (strpos($class, 'My_Parent') !== false || strpos($class, 'Parent') !== false) {
                $role = 'parent';
            }

            $data = Cache::remember(
                'schedule:my:v1:'.CacheVersion::stamp('weekly_program').':'.$role.':'.$user->id,
                300,
                function () use ($user, $role) {
                    $sectionIds = collect();

                    if ($role === 'teacher') {
                        $sectionIds = WeeklyProgram::where('teacher_id', $user->id)
                            ->where('is_active', true)
                            ->distinct()
                            ->pluck('section_id');
                    } elseif ($role === 'student') {
                        if ($user->section_id) {
                            $sectionIds = collect([(int) $user->section_id]);
                        }
                    } elseif ($role === 'parent') {
                        $sectionIds = $user->students()->pluck('section_id')->filter();
                    }

                    $sections = [];
                    if ($sectionIds->isNotEmpty()) {
                        $programs = WeeklyProgram::with($this->relations)
                            ->where('is_active', true)
                            ->whereIn('section_id', $sectionIds)
                            ->orderBy('day_of_week')
                            ->orderBy('start_time')
                            ->get()
                            ->groupBy('section_id');

                        foreach ($programs as $sectionId => $sectionPrograms) {
                            $first = $sectionPrograms->first();
                            $section = $first ? $first->section : null;
                            $sections[] = [
                                'section_id'     => (int) $sectionId,
                                'section_name'   => $section ? $section->Name_Section : null,
                                'classroom_name' => $section && $section->My_classs ? $section->My_classs->Name_Class : null,
                                'grade_name'     => $section && $section->Grades ? $section->Grades->Name : null,
                                'programs'       => $sectionPrograms->map(function ($p) {
                                    return $this->formatProgram($p);
                                })->values(),
                            ];
                        }
                    }

                    return [
                        'user_role' => $role,
                        'sections'  => $sections,
                    ];
                }
            );

            return response()->json([
                'success' => true,
                'message' => 'My schedule retrieved successfully',
                'data'    => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving my schedule',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * بث تحديث البرنامج الأسبوعي إلى المعلمين / الطلاب / أولياء الأمور (موبايل + فايربيز).
     */
    public function notify(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'section_ids' => 'required|array|min:1',
                'section_ids.*' => 'integer|exists:sections,id',
                'message'     => 'nullable|string|max:1000',
                'roles'       => 'sometimes|array',
                'roles.*'     => 'in:teacher,student,parent',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $sectionIds = array_map('intval', $request->section_ids);
            $roles = $request->filled('roles') && is_array($request->roles)
                ? $request->roles
                : ['teacher', 'student', 'parent'];

            $sections = Section::with('teachers')->whereIn('id', $sectionIds)->get();
            $sectionNames = $sections->pluck('Name_Section')->map(function ($name) {
                return is_array($name) ? ($name['ar'] ?? $name['en'] ?? '') : $name;
            })->implode('، ');

            $title = 'تحديث البرنامج الأسبوعي';
            $body = $request->filled('message')
                ? $request->message
                : 'تم تحديث البرنامج الأسبوعي' . ($sectionNames ? ' للقسم: ' . $sectionNames : '') . '، يرجى الاطلاع على الجدول الجديد في التطبيق.';

            $targets = [];

            if (in_array('teacher', $roles, true)) {
                $teacherIds = $sections->flatMap->teachers->pluck('id')->unique();
                foreach ($teacherIds as $id) {
                    $targets[] = ['id' => (int) $id, 'type' => 'App\Models\Teacher'];
                }
            }

            if (in_array('student', $roles, true)) {
                $studentIds = Student::whereIn('section_id', $sectionIds)->pluck('id');
                foreach ($studentIds as $id) {
                    $targets[] = ['id' => (int) $id, 'type' => 'App\Models\Student'];
                }
            }

            if (in_array('parent', $roles, true)) {
                $parentIds = Student::whereIn('section_id', $sectionIds)
                    ->whereNotNull('parent_id')
                    ->pluck('parent_id')
                    ->unique();
                foreach ($parentIds as $id) {
                    $targets[] = ['id' => (int) $id, 'type' => 'App\Models\My_Parent'];
                }
            }

            if (empty($targets)) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يوجد مستلمون لهذه الأقسام',
                ], 409);
            }

            DB::beginTransaction();

            $notification = Notification::create([
                'title'       => $title,
                'body'        => $body,
                'type'        => 'targeted',
                'sender_id'   => auth()->id(),
                'sender_type' => 'App\User',
                'target_role' => null,
                'data'        => [
                    'action'     => 'schedule_update',
                    'action_id'  => $sectionIds[0],
                    'section_ids' => $sectionIds,
                    'targets'    => $targets,
                ],
            ]);

            $rows = [];
            $now = now();
            foreach ($targets as $target) {
                $rows[] = [
                    'notification_id' => $notification->id,
                    'recipient_id'    => $target['id'],
                    'recipient_type'  => $target['type'],
                    'is_read'         => false,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }
            foreach (array_chunk($rows, 500) as $chunk) {
                NotificationRecipient::insert($chunk);
            }

            DB::commit();

            // إرسال إشعار فايربيز (أفضل جهد)
            try {
                $fcm = new \App\Services\FcmNotificationService();
                $fcm->sendToUsers($targets, $title, $body, [
                    'notification_id' => $notification->id,
                    'type'            => $notification->type,
                    'action'          => 'schedule_update',
                    'action_id'       => $sectionIds[0],
                ]);
            } catch (\Exception $e) {
                \Log::warning('FCM schedule update failed: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال تحديث البرنامج الأسبوعي بنجاح',
                'data'    => [
                    'notification_id'   => $notification->id,
                    'recipients_count'  => count($rows),
                    'sections_count'    => count($sectionIds),
                    'roles'             => $roles,
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error sending weekly program update',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
