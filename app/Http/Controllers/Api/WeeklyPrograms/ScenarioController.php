<?php

namespace App\Http\Controllers\Api\WeeklyPrograms;

use App\Http\Controllers\Controller;
use App\Models\WeeklyProgram;
use App\Models\TimetableScenario;
use App\Models\SubjectWeeklyRequirement;
use App\Models\Section;
use App\Models\Subject;
use App\Models\AcademicYear;
use App\Models\SchedulePeriod;
use App\Services\TimetableOptimizationService;
use App\Services\TimetableValidationService;
use App\Services\SubjectYearlyPlanService;
use App\Support\CacheVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ScenarioController extends Controller
{
    /**
     * List all scenarios with optional filters.
     */
    public function index(Request $request)
    {
        try {
            $query = TimetableScenario::with(['grade', 'classroom', 'section']);

            if ($request->filled('grade_id')) {
                $query->where('grade_id', $request->grade_id);
            }
            if ($request->filled('classroom_id')) {
                $query->where('classroom_id', $request->classroom_id);
            }
            if ($request->filled('section_id')) {
                $query->where('section_id', $request->section_id);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $scenarios = $query->orderByDesc('version')->orderByDesc('created_at')->get();

            $scenarios->each(function ($scenario) {
                $scenario->programs_count = $scenario->programs()->count();
            });

            return response()->json([
                'success' => true,
                'message' => 'Scenarios retrieved successfully',
                'data'    => $scenarios,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving scenarios',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resolve per-day lesson slots from the active academic year's schedule
     * periods so generated timetables align with the configured periods.
     * Returns [days, day_slots]; both empty when no periods are configured
     * (the optimizer then falls back to school-settings slots).
     */
    private function resolvePeriodPlan(): array
    {
        try {
            $year = AcademicYear::query()
                ->where('status', AcademicYear::STATUS_ACTIVE)
                ->orderByDesc('is_current')
                ->orderByDesc('id')
                ->first();

            if (!$year || !SchedulePeriod::where('academic_year_id', $year->id)->exists()) {
                return [[], []];
            }

            $days = is_array($year->working_days) && count($year->working_days)
                ? array_values(array_map('intval', $year->working_days))
                : [0, 1, 2, 3, 4];

            $daySlots = [];
            foreach ($days as $day) {
                $slots = [];
                foreach (SchedulePeriod::forDay($year->id, $day) as $period) {
                    if (($period->type ?? null) !== SchedulePeriod::TYPE_LESSON) {
                        continue;
                    }
                    $slots[substr($period->start_time, 0, 5)] = substr($period->end_time, 0, 5);
                }
                if (!empty($slots)) {
                    $daySlots[$day] = $slots;
                }
            }

            return empty($daySlots) ? [[], []] : [array_keys($daySlots), $daySlots];
        } catch (\Throwable $e) {
            return [[], []];
        }
    }

    /**
     * Show a single scenario with its programs.
     */
    public function show($id)
    {
        try {
            $scenario = TimetableScenario::with(['grade', 'classroom', 'section', 'programs.subject', 'programs.teacher'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Scenario retrieved successfully',
                'data'    => $scenario,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Scenario not found',
                'error'   => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Create a new scenario (version).
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name'         => 'required|string|max:255',
                'description'  => 'nullable|string|max:1000',
                'grade_id'     => 'nullable|exists:grades,id',
                'classroom_id' => 'nullable|exists:classrooms,id',
                'section_id'   => 'nullable|exists:sections,id',
                'locked_slots' => 'sometimes|array',
                'strict_availability' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $maxVersion = TimetableScenario::where('grade_id', $request->grade_id)
                ->max('version') ?? 0;

            $scenario = TimetableScenario::create([
                'name'         => $request->name,
                'description'  => $request->description,
                'grade_id'     => $request->grade_id,
                'classroom_id' => $request->classroom_id,
                'section_id'   => $request->section_id,
                'version'      => $maxVersion + 1,
                'status'       => 'draft',
                'is_active'    => false,
                'locked_slots' => $request->input('locked_slots', []),
            ]);

            // Auto-generate the timetable when a section is chosen, so the
            // scenario is created with its lessons instead of empty.
            $generated = ['programs' => [], 'warnings' => [], 'stats' => []];
            if ($scenario->section_id) {
                $optimizer = new TimetableOptimizationService();
                [$days, $daySlots] = $this->resolvePeriodPlan();
                $generated = $optimizer->generate((int) $scenario->section_id, [
                    'scenario_id'     => $scenario->id,
                    'preserve_locked' => false,
                    'strict_availability' => $request->boolean('strict_availability', false),
                    'days'            => $days,
                    'day_slots'       => $daySlots,
                ]);

                DB::beginTransaction();
                foreach ($generated['programs'] as $programData) {
                    WeeklyProgram::create($programData);
                }
                DB::commit();
            }

            // Flag subject requirements whose weekly periods deviate from the
            // weekly average implied by their total yearly lessons.
            $planService = new SubjectYearlyPlanService();
            $yearlyPlanWarnings = $planService->mismatchesForGrade(
                $request->grade_id,
                $request->classroom_id
            );

            $warnings = array_merge($yearlyPlanWarnings, $generated['warnings']);
            if (!$scenario->section_id) {
                $warnings[] = 'لم يتم اختيار قسم — تم إنشاء السيناريو فارغاً. اختر القسم ثم استخدم "إعادة التوليد" لتوليد الجدول.';
            }

            return response()->json([
                'success'  => true,
                'message'  => 'تم إنشاء السيناريو بنجاح',
                'data'     => $scenario->fresh()->loadCount('programs'),
                'warnings' => $warnings,
                'stats'    => $generated['stats'],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating scenario',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a scenario.
     */
    public function update(Request $request, $id)
    {
        try {
            $scenario = TimetableScenario::findOrFail($id);

            if ($scenario->status === 'published') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تعديل سيناريو منشور',
                ], 409);
            }

            $validator = Validator::make($request->all(), [
                'name'        => 'sometimes|string|max:255',
                'description' => 'nullable|string|max:1000',
                'locked_slots' => 'sometimes|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $data = $request->only(['name', 'description']);
            if ($request->has('locked_slots')) {
                $data['locked_slots'] = $request->input('locked_slots');
            }
            $scenario->update($data);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث السيناريو بنجاح',
                'data'    => $scenario->fresh(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating scenario',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a scenario (only drafts).
     */
    public function destroy($id)
    {
        try {
            $scenario = TimetableScenario::findOrFail($id);

            if ($scenario->status === 'published') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف سيناريو منشور',
                ], 409);
            }

            DB::beginTransaction();

            WeeklyProgram::where('scenario_id', $id)->delete();
            $scenario->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف السيناريو بنجاح',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error deleting scenario',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Auto-generate an optimized timetable for a section.
     */
    public function optimize(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'section_id'   => 'required|exists:sections,id',
                'scenario_id'  => 'nullable|exists:timetable_scenarios,id',
                'clear_existing' => 'sometimes|boolean',
                'strict_availability' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $sectionId = $request->section_id;
            $scenarioId = $request->scenario_id;
            $clearExisting = $request->boolean('clear_existing', false);

            // Create a scenario if not provided
            if (!$scenarioId) {
                $section = Section::find($sectionId);
                $gradeId = $section->Grade_id ?? null;
                $classroomId = $section->Class_id ?? null;

                $maxVersion = TimetableScenario::where('grade_id', $gradeId)->max('version') ?? 0;

                $scenario = TimetableScenario::create([
                    'name'         => "تلقائي - " . ($section->Name_Section ?? 'قسم ' . $sectionId),
                    'description'  => 'تم الإنشاء تلقائياً بواسطة محرك التحسين',
                    'grade_id'     => $gradeId,
                    'classroom_id' => $classroomId,
                    'section_id'   => $sectionId,
                    'version'      => $maxVersion + 1,
                    'status'       => 'draft',
                    'is_active'    => false,
                ]);
                $scenarioId = $scenario->id;
            }

            // Clear existing programs for this scenario if requested
            if ($clearExisting) {
                WeeklyProgram::where('scenario_id', $scenarioId)
                    ->where('section_id', $sectionId)
                    ->delete();
            }

            $optimizer = new TimetableOptimizationService();
            [$days, $daySlots] = $this->resolvePeriodPlan();
            $result = $optimizer->generate($sectionId, [
                'scenario_id' => $scenarioId,
                'preserve_locked' => !$clearExisting,
                'strict_availability' => $request->boolean('strict_availability', false),
                'days' => $days,
                'day_slots' => $daySlots,
            ]);

            // Save the generated programs
            DB::beginTransaction();
            foreach ($result['programs'] as $programData) {
                WeeklyProgram::create($programData);
            }
            DB::commit();

            $scenario = TimetableScenario::withCount('programs')->find($scenarioId);

            return response()->json([
                'success' => true,
                'message' => 'تم تحسين الجدول بنجاح',
                'data'    => [
                    'scenario'  => $scenario,
                    'programs'  => $result['programs'],
                    'warnings'  => $result['warnings'],
                    'stats'     => $result['stats'],
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error optimizing timetable',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate a timetable for a section.
     */
    public function validateTimetable(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'section_id'  => 'required|exists:sections,id',
                'scenario_id' => 'nullable|exists:timetable_scenarios,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $validationService = new TimetableValidationService();
            $result = $validationService->validate(
                $request->section_id,
                $request->scenario_id
            );

            return response()->json([
                'success' => true,
                'message' => $result['valid'] ? 'الجدول ساري بدون أخطاء' : 'تم العثور على أخطاء في الجدول',
                'data'    => $result,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error validating timetable',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Publish a scenario (make it active).
     */
    public function publish($id)
    {
        try {
            $scenario = TimetableScenario::findOrFail($id);

            if ($scenario->status === 'published') {
                return response()->json([
                    'success' => false,
                    'message' => 'السيناريو منشور بالفعل',
                ], 409);
            }

            DB::beginTransaction();

            // Unpublish other scenarios for the same section
            if ($scenario->section_id) {
                TimetableScenario::where('section_id', $scenario->section_id)
                    ->where('status', 'published')
                    ->update(['status' => 'archived', 'is_active' => false]);

                // The live timetable is "all programs with is_active = true".
                // Deactivate everything for this section except the scenario
                // being published: the old live (non-scenario) lessons and the
                // lessons of every other scenario (drafts are created active).
                WeeklyProgram::where('section_id', $scenario->section_id)
                    ->where(function ($q) use ($scenario) {
                        $q->whereNull('scenario_id')
                            ->orWhere('scenario_id', '!=', $scenario->id);
                    })
                    ->update(['is_active' => false]);
            }

            $scenario->update(['status' => 'published', 'is_active' => true]);

            // Activate all programs in this scenario
            WeeklyProgram::where('scenario_id', $scenario->id)
                ->update(['is_active' => true]);

            DB::commit();
            CacheVersion::bump('weekly_program');

            return response()->json([
                'success' => true,
                'message' => 'تم نشر السيناريو بنجاح',
                'data'    => $scenario->fresh(),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error publishing scenario',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unpublish a scenario.
     */
    public function unpublish($id)
    {
        try {
            $scenario = TimetableScenario::findOrFail($id);

            if ($scenario->status !== 'published') {
                return response()->json([
                    'success' => false,
                    'message' => 'السيناريو غير منشور',
                ], 409);
            }

            DB::beginTransaction();

            $scenario->update(['status' => 'draft', 'is_active' => false]);

            WeeklyProgram::where('scenario_id', $scenario->id)
                ->update(['is_active' => false]);

            DB::commit();
            CacheVersion::bump('weekly_program');

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء نشر السيناريو بنجاح',
                'data'    => $scenario->fresh(),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error unpublishing scenario',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Compare multiple scenarios.
     */
    public function compare(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'scenario_ids' => 'required|array|min:2|max:5',
                'scenario_ids.*' => 'integer|exists:timetable_scenarios,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $scenarios = TimetableScenario::with(['grade', 'section', 'programs.subject', 'programs.teacher'])
                ->whereIn('id', $request->scenario_ids)
                ->get();

            $comparison = $scenarios->map(function ($scenario) {
                $programs = $scenario->programs;
                return [
                    'id'              => $scenario->id,
                    'name'            => $scenario->name,
                    'version'         => $scenario->version,
                    'status'          => $scenario->status,
                    'programs_count'  => $programs->count(),
                    'days_used'       => $programs->pluck('day_of_week')->unique()->count(),
                    'subjects_covered' => $programs->pluck('subject_id')->unique()->count(),
                    'avg_student_load' => $this->calculateAvgStudentLoad($programs),
                    'teacher_distribution' => $this->calculateTeacherDistribution($programs),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Comparison data retrieved',
                'data'    => $comparison,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error comparing scenarios',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Duplicate a scenario.
     */
    public function duplicate($id)
    {
        try {
            $source = TimetableScenario::findOrFail($id);
            $programs = WeeklyProgram::where('scenario_id', $id)->get();

            $maxVersion = TimetableScenario::where('grade_id', $source->grade_id)->max('version') ?? 0;

            DB::beginTransaction();

            $newScenario = TimetableScenario::create([
                'name'         => $source->name . ' (نسخة)',
                'description'  => $source->description,
                'grade_id'     => $source->grade_id,
                'classroom_id' => $source->classroom_id,
                'section_id'   => $source->section_id,
                'version'      => $maxVersion + 1,
                'status'       => 'draft',
                'is_active'    => false,
            ]);

            foreach ($programs as $p) {
                $data = $p->toArray();
                unset($data['id'], $data['created_at'], $data['updated_at']);
                $data['scenario_id'] = $newScenario->id;
                WeeklyProgram::create($data);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم نسخ السيناريو بنجاح',
                'data'    => $newScenario->loadCount('programs'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error duplicating scenario',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * CRUD for subject weekly requirements.
     */
    public function requirements(Request $request)
    {
        try {
            $query = SubjectWeeklyRequirement::with(['subject', 'grade']);

            if ($request->filled('grade_id')) {
                $query->where('grade_id', $request->grade_id);
            }
            if ($request->filled('classroom_id')) {
                $query->where('classroom_id', $request->classroom_id);
            }
            if ($request->filled('subject_id')) {
                $query->where('subject_id', $request->subject_id);
            }

            $requirements = $query->get();

            return response()->json([
                'success' => true,
                'message' => 'Requirements retrieved successfully',
                'data'    => $requirements,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving requirements',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function storeRequirement(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'subject_id'          => 'required|exists:subjects,id',
                'grade_id'            => 'required|exists:grades,id',
                'classroom_id'        => 'nullable|exists:classrooms,id',
                'periods_per_week'    => 'required|integer|min:1|max:20',
                'total_periods_per_year' => 'nullable|integer|min:1',
                'min_periods_per_day' => 'sometimes|integer|min:0',
                'max_periods_per_day' => 'sometimes|integer|min:1|max:10',
                'allow_consecutive'   => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $requirement = SubjectWeeklyRequirement::updateOrCreate(
                [
                    'subject_id'   => $request->subject_id,
                    'grade_id'     => $request->grade_id,
                    'classroom_id' => $request->classroom_id,
                ],
                [
                    'periods_per_week'    => $request->periods_per_week,
                    'total_periods_per_year' => $request->input('total_periods_per_year'),
                    'min_periods_per_day' => $request->input('min_periods_per_day', 0),
                    'max_periods_per_day' => $request->input('max_periods_per_day', 2),
                    'allow_consecutive'   => $request->boolean('allow_consecutive', false),
                    'notes'               => $request->notes,
                ]
            );

            // Consistency hint: warn if the weekly count doesn't match the
            // weekly average implied by the new yearly total.
            $planService = new SubjectYearlyPlanService();
            $yearlyPlanWarnings = $planService->mismatchMessages(collect([$requirement]));

            return response()->json([
                'success'  => true,
                'message'  => 'تم حفظ المتطلبات بنجاح',
                'data'     => $requirement,
                'warnings' => $yearlyPlanWarnings,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving requirement',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function destroyRequirement($id)
    {
        try {
            SubjectWeeklyRequirement::findOrFail($id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف المتطلب بنجاح',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting requirement',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate N alternative draft scenarios for a section and compare them.
     */
    public function generateAlternatives(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'section_id'         => 'required|exists:sections,id',
                'count'              => 'required|integer|between:2,6',
                'name_prefix'        => 'nullable|string|max:255',
                'strict_availability' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $sectionId = (int) $request->section_id;
            $count = (int) $request->count;
            $prefix = $request->filled('name_prefix') ? $request->name_prefix : 'سيناريو';
            $strict = $request->boolean('strict_availability', false);

            $section = Section::findOrFail($sectionId);
            $gradeId = $section->Grade_id;
            $classroomId = $section->Class_id;
            $maxVersion = TimetableScenario::where('grade_id', $gradeId)->max('version') ?? 0;

            $optimizer = new TimetableOptimizationService();
            [$days, $daySlots] = $this->resolvePeriodPlan();
            $alternatives = [];

            DB::beginTransaction();

            for ($i = 1; $i <= $count; $i++) {
                $scenario = TimetableScenario::create([
                    'name'         => "{$prefix} - بديل {$i}",
                    'description'  => 'تم إنشاؤه تلقائياً كبديل رقم ' . $i,
                    'grade_id'     => $gradeId,
                    'classroom_id' => $classroomId,
                    'section_id'   => $sectionId,
                    'version'      => $maxVersion + $i,
                    'status'       => 'draft',
                    'is_active'    => false,
                ]);

                $result = $optimizer->generate($sectionId, [
                    'scenario_id'       => $scenario->id,
                    'preserve_locked'   => false,
                    'seed'              => ($i * 97 + $sectionId) % 999983,
                    'strict_availability' => $strict,
                    'days'              => $days,
                    'day_slots'         => $daySlots,
                ]);

                foreach ($result['programs'] as $programData) {
                    WeeklyProgram::create($programData);
                }

                $alternatives[] = [
                    'scenario'    => $scenario->loadCount('programs'),
                    'warnings'    => $result['warnings'],
                    'stats'       => $result['stats'],
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء ' . $count . ' بدائل بنجاح',
                'data'    => $alternatives,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error generating alternatives',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Regenerate a draft scenario preserving only its locked slots.
     */
    public function recalculate(Request $request, $id)
    {
        try {
            $scenario = TimetableScenario::findOrFail($id);

            if ($scenario->status === 'published') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إعادة توليد سيناريو منشور',
                ], 409);
            }

            $sectionId = $scenario->section_id
                ? (int) $scenario->section_id
                : (int) $request->input('section_id');
            if (!$sectionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'السيناريو لا يخص قسماً محدداً',
                ], 400);
            }

            // Keep every existing lesson intact: capture them before deleting so
            // the optimizer re-places them locked at their exact slots and only
            // fills slots that are still free / unmet requirements.
            $existingPrograms = WeeklyProgram::where('scenario_id', $scenario->id)
                ->where('section_id', $sectionId)
                ->where('is_active', true)
                ->get()
                ->toArray();

            DB::beginTransaction();

            WeeklyProgram::where('scenario_id', $scenario->id)
                ->where('section_id', $sectionId)
                ->delete();

            $optimizer = new TimetableOptimizationService();
            [$days, $daySlots] = $this->resolvePeriodPlan();
            $result = $optimizer->generate($sectionId, [
                'scenario_id'       => $scenario->id,
                'preserve_locked'   => false,
                'existing_programs' => $existingPrograms,
                'seed'              => $request->filled('seed') ? (int) $request->seed : null,
                'days'              => $days,
                'day_slots'         => $daySlots,
            ]);

            foreach ($result['programs'] as $programData) {
                WeeklyProgram::create($programData);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إعادة توليد الجدول مع الاحتفاظ بالحصص المثبتة',
                'data'    => [
                    'scenario' => $scenario->fresh()->loadCount('programs'),
                    'programs' => $result['programs'],
                    'warnings' => $result['warnings'],
                    'stats'    => $result['stats'],
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error recalculating scenario',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private function calculateAvgStudentLoad($programs): float
    {
        if ($programs->isEmpty()) return 0;
        $dayCounts = $programs->groupBy('day_of_week')->map->count();
        return round($dayCounts->avg(), 1);
    }

    private function calculateTeacherDistribution($programs): array
    {
        return $programs->groupBy('teacher_id')->map(function ($p) {
            return $p->count();
        })->toArray();
    }
}
