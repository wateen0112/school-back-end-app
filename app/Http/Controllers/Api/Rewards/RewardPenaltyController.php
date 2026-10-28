<?php

namespace App\Http\Controllers\Api\Rewards;

use App\Http\Controllers\Api\Transportation\TransportationController;
use App\Models\RewardPenaltySetting;
use App\Models\Student;
use App\Models\StudentMonthlyReport;
use App\Models\StudentRewardPenalty;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Student rewards & penalties based on progress. Records are school-scoped,
 * tie back to the student, and can be suggested automatically from the
 * monthly report progress score / trend.
 */
class RewardPenaltyController extends TransportationController
{
    /** Paginated, filtered list of reward/penalty records. */
    public function index(Request $request)
    {
        try {
            $query = $this->scopeToSchool(
                StudentRewardPenalty::with(['student:id,name,Grade_id,Classroom_id,section_id', 'creator:id,name'])
                    ->orderByDesc('awarded_at')
                    ->orderByDesc('id'),
                $request
            );

            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('source')) {
                $query->where('source', $request->source);
            }
            if ($request->filled('student_id')) {
                $query->where('student_id', $request->integer('student_id'));
            }
            if ($request->filled('grade_id') || $request->filled('classroom_id') || $request->filled('section_id')) {
                $query->whereHas('student', function ($q) use ($request) {
                    if ($request->filled('grade_id')) {
                        $q->where('Grade_id', $request->integer('grade_id'));
                    }
                    if ($request->filled('classroom_id')) {
                        $q->where('Classroom_id', $request->integer('classroom_id'));
                    }
                    if ($request->filled('section_id')) {
                        $q->where('section_id', $request->integer('section_id'));
                    }
                });
            }
            if ($request->filled('period_month')) {
                $query->where('period_month', $request->period_month);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%"));
            }

            return $this->ok($query->paginate($request->integer('per_page', 20)), 'Rewards and penalties retrieved successfully');
        } catch (\Exception $e) {
            return $this->fail('Error retrieving rewards and penalties', 500, $e->getMessage());
        }
    }

    /** Aggregate counts/points for the dashboard cards. */
    public function stats(Request $request)
    {
        try {
            $base = $this->scopeToSchool(StudentRewardPenalty::query(), $request)
                ->where('status', 'approved');

            $rewards = (clone $base)->where('type', 'reward');
            $penalties = (clone $base)->where('type', 'penalty');

            return $this->ok([
                'rewards_count' => (clone $rewards)->count(),
                'penalties_count' => (clone $penalties)->count(),
                'total_reward_points' => round((float) (clone $rewards)->sum('points'), 2),
                'total_penalty_points' => round((float) (clone $penalties)->sum('points'), 2),
                'students_rewarded' => (clone $rewards)->distinct()->count('student_id'),
                'students_penalized' => (clone $penalties)->distinct()->count('student_id'),
            ], 'Reward and penalty stats retrieved successfully');
        } catch (\Exception $e) {
            return $this->fail('Error retrieving stats', 500, $e->getMessage());
        }
    }

    /**
     * Suggested rewards/penalties derived from the latest monthly report:
     * a student scoring above the reward threshold or improving earns a
     * reward; one below the penalty threshold or declining earns a penalty.
     */
    public function suggestions(Request $request)
    {
        try {
            $setting = RewardPenaltySetting::forSchool($this->schoolId($request));

            $query = $this->scopeToSchool(
                Student::select('id', 'name', 'school_id', 'Grade_id', 'Classroom_id', 'section_id'),
                $request
            );

            if ($request->filled('grade_id')) {
                $query->where('Grade_id', $request->integer('grade_id'));
            }
            if ($request->filled('classroom_id')) {
                $query->where('Classroom_id', $request->integer('classroom_id'));
            }
            if ($request->filled('section_id')) {
                $query->where('section_id', $request->integer('section_id'));
            }

            $students = $query->get();
            $studentIds = $students->pluck('id')->all();

            $reports = StudentMonthlyReport::whereIn('student_id', $studentIds)
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->get()
                ->unique('student_id')
                ->keyBy('student_id');

            $existing = StudentRewardPenalty::whereIn('student_id', $studentIds)
                ->where('source', 'auto')
                ->where('status', 'approved')
                ->get()
                ->groupBy(fn ($r) => $r->student_id.'|'.$r->type.'|'.$r->period_month);

            $suggestions = [];
            foreach ($students as $student) {
                $report = $reports->get($student->id);
                if (! $report) {
                    continue;
                }
                $period = sprintf('%04d-%02d', $report->year, $report->month);
                $score = (float) $report->progress_score;

                $candidate = null;
                if ($setting->rewards_enabled && ($score >= $setting->reward_threshold || $report->trend === 'improved')) {
                    $candidate = [
                        'type' => 'reward',
                        'title' => $report->trend === 'improved' && $score < $setting->reward_threshold
                            ? 'تحسّن ملحوظ في الأداء'
                            : 'تميّز أكاديمي',
                        'points' => $setting->improved_bonus_points,
                        'severity' => null,
                    ];
                } elseif ($setting->penalties_enabled && ($score <= $setting->penalty_threshold || $report->trend === 'declined')) {
                    $candidate = [
                        'type' => 'penalty',
                        'title' => $report->trend === 'declined' && $score > $setting->penalty_threshold
                            ? 'تراجع في الأداء'
                            : 'ضعف في التحصيل',
                        'points' => $setting->declined_penalty_points,
                        'severity' => 2,
                    ];
                }

                if (! $candidate) {
                    continue;
                }
                if ($existing->has($student->id.'|'.$candidate['type'].'|'.$period)) {
                    continue;
                }

                $suggestions[] = array_merge($candidate, [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'grade_id' => $student->Grade_id,
                    'classroom_id' => $student->Classroom_id,
                    'section_id' => $student->section_id,
                    'progress_score' => $score,
                    'trend' => $report->trend,
                    'period_month' => $period,
                ]);
            }

            return $this->ok(['suggestions' => $suggestions], 'Suggestions retrieved successfully');
        } catch (\Exception $e) {
            return $this->fail('Error retrieving suggestions', 500, $e->getMessage());
        }
    }

    /** Apply a batch of (auto) suggestions as real records. */
    public function applySuggestions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.student_id' => 'required|exists:students,id',
            'items.*.type' => 'required|in:reward,penalty',
            'items.*.title' => 'required|string|max:255',
            'items.*.points' => 'nullable|numeric|min:0|max:99999.99',
            'items.*.progress_score' => 'nullable|numeric',
            'items.*.period_month' => 'nullable|string|max:7',
            'items.*.severity' => 'nullable|integer|min:1|max:5',
        ]);
        if ($validator->fails()) {
            return $this->fail('Validation error', 422, $validator->errors()->first());
        }

        try {
            $schoolId = $this->schoolId($request);
            $userId = $request->user()?->id;
            $created = 0;

            DB::transaction(function () use ($request, $schoolId, $userId, &$created) {
                foreach ($request->items as $item) {
                    StudentRewardPenalty::create([
                        'student_id' => $item['student_id'],
                        'school_id' => $schoolId,
                        'type' => $item['type'],
                        'source' => 'auto',
                        'title' => $item['title'],
                        'reason' => $item['reason'] ?? null,
                        'points' => $item['points'] ?? 0,
                        'progress_score_snapshot' => $item['progress_score'] ?? null,
                        'period_month' => $item['period_month'] ?? null,
                        'severity' => $item['severity'] ?? null,
                        'status' => 'approved',
                        'created_by' => $userId,
                        'approved_by' => $userId,
                        'awarded_at' => now(),
                    ]);
                    $created++;
                }
            });

            return $this->ok(['created' => $created], 'Suggestions applied successfully');
        } catch (\Exception $e) {
            return $this->fail('Error applying suggestions', 500, $e->getMessage());
        }
    }

    /** Create a manual reward/penalty record. */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'type' => 'required|in:reward,penalty',
            'title' => 'required|string|max:255',
            'reason' => 'nullable|string|max:2000',
            'points' => 'nullable|numeric|min:0|max:99999.99',
            'amount' => 'nullable|numeric|min:0|max:99999999.99',
            'progress_score_snapshot' => 'nullable|numeric',
            'period_month' => 'nullable|string|max:7',
            'severity' => 'nullable|integer|min:1|max:5',
            'status' => 'nullable|in:draft,approved,cancelled',
        ]);
        if ($validator->fails()) {
            return $this->fail('Validation error', 422, $validator->errors()->first());
        }

        try {
            $student = Student::findOrFail($request->student_id);
            $this->assertSchool($student->school_id, $request);

            $data = $validator->validated();
            $userId = $request->user()?->id;
            $status = $data['status'] ?? 'approved';

            $record = StudentRewardPenalty::create([
                'student_id' => $student->id,
                'school_id' => $this->schoolId($request),
                'type' => $data['type'],
                'source' => 'manual',
                'title' => $data['title'],
                'reason' => $data['reason'] ?? null,
                'points' => $data['points'] ?? 0,
                'amount' => $data['amount'] ?? null,
                'progress_score_snapshot' => $data['progress_score_snapshot'] ?? null,
                'period_month' => $data['period_month'] ?? null,
                'severity' => $data['severity'] ?? null,
                'status' => $status,
                'created_by' => $userId,
                'approved_by' => $status === 'approved' ? $userId : null,
                'awarded_at' => now(),
            ]);

            AuditLogger::log('rewards.created', 'student_reward_penalty', $record->id);

            return $this->ok($record->load('student:id,name,Grade_id,Classroom_id,section_id'), 'Reward/penalty created successfully', 201);
        } catch (\Exception $e) {
            return $this->fail('Error creating reward/penalty', 500, $e->getMessage());
        }
    }

    /** Update an existing record. */
    public function update(Request $request, $id)
    {
        try {
            $record = StudentRewardPenalty::with('student')->findOrFail($id);
            $this->assertSchool($record->school_id ?? $record->student?->school_id, $request);

            $validator = Validator::make($request->all(), [
                'type' => 'nullable|in:reward,penalty',
                'title' => 'nullable|string|max:255',
                'reason' => 'nullable|string|max:2000',
                'points' => 'nullable|numeric|min:0|max:99999.99',
                'amount' => 'nullable|numeric|min:0|max:99999999.99',
                'severity' => 'nullable|integer|min:1|max:5',
                'status' => 'nullable|in:draft,approved,cancelled',
            ]);
            if ($validator->fails()) {
                return $this->fail('Validation error', 422, $validator->errors()->first());
            }

            foreach ($validator->validated() as $key => $value) {
                $record->{$key} = $value;
            }
            if ($request->filled('status') && $request->status === 'approved' && ! $record->approved_by) {
                $record->approved_by = $request->user()?->id;
            }
            $record->save();

            AuditLogger::log('rewards.updated', 'student_reward_penalty', $record->id);

            return $this->ok($record->fresh('student:id,name,Grade_id,Classroom_id,section_id'), 'Reward/penalty updated successfully');
        } catch (\Exception $e) {
            return $this->fail('Error updating reward/penalty', 500, $e->getMessage());
        }
    }

    /** Soft cancel / delete a record. */
    public function destroy(Request $request, $id)
    {
        try {
            $record = StudentRewardPenalty::with('student')->findOrFail($id);
            $this->assertSchool($record->school_id ?? $record->student?->school_id, $request);

            $record->delete();

            AuditLogger::log('rewards.deleted', 'student_reward_penalty', $record->id);

            return $this->ok(null, 'Reward/penalty deleted successfully');
        } catch (\Exception $e) {
            return $this->fail('Error deleting reward/penalty', 500, $e->getMessage());
        }
    }

    /** Read the school's rewards/penalties configuration. */
    public function settings(Request $request)
    {
        try {
            $setting = RewardPenaltySetting::forSchool($this->schoolId($request));

            return $this->ok($setting, 'Settings retrieved successfully');
        } catch (\Exception $e) {
            return $this->fail('Error retrieving settings', 500, $e->getMessage());
        }
    }

    /** Update the school's rewards/penalties configuration. */
    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rewards_enabled' => 'nullable|boolean',
            'penalties_enabled' => 'nullable|boolean',
            'auto_apply' => 'nullable|boolean',
            'reward_threshold' => 'nullable|numeric|between:0,100',
            'penalty_threshold' => 'nullable|numeric|between:0,100',
            'improved_bonus_points' => 'nullable|numeric|min:0|max:99999.99',
            'declined_penalty_points' => 'nullable|numeric|min:0|max:99999.99',
            'reward_catalog' => 'nullable|array',
            'penalty_catalog' => 'nullable|array',
        ]);
        if ($validator->fails()) {
            return $this->fail('Validation error', 422, $validator->errors()->first());
        }

        try {
            $setting = RewardPenaltySetting::forSchool($this->schoolId($request));
            foreach ($validator->validated() as $key => $value) {
                $setting->{$key} = $value;
            }
            $setting->save();

            AuditLogger::log('rewards.settings_updated', 'reward_penalty_setting', $setting->id);

            return $this->ok($setting, 'Settings updated successfully');
        } catch (\Exception $e) {
            return $this->fail('Error updating settings', 500, $e->getMessage());
        }
    }
}
