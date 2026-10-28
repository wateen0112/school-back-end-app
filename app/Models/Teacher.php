<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Translatable\HasTranslations;

use Laravel\Sanctum\HasApiTokens;

class Teacher extends  Authenticatable
{
    use HasTranslations, HasApiTokens, Concerns\Auditable;
    public $translatable = ['Name'];
    
    protected $hidden=['password'];

    protected $casts = [
        'salary_type' => 'string',
        'hour_rate' => 'decimal:2',
        'custom_salary_amount' => 'decimal:2',
    ];

    public const SALARY_MONTHLY = 'monthly';
    public const SALARY_HOURLY = 'hourly';
    public const SALARY_CUSTOM = 'custom';
    // علاقة بين المعلمين والتخصصات لجلب اسم التخصص
    public function specializations()
    {
        return $this->belongsTo('App\Models\Specialization', 'Specialization_id');
    }

    // علاقة بين المعلمين والانواع لجلب جنس المعلم
    public function genders()
    {
        return $this->belongsTo('App\Models\Gender', 'Gender_id');
    }

// علاقة المعلمين مع الاقسام
    public function Sections()
    {
        return $this->belongsToMany('App\Models\Section','teacher_section');
    }

    // المواد التي يدرسها المعلم في الأقسام
    public function sectionSubjects()
    {
        return $this->hasMany(SectionSubjectTeacher::class, 'teacher_id');
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'section_subject_teacher')
            ->withPivot('section_id')
            ->withTimestamps();
    }

    // الجدول الأسبوعي للمعلم (توفر / تفضيل)
    public function weeklySchedules()
    {
        return $this->hasMany('App\Models\TeacherWeeklySchedule', 'teacher_id');
    }

    public function preference()
    {
        return $this->hasOne('App\Models\TeacherPreference', 'teacher_id');
    }

    // علاقة المعلمين بالأدوار (منظومة الصلاحيات)
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_teacher', 'teacher_id', 'role_id');
    }

    /**
     * Super admins → ['*'].
     * No roles yet → ['*'] (RBAC opt-in: full access until restricted).
     * Otherwise the union of all active roles' permission keys.
     */
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

    /** Non-fixed installments of a custom salary contract. */
    public function customInstallments()
    {
        return $this->hasMany(TeacherCustomInstallment::class, 'teacher_id')->orderBy('due_date');
    }

    /** Logged work hours (hourly contracts). */
    public function workHours()
    {
        return $this->hasMany(TeacherWorkHour::class, 'teacher_id')->orderByDesc('work_date');
    }

    /** Monthly payroll entries for this teacher. */
    public function salaryPayments()
    {
        return $this->hasMany(SalaryPayment::class, 'employee_id')
            ->where('employee_type', 'teacher');
    }

}
