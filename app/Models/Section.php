<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Section extends Model
{
    use HasTranslations, Concerns\Auditable;
    public $translatable = ['Name_Section'];
    protected $fillable=['Name_Section','Status','Grade_id','Class_id'];

    protected $table = 'sections';
    public $timestamps = true;


    // علاقة بين الاقسام والصفوف لجلب اسم الصف في جدول الاقسام

    public function My_classs()
    {
        return $this->belongsTo('App\Models\Classroom', 'Class_id');
    }

    // علاقة الاقسام مع المعلمين
    public function teachers()
    {
        return $this->belongsToMany('App\Models\Teacher','teacher_section');
    }

    // المواد والمعلمين المخصصين لهذا القسم
    public function subjectTeachers()
    {
        return $this->hasMany(SectionSubjectTeacher::class, 'section_id');
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'section_subject_teacher')
            ->withPivot('teacher_id')
            ->withTimestamps();
    }

    public function Grades()
    {
        return $this->belongsTo('App\Models\Grade','Grade_id');
    }

}
