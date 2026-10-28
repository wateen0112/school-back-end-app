<?php

namespace App\Models\Concerns;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Automatically audit model lifecycle events into the audit_logs table.
 * Snapshots strip sensitive/hidden fields via $auditExclude.
 */
trait Auditable
{
    /** @var array<string> Fields never written to audit snapshots. */
    public static $auditExclude = ['password', 'remember_token', 'api_token'];

    /** @var array<string, array> Pending before/after snapshots, keyed by model object hash. */
    protected static $auditPending = [];

    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            AuditLogger::logChange(
                static::auditAction($model, 'created'),
                $model,
                [],
                static::auditSnapshot($model, $model->getAttributes())
            );
        });

        static::updating(function (Model $model) {
            if (! $model->isDirty()) {
                return;
            }

            static::$auditPending[spl_object_hash($model)] = array_intersect_key(
                $model->getOriginal(),
                $model->getDirty()
            );
        });

        static::updated(function (Model $model) {
            $hash = spl_object_hash($model);
            $old = static::$auditPending[$hash] ?? [];
            unset(static::$auditPending[$hash]);

            $new = array_intersect_key($model->getAttributes(), $old ?: $model->getChanges());

            AuditLogger::logChange(
                static::auditAction($model, 'updated'),
                $model,
                static::auditSnapshot($model, $old),
                static::auditSnapshot($model, $new)
            );
        });

        static::deleted(function (Model $model) {
            AuditLogger::logChange(
                static::auditAction($model, 'deleted'),
                $model,
                static::auditSnapshot($model, $model->getAttributes()),
                []
            );
        });
    }

    protected static function auditAction(Model $model, string $event): string
    {
        return Str::snake(class_basename($model)).'.'.$event;
    }

    /** Strip excluded/hidden fields from a snapshot. */
    protected static function auditSnapshot(Model $model, array $values): array
    {
        $excluded = array_merge(
            static::$auditExclude,
            (array) ($model->getHidden() ?? [])
        );

        return array_diff_key($values, array_flip($excluded));
    }
}
