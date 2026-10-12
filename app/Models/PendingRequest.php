<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PendingRequest extends Model
{
    protected $guarded = [];

    protected $casts = [
        'payload'   => 'array',
        'reviewed_at' => 'datetime',
        'applied_at' => 'datetime',
    ];

    public const STATUS_PENDING   = 'pending';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_REJECTED  = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH   = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED,
        ];
    }

    public static function priorities(): array
    {
        return [self::PRIORITY_NORMAL, self::PRIORITY_HIGH, self::PRIORITY_URGENT];
    }

    public function requester()
    {
        return $this->morphTo('requester', 'requested_by_type', 'requested_by_id');
    }

    public function reviewer()
    {
        return $this->morphTo('reviewer', 'reviewed_by_type', 'reviewed_by_id');
    }

    /** The record the pending action would change, when it still exists. */
    public function entity()
    {
        if (! $this->entity_type || ! $this->entity_id) {
            return null;
        }

        $class = $this->entity_type;

        if (! class_exists($class)) {
            return null;
        }

        return $class::find($this->entity_id);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeOfSchool(Builder $query, ?int $schoolId): Builder
    {
        if ($schoolId !== null) {
            $query->where('school_id', $schoolId);
        }

        return $query;
    }

    public function scopeOfRequester(Builder $query, ?Model $actor): Builder
    {
        if (! $actor) {
            return $query;
        }

        return $query
            ->where('requested_by_id', $actor->getKey())
            ->where('requested_by_type', get_class($actor));
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /** Bilingual title resolved for the active locale. */
    public function localizedTitle(): string
    {
        if (app()->getLocale() === 'ar' && $this->title_ar) {
            return $this->title_ar;
        }

        return $this->title;
    }

    /** Bilingual description resolved for the active locale. */
    public function localizedDescription(): ?string
    {
        if (app()->getLocale() === 'ar' && $this->description_ar) {
            return $this->description_ar;
        }

        return $this->description;
    }

    /** Human readable label for the requested action in the active locale. */
    public function actionLabel(): string
    {
        return \App\Services\Pending\PendingActionRegistry::label($this->action, $this->title);
    }
}