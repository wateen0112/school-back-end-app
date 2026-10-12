<?php

namespace App\Services\Scoping;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Resolves the tenant (school) scope from the acting authenticated model
 * and applies it to query builders. The AI assistant and integration bridge
 * rely on this so data never leaks across schools.
 */
class SchoolContext
{
    public static function schoolIdFrom(?Model $actor): ?int
    {
        if (! $actor) {
            return null;
        }

        if ($actor instanceof \App\User) {
            return $actor->school_id !== null ? (int) $actor->school_id : null;
        }

        // Teacher / Student / My_Parent now carry school_id after migration
        if (in_array('school_id', $actor->getFillable() ?? [], true) || in_array('school_id',
            array_keys($actor->getAttributes() ?? []), true)) {
            $value = $actor->getAttribute('school_id');

            return $value !== null ? (int) $value : null;
        }

        return null;
    }

    /**
     * Super admins act globally (null school) or may target a school via request header/param.
     */
    public static function effectiveSchoolId(?Model $actor): ?int
    {
        $school = static::schoolIdFrom($actor);

        if ($school !== null) {
            return $school;
        }

        // Allow a super admin to scope to one school explicitly
        if ($actor instanceof \App\User && $actor->is_super_admin) {
            $requested = request()->get('school_id') ?? request()->header('X-School-Id');

            return $requested !== null ? (int) $requested : null;
        }

        return null;
    }

    /**
     * Apply school scoping to a query when a tenant is active.
     */
    public static function scope(Builder $query, ?int $schoolId, string $column = 'school_id'): void
    {
        if ($schoolId !== null) {
            $query->where($column, $schoolId);
        }
    }
}