<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Role extends Model
{
    use HasTranslations;

    public $translatable = ['name'];

    protected $fillable = [
        'name',
        'description',
        'permissions',
        'is_active',
        'school_id',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function users()
    {
        return $this->belongsToMany(\App\User::class, 'role_user', 'role_id', 'user_id');
    }

    public function getActivePermissions(): array
    {
        if (! $this->is_active) {
            return [];
        }

        $perms = $this->permissions ?? [];
        return array_filter($perms, fn ($v) => $v === true);
    }
}
