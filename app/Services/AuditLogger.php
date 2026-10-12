<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Write an audit entry (best-effort; never breaks the request).
     */
    public static function log(
        string $action,
        ?string $entityType = null,
        $entityId = null,
        ?string $description = null,
        array $meta = []
    ): void {
        try {
            $user = static::actingUser();

            AuditLog::create([
                'user_id' => $user?->id,
                'user_type' => $user ? get_class($user) : null,
                'school_id' => static::resolveSchoolId($user),
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'description' => $description,
                'meta' => $meta,
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 500),
            ]);
        } catch (\Throwable $e) {
            // Auditing must never break a request.
        }
    }

    /**
     * Write a data-change audit with before/after snapshots.
     */
    public static function logChange(
        string $action,
        Model $entity,
        array $old,
        array $new,
        ?string $reason = null,
        ?string $approvalRef = null,
        array $extraMeta = []
    ): void {
        try {
            $user = static::actingUser();
            $diff = self::diffArrays($old, $new);

            AuditLog::create([
                'user_id' => $user?->id,
                'user_type' => $user ? get_class($user) : null,
                'school_id' => static::resolveSchoolId($user) ?? ($entity->getAttribute('school_id') ?? null),
                'action' => $action,
                'entity_type' => get_class($entity),
                'entity_id' => $entity->getKey(),
                'description' => static::buildChangeDescription($entity, $diff),
                'meta' => array_merge($extraMeta, ['changed' => array_keys($diff)]),
                'old_values' => $old,
                'new_values' => $new,
                'reason' => $reason,
                'approval_reference' => $approvalRef,
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 500),
            ]);
        } catch (\Throwable $e) {
            // Auditing must never break a request.
        }
    }

    private static function actingUser(): ?Model
    {
        return Auth::guard('api')->user()
            ?? Auth::guard('web')->user()
            ?? Auth::guard('teacher')->user()
            ?? Auth::guard('student')->user()
            ?? Auth::guard('parent')->user();
    }

    public static function resolveSchoolId(?Model $user): ?int
    {
        if (! $user) {
            return null;
        }

        // User (admin) model
        if ($user instanceof \App\User || $user instanceof \App\Models\School) {
            return $user->getAttribute('school_id');
        }

        // Student / Teacher / My_Parent — school_id added by migration
        $val = $user->getAttribute('school_id');

        return $val !== null ? (int) $val : null;
    }

    private static function diffArrays(array $old, array $new): array
    {
        $diff = [];
        $allKeys = array_unique(array_merge(array_keys($old), array_keys($new)));

        foreach ($allKeys as $key) {
            $o = $old[$key] ?? null;
            $n = $new[$key] ?? null;
            if ($o !== $n) {
                $diff[$key] = ['old' => $o, 'new' => $n];
            }
        }

        return $diff;
    }

    private static function buildChangeDescription(Model $entity, array $diff): string
    {
        $short = class_basename($entity);
        $changedKeys = implode(', ', array_keys($diff));

        return "{$short} #{$entity->getKey()} changed [{$changedKeys}]";
    }
}
