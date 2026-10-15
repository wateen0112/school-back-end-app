<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchedulePeriod extends Model
{
    protected $fillable = [
        'academic_year_id',
        'period_number',
        'day_of_week',
        'start_time',
        'end_time',
        'type',
        'note',
    ];

    protected $casts = [
        'period_number' => 'integer',
        'day_of_week' => 'integer',
    ];

    /**
     * Periods effective for a given weekday: global rows (day_of_week NULL)
     * are overridden by day-specific rows with the same period number.
     */
    public static function forDay(int $academicYearId, int $dayOfWeek)
    {
        $rows = static::query()
            ->where('academic_year_id', $academicYearId)
            ->where(fn ($q) => $q->whereNull('day_of_week')->orWhere('day_of_week', $dayOfWeek))
            ->orderBy('period_number')
            ->get();

        return $rows
            ->groupBy('period_number')
            ->map(fn ($group) => $group->firstWhere('day_of_week', $dayOfWeek) ?? $group->first())
            ->sortKeys()
            ->values();
    }

    public const TYPE_LESSON = 'lesson';
    public const TYPE_BREAK = 'break';

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function scopeLessons($query)
    {
        return $query->where('type', self::TYPE_LESSON);
    }
}