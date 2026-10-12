<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchedulePeriod extends Model
{
    protected $fillable = [
        'academic_year_id',
        'period_number',
        'start_time',
        'end_time',
        'type',
        'note',
    ];

    protected $casts = [
        'period_number' => 'integer',
    ];

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