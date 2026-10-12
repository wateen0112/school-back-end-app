<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class AcademicYear extends Model
{
    use HasTranslations;

    public $translatable = ['name'];

    protected $fillable = [
        'school_id',
        'name',
        'start_date',
        'end_date',
        'working_days',
        'start_time',
        'end_time',
        'period_duration_min',
        'periods_count',
        'breaks',
        'status',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'working_days' => 'array',
        'breaks' => 'array',
        'period_duration_min' => 'integer',
        'periods_count' => 'integer',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';

    public static function statuses(): array
    {
        return [self::STATUS_DRAFT, self::STATUS_ACTIVE, self::STATUS_ARCHIVED];
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function weeks()
    {
        return $this->hasMany(AcademicWeek::class);
    }

    public function periods()
    {
        return $this->hasMany(SchedulePeriod::class)->orderBy('period_number');
    }

    public function holidays()
    {
        return $this->hasMany(Holiday::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForSchool($query, ?int $schoolId)
    {
        return $schoolId !== null ? $query->where('school_id', $schoolId) : $query;
    }
}