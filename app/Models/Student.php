<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Translatable\HasTranslations;
use Laravel\Sanctum\HasApiTokens;

class Student extends Authenticatable
{
    use SoftDeletes, HasTranslations, HasApiTokens;
    public $translatable = ['name'];
    protected $guarded =[];
    protected $hidden = ['password', 'remember_token'];

    // علاقة بين الطلاب والانواع لجلب اسم النوع في جدول الطلاب

    public function gender()
    {
        return $this->belongsTo('App\Models\Gender', 'gender_id');
    }

    // علاقة بين الطلاب والمراحل الدراسية لجلب اسم المرحلة في جدول الطلاب

    public function grade()
    {
        return $this->belongsTo('App\Models\Grade', 'Grade_id');
    }


    // علاقة بين الطلاب الصفوف الدراسية لجلب اسم الصف في جدول الطلاب

    public function classroom()
    {
        return $this->belongsTo('App\Models\Classroom', 'Classroom_id');
    }

    // علاقة بين الطلاب الاقسام الدراسية لجلب اسم القسم  في جدول الطلاب

    public function section()
    {
        return $this->belongsTo('App\Models\Section', 'section_id');
    }


    // علاقة بين الطلاب والصور لجلب اسم الصور  في جدول الطلاب
    public function images()
    {
        return $this->morphMany('App\Models\Image', 'imageable');
    }

    // علاقة بين الطلاب والجنسيات  لجلب اسم الجنسية  في جدول الجنسيات

    public function Nationality()
    {
        return $this->belongsTo('App\Models\Nationalitie', 'nationalitie_id');
    }


    // علاقة بين الطلاب والاباء لجلب اسم الاب في جدول الاباء

    public function myparent()
    {
        return $this->belongsTo('App\Models\My_Parent', 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo('App\Models\My_Parent', 'parent_id');
    }

    // Transportation extension profile (one-to-one, transportation module only)
    public function transportProfile()
    {
        return $this->hasOne(\App\Models\Transportation\StudentTransportProfile::class, 'student_id');
    }

    // علاقة بين جدول سدادت الطلاب وجدول الطلاب لجلب اجمالي المدفوعات والمتبقي
    public function student_account()
    {
        return $this->hasMany('App\Models\StudentAccount', 'student_id');
    }

   // علاقة بين جدول الطلاب وجدول الحضور والغياب
    public function attendance()
    {
        return $this->hasMany('App\Models\Attendance', 'student_id');
    }

    public function studentInstallments()
    {
        return $this->hasMany('App\Models\StudentInstallment', 'student_id');
    }

    public function degrees()
    {
        return $this->hasMany('App\Models\Degree', 'student_id');
    }

    public function studentAnswers()
    {
        return $this->hasMany('App\Models\StudentAnswer', 'student_id');
    }

    public function paperExamMarks()
    {
        return $this->hasMany('App\Models\PaperExamMark', 'student_id');
    }

}
