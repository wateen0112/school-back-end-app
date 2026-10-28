<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * إعدادات حساب المحصلة والجلاء المدرسي لصف معين (Grade):
 * - direct: إدخال المحصلة مباشرة دون تقسيم (الصفوف 1-4).
 * - coursework_exam: درجة أعمال (60 افتراضياً) + درجة امتحان (40).
 */
class GradeTranscriptSetting extends Model
{
    public const MODE_DIRECT = 'direct';
    public const MODE_COURSEWORK_EXAM = 'coursework_exam';

    public const DEFAULT_EXAM_TYPES = ['final_exam'];

    protected $fillable = [
        'grade_id',
        'classroom_id',
        'calculation_mode',
        'coursework_max',
        'exam_max',
        'oral_max',
        'homework_max',
        'quizzes_max',
        'exam_types',
        'pass_percentage',
        'show_grade_level',
        'is_active',
    ];

    protected $casts = [
        'coursework_max' => 'decimal:2',
        'exam_max' => 'decimal:2',
        'oral_max' => 'decimal:2',
        'homework_max' => 'decimal:2',
        'quizzes_max' => 'decimal:2',
        'exam_types' => 'array',
        'pass_percentage' => 'decimal:2',
        'show_grade_level' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function grade()
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
