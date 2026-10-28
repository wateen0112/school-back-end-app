<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Translatable\HasTranslations;
use Laravel\Sanctum\HasApiTokens;

class Student extends Authenticatable
{
    use SoftDeletes, HasTranslations, HasApiTokens, Concerns\Auditable;
    public $translatable = ['name'];
    protected $guarded =[];
    protected $hidden = ['password', 'remember_token'];

    // --- RBAC ---

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_student', 'student_id', 'role_id');
    }

    public function effectivePermissions(): array
    {
        $roles = $this->roles()->where('is_active', true)->get();

        if ($roles->isEmpty()) {
            return ['*'];
        }

        $permissions = [];
        foreach ($roles as $role) {
            foreach (array_keys($role->getActivePermissions()) as $key) {
                $permissions[$key] = true;
            }
        }

        return array_keys($permissions);
    }

    public function hasPermission(string $key): bool
    {
        $permissions = $this->effectivePermissions();

        return in_array('*', $permissions, true) || in_array($key, $permissions, true);
    }

    // O1U,OU,Oc O"USU+ OU,O�U,OO" U^OOU+U^OO1 U,O�U,O" OO3U. OU,U+U^O1 U?US O�O_U^U, OU,O�U,OO"

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

    // Fee discounts granted to this student (cash, siblings, excellence, ...)
    public function discounts()
    {
        return $this->hasMany('App\Models\StudentDiscount', 'student_id');
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

    // The academic year this student is enrolled in.
    public function academicYear()
    {
        return $this->belongsTo('App\Models\AcademicYear', 'academic_year_id');
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
