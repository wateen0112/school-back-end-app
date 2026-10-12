<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class SchoolBranch extends Model
{
    use HasTranslations;

    public $translatable = ['name'];

    protected $fillable = [
        'school_id',
        'name',
        'address',
        'city',
        'phone',
        'email',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}