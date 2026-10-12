<?php

namespace App\Http\Controllers\Api\Schools;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;

/**
 * Serves a school's custom branding (color theme + logo) to its own
 * dashboard. The theme is set by the super admin; any authenticated user
 * that belongs to a school can read it so the dashboard can apply it.
 */
class SchoolBrandingController extends Controller
{
    /**
     * Resolve branding for the authenticated user's school.
     * A super admin may preview any school via ?school_id=.
     */
    public function show(Request $request)
    {
        $user = $request->user() ?: auth()->user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $schoolId = $user->school_id ?? null;

        // Super admin has no school of their own; allow explicit preview.
        if (! $schoolId && ! empty($user->is_super_admin)) {
            $schoolId = $request->integer('school_id') ?: null;
        }

        if (! $schoolId) {
            return response()->json(['success' => false, 'message' => 'No school associated with this account'], 404);
        }

        $school = School::where('id', $schoolId)->active()->first();

        if (! $school) {
            return response()->json(['success' => false, 'message' => 'School not found'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'School branding retrieved successfully',
            'data' => [
                'school_id' => $school->id,
                'name' => $school->getTranslations('name'),
                'logo_url' => $school->logoUrl(),
                'theme' => $school->theme,
            ],
        ], 200);
    }
}