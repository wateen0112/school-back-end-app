<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Classroom extends Model
{

    use HasTranslations;
    public $translatable = ['Name_Class'];


    protected $table = 'Classrooms';
    public $timestamps = true;
    protected $fillable=['Name_Class','Grade_id','capacity'];


    // علاقة بين الصفوف المراحل الدراسية لجلب اسم المرحلة في جدول الصفوف

    public function grade()
    {
        return $this->belongsTo('App\Models\Grade', 'Grade_id');
    }

    public function students()
    {
        return $this->hasMany('App\Models\Student', 'Classroom_id');
    }

}
