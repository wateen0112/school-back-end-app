<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimetableScenario extends Model
{
    protected $table = 'timetable_scenarios';

    protected $fillable = [
        'name',
        'description',
        'grade_id',
        'classroom_id',
        'section_id',
        'version',
        'status',
        'is_active',
        'locked_slots',
        'metadata',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'locked_slots' => 'array',
        'metadata'     => 'array',
    ];

    public function grade()
    {
        return $this->belongsTo('App\Models\Grade', 'grade_id');
    }

    public function classroom()
    {
        return $this->belongsTo('App\Models\Classroom', 'classroom_id');
    }

    public function section()
    {
        return $this->belongsTo('App\Models\Section', 'section_id');
    }

    public function programs()
    {
        return $this->hasMany('App\Models\WeeklyProgram', 'scenario_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeForSection($query, $sectionId)
    {
        return $query->where('section_id', $sectionId);
    }

    public function scopeForGrade($query, $gradeId)
    {
        return $query->where('grade_id', $gradeId);
    }
}
