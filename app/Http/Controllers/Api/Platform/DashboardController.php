<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\UsageMetric;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $schools = \App\Models\School::withTrashed()->get();

        $subscriptionStatuses = \App\Models\Subscription::selectRaw('status, count(*) as total')
            ->groupBy('status')->pluck('total', 'status');

        $now = now();
        $monthlySchools = \App\Models\School::withTrashed()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, count(*) as total")
            ->where('created_at', '>=', $now->copy()->subMonths(6)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $usageByMetric = UsageMetric::selectRaw('metric, sum(value) as total')
            ->where('recorded_on', '>=', $now->copy()->startOfMonth())
            ->groupBy('metric')->pluck('total', 'metric');

        $failedLogins = AuditLog::where('action', 'auth.login_failed')
            ->where('created_at', '>=', $now->copy()->subDays(7))
            ->count();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard stats retrieved successfully',
            'data' => [
                'schools' => [
                    'total' => $schools->count(),
                    'active' => $schools->whereNull('suspended_at')->whereNull('archived_at')->count(),
                    'suspended' => $schools->whereNotNull('suspended_at')->count(),
                    'archived' => $schools->whereNotNull('archived_at')->count(),
                ],
                'subscriptions' => [
                    'trial' => $subscriptionStatuses->get('trial', 0),
                    'active' => $subscriptionStatuses->get('active', 0),
                    'expired' => $subscriptionStatuses->get('expired', 0),
                    'suspended' => $subscriptionStatuses->get('suspended', 0),
                    'cancelled' => $subscriptionStatuses->get('cancelled', 0),
                ],
                'users' => [
                    'admins' => User::count(),
                    'teachers' => DB::table('teachers')->count(),
                    'parents' => DB::table('my__parents')->count(),
                    'students' => DB::table('students')->count(),
                ],
                'plans' => \App\Models\Plan::count(),
                'branches' => \App\Models\SchoolBranch::count(),
                'audit' => [
                    'total' => AuditLog::count(),
                    'failed_logins_7d' => $failedLogins,
                ],
                'schools_monthly' => $monthlySchools,
                'usage_this_month' => $usageByMetric,
            ],
        ], 200);
    }
}