<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Grade extends Model
{

    use HasTranslations;
    public $translatable = ['Name'];

    protected $fillable=['Name','Notes'];
    protected $table = 'Grades';
    public $timestamps = true;

    // علاقة المراحل الدراسية لجلب الاقسام المتعلقة بكل مرحلة
    public function Sections()
    {
        return $this->hasMany('App\Models\Section', 'Grade_id');
    }

    /**
     * Get the classrooms associated with the grade.
     */
    public function classrooms()
    {
        return $this->hasMany('App\Models\Classroom', 'Grade_id');
    }

}