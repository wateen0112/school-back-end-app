<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaperExam extends Model
{
    protected $guarded = [];

    protected $casts = [
        'exam_date' => 'date:Y-m-d',
        'max_score' => 'decimal:2',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
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

    public function marks()
    {
        return $this->hasMany(PaperExamMark::class, 'paper_exam_id');
    }

    public function students()
    {
        return $this->hasManyThrough(
            Student::class,
            PaperExamMark::class,
            'paper_exam_id',
            'id',
            'id',
            'student_id'
        );
    }
}
