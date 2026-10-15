<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * A semester/term inside an academic year. Two terms (TERM_1, TERM_2) are
 * auto-generated when a year is activated.
 */
class AcademicTerm extends Model
{
    use HasTranslations;

    public $translatable = ['name'];

    protected $fillable = [
        'academic_year_id',
        'name',
        'term_code',
        'start_date',
        'end_date',
        'status',
        'is_current',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'is_current' => 'boolean',
    ];

    public const CODE_TERM_1 = 'TERM_1';
    public const CODE_TERM_2 = 'TERM_2';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';

    public static function codes(): array
    {
        return [self::CODE_TERM_1, self::CODE_TERM_2];
    }

    public static function defaultName(string $code, string $locale): string
    {
        if ($locale === 'ar') {
            return $code === self::CODE_TERM_1 ? 'الفصل الدراسي الأول' : 'الفصل الدراسي الثاني';
        }

        return $code === self::CODE_TERM_1 ? 'First Term' : 'Second Term';
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
