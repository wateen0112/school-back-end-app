<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'user_type',
        'school_id',
        'action',
        'entity_type',
        'entity_id',
        'description',
        'meta',
        'old_values',
        'new_values',
        'reason',
        'approval_reference',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }
}