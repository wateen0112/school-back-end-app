<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class School extends Model
{
    use HasTranslations, SoftDeletes, Concerns\Auditable;

    public $translatable = ['name'];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'governorate',
        'principal_name',
        'status',
        'logo',
        'theme',
        'suspended_at',
        'archived_at',
    ];

    protected $casts = [
        'suspended_at' => 'datetime',
        'archived_at' => 'datetime',
        'theme' => 'array',
    ];

    public const THEME_DEFAULTS = [
        'primary' => '#0088CC',
        'primary_dark' => '#1E2942',
        'accent' => '#0088CC',
    ];

    /**
     * The color theme keys the dashboard reads to build its palette.
     */
    public const THEME_KEYS = ['primary', 'primary_dark', 'accent'];

    /**
     * Normalize a raw theme payload (array or JSON string) into a clean
     * map that only contains valid hex colors. Missing keys fall back to
     * the design-system defaults.
     */
    public static function normalizeTheme($theme): array
    {
        $data = is_string($theme)
            ? json_decode($theme, true)
            : $theme;

        if (! is_array($data) || empty($data)) {
            return self::THEME_DEFAULTS;
        }

        $normalized = [];

        foreach (self::THEME_KEYS as $key) {
            $value = $data[$key] ?? self::THEME_DEFAULTS[$key];
            $normalized[$key] = self::validHex($value) ? strtoupper($value) : self::THEME_DEFAULTS[$key];
        }

        return $normalized;
    }

    /**
     * The merged theme for this school (custom values over defaults).
     */
    public function getThemeAttribute()
    {
        return self::normalizeTheme($this->attributes['theme'] ?? null);
    }

    /**
     * Absolute URL for the uploaded school logo, or null.
     */
    public function logoUrl()
    {
        return $this->logo
            ? asset('attachments/schools/'.ltrim($this->logo, '/'))
            : null;
    }

    public static function validHex(?string $value): bool
    {
        return is_string($value) && preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value) === 1;
    }

    /**
     * The admin user that belongs to this school.
     */
    public function admin()
    {
        return $this->hasOne(User::class, 'school_id');
    }

    public function branchAdmins()
    {
        return $this->hasMany(User::class, 'school_id');
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }

    public function branches()
    {
        return $this->hasMany(SchoolBranch::class);
    }

    public function academicYears()
    {
        return $this->hasMany(AcademicYear::class);
    }

    public function holidays()
    {
        return $this->hasMany(Holiday::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('suspended_at')->whereNull('archived_at')
            ->whereNull('deleted_at');
    }

    public function scopeSuspended($query)
    {
        return $query->whereNotNull('suspended_at');
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }
}