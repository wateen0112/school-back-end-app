<?php

namespace App;

use App\Models\School;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'school_id', 'is_super_admin', 'salary',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_super_admin' => 'boolean',
    ];

    /**
     * The school this admin account belongs to (null for the super admin).
     */
    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    /**
     * The roles assigned to this admin (a user can hold multiple roles).
     */
    public function roles()
    {
        return $this->belongsToMany(\App\Models\Role::class, 'role_user', 'user_id', 'role_id');
    }

    /** Monthly payroll entries for this staff member (users table). */
    public function salaryPayments()
    {
        return $this->hasMany(\App\Models\SalaryPayment::class, 'employee_id')
            ->where('employee_type', 'staff');
    }

    /**
     * Effective permission keys after merging all active roles.
     * - Super admin always has full access ('*').
     * - Users with no roles yet keep full access (RBAC opt-in) so existing
     *   admins are not locked out until roles are configured.
     */
    public function effectivePermissions(): array
    {
        if ($this->is_super_admin) {
            return ['*'];
        }

        $activeRoles = $this->roles()->where('roles.is_active', true)->get();

        if ($activeRoles->isEmpty()) {
            return ['*'];
        }

        $merged = [];
        foreach ($activeRoles as $role) {
            foreach ($role->getActivePermissions() as $key => $enabled) {
                if ($enabled) {
                    $merged[$key] = true;
                }
            }
        }

        return array_keys($merged);
    }

    /**
     * Determine whether this user holds a permission key (or full access).
     */
    public function hasPermission(string $key): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        $permissions = $this->effectivePermissions();

        if (in_array('*', $permissions, true)) {
            return true;
        }

        return in_array($key, $permissions, true);
    }
}
