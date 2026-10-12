<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsageMetric extends Model
{
    protected $fillable = [
        'school_id',
        'metric',
        'value',
        'recorded_on',
    ];

    protected $casts = [
        'recorded_on' => 'date',
    ];
}