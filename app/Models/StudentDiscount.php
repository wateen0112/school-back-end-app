<?php

namespace App\Models;

use App\Models\Concerns\HasInvoiceNumber;
use Illuminate\Database\Eloquent\Model;

class StudentDiscount extends Model
{
    use HasInvoiceNumber;

    public static $invoicePrefix = 'DSC';

    public const TYPE_CASH = 'cash';
    public const TYPE_SIBLINGS = 'siblings';
    public const TYPE_EXCELLENCE = 'excellence';
    public const TYPE_TEACHER_PARENT = 'teacher_parent';
    public const TYPE_SPECIAL = 'special';

    public const TYPES = [
        self::TYPE_CASH,
        self::TYPE_SIBLINGS,
        self::TYPE_EXCELLENCE,
        self::TYPE_TEACHER_PARENT,
        self::TYPE_SPECIAL,
    ];

    /**
     * Localized labels for the discount types.
     */
    public const TYPE_LABELS = [
        self::TYPE_CASH => ['ar' => 'خصم نقدي', 'en' => 'Cash discount'],
        self::TYPE_SIBLINGS => ['ar' => 'خصم الإخوة', 'en' => 'Siblings discount'],
        self::TYPE_EXCELLENCE => ['ar' => 'خصم التفوق', 'en' => 'Excellence discount'],
        self::TYPE_TEACHER_PARENT => ['ar' => 'خصم أبناء المعلمين', 'en' => "Teacher's child discount"],
        self::TYPE_SPECIAL => ['ar' => 'حالات خاصة', 'en' => 'Special cases'],
    ];

    protected $fillable = [
        'student_id',
        'type',
        'value',
        'is_percentage',
        'reason',
        'year',
        'years',
        'active',
    ];

    protected $casts = [
        'value' => 'float',
        'is_percentage' => 'boolean',
        'active' => 'boolean',
        'years' => 'array',
    ];

    public function student()
    {
        return $this->belongsTo('App\Models\Student', 'student_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
