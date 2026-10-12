<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    protected $fillable = [
        'student_id',
        'teacher_id',
        'subject_id',
        'grade_id',
        'classroom_id',
        'section_id',
        'evaluation_level',
        'notes',
        'month',
        'year',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
    ];

    // Relationships
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    // Helper to get level label
    public function getLevelLabelAttribute()
    {
        $labels = [
            'bad' => __('Bad'),
            'good' => __('Good'),
            'very_good' => __('Very Good'),
            'excellent' => __('Excellent'),
        ];
        return $labels[$this->evaluation_level] ?? $this->evaluation_level;
    }

    // Helper to get level numeric value for sorting/reporting
    public function getLevelValueAttribute()
    {
        $values = [
            'bad' => 1,
            'good' => 2,
            'very_good' => 3,
            'excellent' => 4,
        ];
        return $values[$this->evaluation_level] ?? 0;
    }
}
