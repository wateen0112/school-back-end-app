<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Subject extends Model
{
    use HasTranslations;

    public $translatable = ['Name'];

    protected $fillable = ['Name','Grade_id','Classroom_id','teacher_id'];


    // جلب اسم المراحل الدراسية

    public function grade()
    {
        return $this->belongsTo('App\Models\Grade', 'grade_id');
    }

    // جلب اسم الصفوف الدراسية
    public function classroom()
    {
        return $this->belongsTo('App\Models\Classroom', 'classroom_id');
    }

    // جلب اسم المعلم
    public function teacher()
    {
        return $this->belongsTo('App\Models\Teacher', 'teacher_id');
    }

    // الأقسام التي تدرس فيها هذه المادة مع معلميها
    public function sectionTeachers()
    {
        return $this->hasMany(SectionSubjectTeacher::class, 'subject_id');
    }

    public function sections()
    {
        return $this->belongsToMany(Section::class, 'section_subject_teacher')
            ->withPivot('teacher_id')
            ->withTimestamps();
    }

    /**
     * Resolve the teacher for this subject in a specific section.
     * Falls back to the legacy subject.teacher_id column.
     */
    public function teacherForSection(int $sectionId): ?Teacher
    {
        $pivot = $this->sectionTeachers()
            ->where('section_id', $sectionId)
            ->with('teacher')
            ->first();

        if ($pivot && $pivot->teacher_id) {
            return $pivot->teacher;
        }

        return $this->teacher;
    }
}
