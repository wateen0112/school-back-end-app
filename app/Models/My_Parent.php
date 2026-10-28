<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Translatable\HasTranslations;
use Laravel\Sanctum\HasApiTokens;

class My_Parent extends Authenticatable
{
    use HasTranslations, HasApiTokens, Concerns\Auditable;
    public $translatable = ['Name_Father','Job_Father','Name_Mother','Job_Mother','Nationality_Father','Nationality_Mother'];
    protected $table = 'my__parents';
    protected $guarded=[];
    public $hidden = ['password'];

    // --- RBAC ---

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_parent', 'parent_id', 'role_id');
    }

    public function effectivePermissions(): array
    {
        $roles = $this->roles()->where('is_active', true)->get();

        if ($roles->isEmpty()) {
            return ['*'];
        }

        $permissions = [];
        foreach ($roles as $role) {
            foreach (array_keys($role->getActivePermissions()) as $key) {
                $permissions[$key] = true;
            }
        }

        return array_keys($permissions);
    }

    public function hasPermission(string $key): bool
    {
        $permissions = $this->effectivePermissions();

        return in_array('*', $permissions, true) || in_array($key, $permissions, true);
    }

    /**
     * Get the students associated with the parent.
     */
    public function students()
    {
        return $this->hasMany(Student::class, 'parent_id');
    }
}
