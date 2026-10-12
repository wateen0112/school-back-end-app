<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherWeeklySchedule extends Model
{
    protected $table = 'teacher_weekly_schedules';

    protected $fillable = [
        'teacher_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_available',
        'is_preferred',
        'note',
    ];

    protected $casts = [
        'day_of_week'  => 'integer',
        'is_available' => 'boolean',
        'is_preferred' => 'boolean',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }
}