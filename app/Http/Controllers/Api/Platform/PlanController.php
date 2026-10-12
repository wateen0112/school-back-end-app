<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::orderBy('sort_order')->orderBy('id')->get()
            ->map(fn ($plan) => $this->format($plan));

        return response()->json(['success' => true, 'message' => 'Plans retrieved successfully', 'data' => $plans], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'code' => 'required|string|max:50|unique:plans,code',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'billing_cycle' => 'nullable|string|in:monthly,yearly',
            'max_students' => 'nullable|integer|min:0',
            'max_teachers' => 'nullable|integer|min:0',
            'max_branches' => 'nullable|integer|min:0',
            'max_storage_gb' => 'nullable|integer|min:0',
            'max_api_calls_monthly' => 'nullable|integer|min:0',
            'ai_enabled' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()], 422);
        }

        $plan = Plan::create([
            'name' => ['ar' => $request->name, 'en' => $request->name_en ?: $request->name],
            'code' => $request->code,
            'price' => (float) $request->price,
            'currency' => $request->currency ?: 'SAR',
            'billing_cycle' => $request->billing_cycle ?: 'monthly',
            'features' => $request->features ?? [],
            'max_students' => $request->max_students,
            'max_teachers' => $request->max_teachers,
            'max_branches' => $request->max_branches ?? 1,
            'max_storage_gb' => $request->max_storage_gb ?? 5,
            'max_api_calls_monthly' => $request->max_api_calls_monthly ?? 100000,
            'ai_enabled' => $request->boolean('ai_enabled'),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        AuditLogger::log('plan.created', 'Plan', $plan->id, 'إنشاء خطة اشتراك جديدة');

        return response()->json(['success' => true, 'message' => 'Plan created successfully', 'data' => $this->format($plan)], 201);
    }

    public function update(Request $request, $id)
    {
        $plan = Plan::find($id);

        if (! $plan) {
            return response()->json(['success' => false, 'message' => 'Plan not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:50|unique:plans,code,'.$plan->id,
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'billing_cycle' => 'nullable|string|in:monthly,yearly',
            'max_students' => 'nullable|integer|min:0',
            'max_teachers' => 'nullable|integer|min:0',
            'max_branches' => 'nullable|integer|min:0',
            'max_storage_gb' => 'nullable|integer|min:0',
            'max_api_calls_monthly' => 'nullable|integer|min:0',
            'ai_enabled' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()], 422);
        }

        if ($request->filled('name') || $request->filled('name_en')) {
            $translations = $plan->getTranslations('name');
            $translations['ar'] = $request->input('name', $translations['ar'] ?? '');
            $translations['en'] = $request->input('name_en', $translations['en'] ?? $translations['ar'] ?? '');
            $plan->name = $translations;
        }

        $plan->fill($request->except(['name', 'name_en']));
        $plan->save();

        AuditLogger::log('plan.updated', 'Plan', $plan->id, 'تعديل خطة الاشتراك');

        return response()->json(['success' => true, 'message' => 'Plan updated successfully', 'data' => $this->format($plan)], 200);
    }

    public function destroy(Request $request, $id)
    {
        $plan = Plan::find($id);

        if (! $plan) {
            return response()->json(['success' => false, 'message' => 'Plan not found'], 404);
        }

        if ($plan->subscriptions()->exists()) {
            return response()->json(['success' => false, 'message' => 'لا يمكن حذف خطة مرتبطة باشتراكات'], 409);
        }

        $plan->delete();

        AuditLogger::log('plan.deleted', 'Plan', $id, 'حذف خطة الاشتراك');

        return response()->json(['success' => true, 'message' => 'Plan deleted successfully'], 200);
    }

    private function format(Plan $plan)
    {
        $data = $plan->toArray();
        $data['name'] = $plan->getTranslations('name');

        return $data;
    }
}
