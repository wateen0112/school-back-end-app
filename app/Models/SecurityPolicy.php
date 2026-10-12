<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityPolicy extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'config',
        'is_enabled',
    ];

    protected $casts = [
        'config' => 'array',
        'is_enabled' => 'boolean',
    ];
}