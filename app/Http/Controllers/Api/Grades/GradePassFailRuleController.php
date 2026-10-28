<?php

namespace App\Http\Controllers\Api\Grades;

use App\Http\Controllers\Controller;
use App\Models\GradePassFailRule;
use Illuminate\Http\Request;

/**
 * إعدادات النجاح والرسوب الخاصة بكل صف (Grade) على حدة:
 * - single_subject: مادة معينة تُعتبر مرسبة إذا لم يستوفِ الطالب الحد الأدنى فيها.
 * - combined_subjects: مادتان معينتان تُحتسبان كمجموع، ويرسب الطالب إذا لم
 *   يحقق النسبة المئوية الدنيا من مجموع علامتيهما.
 */
class GradePassFailRuleController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = GradePassFailRule::with(['classroom', 'subject', 'secondSubject']);

            if ($request->filled('classroom_id')) {
                $query->where('classroom_id', $request->classroom_id);
            }
            if ($request->filled('grade_id')) {
                $query->where('grade_id', $request->grade_id);
            }
            if ($request->filled('rule_type')) {
                $query->where('rule_type', $request->rule_type);
            }

            return response()->json([
                'success' => true,
                'message' => 'Grade pass/fail rules retrieved successfully',
                'data' => $query->get(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving grade pass/fail rules',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'classroom_id' => 'required|exists:Classrooms,id',
                'grade_id' => 'nullable|exists:Grades,id',
                'rule_type' => 'required|in:single_subject,combined_subjects',
                // مادة مرسبة: المادة المحددة إلزامية
                'subject_id' => 'required_if:rule_type,single_subject|nullable|exists:subjects,id',
                // مادتان محددتان (اختياري): إن لم تُحددا طُبقت القاعدة على أي مادتين
                'second_subject_id' => 'nullable|exists:subjects,id|different:subject_id',
                // المواد المستثناة من قاعدة المجموع (إلزامي تعيينها لقاعدة أي مادتين)
                'excluded_subject_ids' => 'nullable|array',
                'excluded_subject_ids.*' => 'exists:subjects,id',
                'subject_min_mark' => 'required_if:rule_type,single_subject|nullable|numeric|min:0',
                'combined_min_percentage' => 'required_if:rule_type,combined_subjects|nullable|numeric|min:0|max:100',
                'is_active' => 'nullable|boolean',
            ]);

            $classroom = \App\Models\Classroom::find($validated['classroom_id']);

            $rule = GradePassFailRule::updateOrCreate(
                [
                    'classroom_id' => $validated['classroom_id'],
                    'rule_type' => $validated['rule_type'],
                    'subject_id' => $validated['subject_id'] ?? null,
                    'second_subject_id' => $validated['second_subject_id'] ?? null,
                ],
                [
                    'grade_id' => $validated['grade_id'] ?? optional($classroom)->Grade_id,
                    'subject_min_mark' => $validated['subject_min_mark'] ?? null,
                    'combined_min_percentage' => $validated['combined_min_percentage'] ?? null,
                    'excluded_subject_ids' => $validated['excluded_subject_ids'] ?? null,
                    'is_active' => $validated['is_active'] ?? true,
                ]
            );

            $rule->load(['classroom', 'subject', 'secondSubject']);

            return response()->json([
                'success' => true,
                'message' => 'Grade pass/fail rule saved successfully',
                'data' => $rule,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving grade pass/fail rule',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $rule = GradePassFailRule::with(['classroom', 'subject', 'secondSubject'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Grade pass/fail rule retrieved successfully',
                'data' => $rule,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving grade pass/fail rule',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تعديل قاعدة موجودة (تحديث مباشر بالمعرف).
     */
    public function update(Request $request, $id)
    {
        try {
            $rule = GradePassFailRule::findOrFail($id);

            $validated = $request->validate([
                'rule_type' => 'required|in:single_subject,combined_subjects',
                'subject_id' => 'required_if:rule_type,single_subject|nullable|exists:subjects,id',
                'second_subject_id' => 'nullable|exists:subjects,id|different:subject_id',
                'excluded_subject_ids' => 'nullable|array',
                'excluded_subject_ids.*' => 'exists:subjects,id',
                'subject_min_mark' => 'required_if:rule_type,single_subject|nullable|numeric|min:0',
                'combined_min_percentage' => 'required_if:rule_type,combined_subjects|nullable|numeric|min:0|max:100',
                'is_active' => 'nullable|boolean',
            ]);

            $rule->update([
                'rule_type' => $validated['rule_type'],
                'subject_id' => $validated['subject_id'] ?? null,
                'second_subject_id' => $validated['second_subject_id'] ?? null,
                'excluded_subject_ids' => $validated['excluded_subject_ids'] ?? null,
                'subject_min_mark' => $validated['subject_min_mark'] ?? null,
                'combined_min_percentage' => $validated['combined_min_percentage'] ?? null,
                'is_active' => $validated['is_active'] ?? $rule->is_active,
            ]);

            $rule->load(['classroom', 'subject', 'secondSubject']);

            return response()->json([
                'success' => true,
                'message' => 'Grade pass/fail rule updated successfully',
                'data' => $rule,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating grade pass/fail rule',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $rule = GradePassFailRule::findOrFail($id);
            $rule->delete();

            return response()->json([
                'success' => true,
                'message' => 'Grade pass/fail rule deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting grade pass/fail rule',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
