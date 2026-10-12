<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Holiday extends Model
{
    use HasTranslations;

    public $translatable = ['name'];

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'name',
        'date',
        'type',
        'affects_schedule',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'affects_schedule' => 'boolean',
    ];

    public const TYPE_HOLIDAY = 'holiday';
    public const TYPE_EXAM = 'exam';
    public const TYPE_CLOSURE = 'closure';
    public const TYPE_SPECIAL = 'special';

    public static function types(): array
    {
        return [self::TYPE_HOLIDAY, self::TYPE_EXAM, self::TYPE_CLOSURE, self::TYPE_SPECIAL];
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }
}