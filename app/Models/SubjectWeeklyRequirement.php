<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectWeeklyRequirement extends Model
{
    protected $table = 'subject_weekly_requirements';

    protected $fillable = [
        'subject_id',
        'grade_id',
        'classroom_id',
        'periods_per_week',
        'total_periods_per_year',
        'min_periods_per_day',
        'max_periods_per_day',
        'allow_consecutive',
        'notes',
    ];

    protected $casts = [
        'periods_per_week'  => 'integer',
        'total_periods_per_year' => 'integer',
        'min_periods_per_day' => 'integer',
        'max_periods_per_day' => 'integer',
        'allow_consecutive' => 'boolean',
    ];

    public function subject()
    {
        return $this->belongsTo('App\Models\Subject', 'subject_id');
    }

    public function grade()
    {
        return $this->belongsTo('App\Models\Grade', 'grade_id');
    }

    public function classroom()
    {
        return $this->belongsTo('App\Models\Classroom', 'classroom_id');
    }

    public function scopeForGrade($query, $gradeId)
    {
        return $query->where('grade_id', $gradeId);
    }

    public function scopeForClassroom($query, $classroomId)
    {
        return $query->where('classroom_id', $classroomId);
    }

    public function scopeForSubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }
}
