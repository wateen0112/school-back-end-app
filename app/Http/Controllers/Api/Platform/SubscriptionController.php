<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Subscription;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $query = Subscription::with(['school:id,name,email,status', 'plan:id,name,code,price,currency,billing_cycle'])
            ->orderByDesc('id');

        if ($request->query('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->query('school_id')) {
            $query->where('school_id', $request->query('school_id'));
        }

        $subscriptions = $query->paginate($request->integer('per_page', 20))->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Subscriptions retrieved successfully',
            'data' => collect($subscriptions->items())->map(function ($sub) {
                $schoolName = $sub->school?->getTranslations('name');
                $planName = $sub->plan?->getTranslations('name');

                return array_merge($sub->toArray(), [
                    'school_name' => $schoolName['ar'] ?? null,
                    'plan_name' => $planName['ar'] ?? null,
                ]);
            }),
            'meta' => [
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
                'total' => $subscriptions->total(),
            ],
        ], 200);
    }

    /**
     * Assign a plan to a school (creates/replaces the current subscription).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'school_id' => 'required|integer|exists:schools,id',
            'plan_id' => 'required|integer|exists:plans,id',
            'status' => 'nullable|string|in:trial,active,expired,suspended,cancelled',
            'trial_days' => 'nullable|integer|min:0|max:365',
            'ends_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()], 422);
        }

        $school = School::find($request->school_id);
        $subscription = Subscription::firstOrNew(['school_id' => $school->id]);
        $subscription->plan_id = $request->plan_id;
        $subscription->status = $request->status ?: 'active';
        $subscription->starts_at = now();
        $subscription->trial_ends_at = $request->status === 'trial' && $request->has('trial_days')
            ? now()->addDays((int) $request->trial_days)
            : null;
        $subscription->ends_at = $request->filled('ends_at') ? $request->ends_at : null;
        $subscription->save();

        AuditLogger::log('subscription.assigned', 'Subscription', $subscription->id, 'تحديد اشتراك للمدرسة', [
            'school_id' => $school->id,
            'plan_id' => $subscription->plan_id,
            'status' => $subscription->status,
        ]);

        return response()->json(['success' => true, 'message' => 'Subscription assigned successfully', 'data' => $subscription], 201);
    }

    /**
     * Change subscription status / dates.
     */
    public function update(Request $request, $id)
    {
        $subscription = Subscription::find($id);

        if (! $subscription) {
            return response()->json(['success' => false, 'message' => 'Subscription not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'nullable|string|in:trial,active,expired,suspended,cancelled',
            'plan_id' => 'nullable|integer|exists:plans,id',
            'ends_at' => 'nullable|date',
            'auto_renew' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()], 422);
        }

        $subscription->update($request->only(['status', 'plan_id', 'ends_at', 'auto_renew']));

        AuditLogger::log('subscription.updated', 'Subscription', $subscription->id, 'تحديث الاشتراك', [
            'status' => $subscription->status,
        ]);

        return response()->json(['success' => true, 'message' => 'Subscription updated successfully', 'data' => $subscription], 200);
    }

    public function destroy(Request $request, $id)
    {
        $subscription = Subscription::find($id);

        if (! $subscription) {
            return response()->json(['success' => false, 'message' => 'Subscription not found'], 404);
        }

        $subscription->delete();

        AuditLogger::log('subscription.deleted', 'Subscription', $id, 'حذف الاشتراك');

        return response()->json(['success' => true, 'message' => 'Subscription deleted successfully'], 200);
    }
}