<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklyProgram extends Model
{
    protected $table = 'weekly_programs';

    protected $fillable = [
        'section_id',
        'day_of_week',
        'start_time',
        'end_time',
        'subject_id',
        'teacher_id',
        'location',
        'notes',
        'is_active',
        'scenario_id',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_active'   => 'boolean',
    ];

    // علاقة الحصة بالقسم
    public function section()
    {
        return $this->belongsTo('App\Models\Section', 'section_id');
    }

    // علاقة الحصة بالمادة الدراسية
    public function subject()
    {
        return $this->belongsTo('App\Models\Subject', 'subject_id');
    }

    // علاقة الحصة بالمعلم
    public function teacher()
    {
        return $this->belongsTo('App\Models\Teacher', 'teacher_id');
    }

    // علاقة الحصة بالسيناريو
    public function scenario()
    {
        return $this->belongsTo('App\Models\TimetableScenario', 'scenario_id');
    }
}