<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Logged teaching/work hours for hourly-contract teachers.
 * Monthly payroll for those teachers = Σ hours in the period × teacher.hour_rate.
 */
class TeacherWorkHour extends Model
{
    protected $fillable = [
        'teacher_id',
        'work_date',
        'start_time',
        'end_time',
        'hours',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'work_date' => 'date:Y-m-d',
        'hours' => 'decimal:2',
    ];

    /** "H:i" start of the worked window (null for legacy manual entries). */
    public function getStartTimeAttribute($value)
    {
        return $value ? substr($value, 0, 5) : null;
    }

    /** "H:i" end of the worked window (null for legacy manual entries). */
    public function getEndTimeAttribute($value)
    {
        return $value ? substr($value, 0, 5) : null;
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
