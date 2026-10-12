<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaperExamMark extends Model
{
    protected $guarded = [];

    protected $casts = [
        'marks_obtained' => 'decimal:2',
    ];

    public function paperExam()
    {
        return $this->belongsTo(PaperExam::class, 'paper_exam_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
