<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SectionSubjectTeacher extends Model
{
    protected $table = 'section_subject_teacher';

    protected $fillable = [
        'section_id',
        'subject_id',
        'teacher_id',
    ];

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }
}
