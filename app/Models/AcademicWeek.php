<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicWeek extends Model
{
    protected $fillable = [
        'academic_year_id',
        'week_number',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'week_number' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}