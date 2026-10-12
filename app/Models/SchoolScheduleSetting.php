<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolScheduleSetting extends Model
{
    protected $table = 'school_schedule_settings';

    protected $fillable = [
        'school_id',
        'days',
        'start_time',
        'end_time',
        'breaks',
    ];

    protected $casts = [
        'days'   => 'array',
        'breaks' => 'array',
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }
}