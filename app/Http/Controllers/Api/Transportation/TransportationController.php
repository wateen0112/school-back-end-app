<?php

namespace App\Http\Controllers\Api\Transportation;

use App\Http\Controllers\Controller;
use App\Models\Transportation\DriverProfile;
use App\Services\Scoping\SchoolContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Shared helpers for the transportation module:
 *  - tenant scoping through the existing SchoolContext,
 *  - response envelope matching the project's API conventions,
 *  - driver/parent actor resolution for the mobile endpoints.
 */
abstract class TransportationController extends Controller
{
    /** Tenant id for the acting user, or null for a global super admin. */
    protected function schoolId(Request $request): ?int
    {
        return SchoolContext::effectiveSchoolId($request->user());
    }

    /** Constrain a query to the actor's school (no-op for global super admins). */
    protected function scopeToSchool(Builder $query, Request $request): Builder
    {
        if ($schoolId = $this->schoolId($request)) {
            SchoolContext::scope($query, $schoolId);
        }

        return $query;
    }

    /** Enforce tenant isolation for a resolved model instance. */
    protected function assertSchool(?int $modelSchoolId, Request $request): void
    {
        $schoolId = $this->schoolId($request);
        if ($schoolId !== null && (int) $modelSchoolId !== (int) $schoolId) {
            abort(404, 'Not found.');
        }
    }

    /**
     * The authenticated driver (an App\User holding an active driver
     * profile), or 403. Resolved through the web guard explicitly: a
     * driver IS a staff user, and the auth.any middleware sets that guard.
     */
    /** The authenticated driver user (App\User), or 403. */
    protected function driverUser(Request $request): \App\User
    {
        $user = \Illuminate\Support\Facades\Auth::guard('web')->user() ?? $request->user();

        abort_unless($user instanceof \App\User, 403, 'Drivers only.');

        return $user;
    }

    protected function driverProfile(Request $request): DriverProfile
    {
        $user = $this->driverUser($request);

        $profile = DriverProfile::where('user_id', $user->id)->first();

        abort_unless($profile, 403, 'No driver profile for this account.');
        abort_if($profile->status !== 'ACTIVE', 403, 'Driver account is inactive.');

        return $profile;
    }

    protected function ok($data, string $message = 'OK', int $status = 200)
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }

    protected function fail(string $message, int $status = 500, ?string $error = null)
    {
        $payload = ['success' => false, 'message' => $message];
        if ($error !== null) {
            $payload['error'] = $error;
        }
        return response()->json($payload, $status);
    }
}
