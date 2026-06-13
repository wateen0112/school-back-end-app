<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Translatable\HasTranslations;
use Laravel\Sanctum\HasApiTokens;

class My_Parent extends Authenticatable
{
    use HasTranslations, HasApiTokens;
    public $translatable = ['Name_Father','Job_Father','Name_Mother','Job_Mother','Nationality_Father','Nationality_Mother'];
    protected $table = 'my__parents';
    protected $guarded=[];
    public $hidden = ['password'];

    /**
     * Get the students associated with the parent.
     */
    public function students()
    {
        return $this->hasMany(Student::class, 'parent_id');
    }
}
