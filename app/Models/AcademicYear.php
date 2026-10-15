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
        'is_current',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'working_days' => 'array',
        'breaks' => 'array',
        'period_duration_min' => 'integer',
        'periods_count' => 'integer',
        'is_current' => 'boolean',
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

    public function terms()
    {
        return $this->hasMany(AcademicTerm::class)->orderBy('term_code');
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

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /** The current active year + current active term, or null when locked. */
    public static function activeContext(): ?array
    {
        $year = static::query()
            ->where('status', self::STATUS_ACTIVE)
            ->where('is_current', true)
            ->with('terms')
            ->first();

        if (! $year) {
            return null;
        }

        $term = $year->terms->firstWhere('is_current', true);

        return ($term && $term->status === AcademicTerm::STATUS_ACTIVE) ? ['year' => $year, 'term' => $term] : null;
    }

    public function scopeForSchool($query, ?int $schoolId)
    {
        return $schoolId !== null ? $query->where('school_id', $schoolId) : $query;
    }
}