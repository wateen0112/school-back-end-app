<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherPreference extends Model
{
    protected $table = 'teacher_preferences';

    protected $fillable = [
        'teacher_id',
        'preferred_consecutive',
        'max_consecutive',
        'max_periods_per_day',
        'preferred_start_time',
        'preferred_end_time',
        'avoid_days',
        'notes',
    ];

    protected $casts = [
        'preferred_consecutive' => 'integer',
        'max_consecutive'       => 'integer',
        'max_periods_per_day'   => 'integer',
        'avoid_days'            => 'array',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }
}