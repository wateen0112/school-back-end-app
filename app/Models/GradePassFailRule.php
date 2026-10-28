<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * قاعدة نجاح/رسوب خاصة بصف معين (Grade) وليست عامة:
 * - single_subject: مادة مرسبة عند عدم استيفاء الحد الأدنى فيها.
 * - combined_subjects: مادتان تحتسبان كمجموع، ويرسب الطالب إذا لم يحقق
 *   النسبة المئوية الدنيا من مجموع علامتيهما.
 */
class GradePassFailRule extends Model
{
    protected $fillable = [
        'grade_id',
        'classroom_id',
        'rule_type',
        'subject_id',
        'second_subject_id',
        'subject_min_mark',
        'combined_min_percentage',
        'excluded_subject_ids',
        'is_active',
    ];

    protected $casts = [
        'subject_min_mark' => 'decimal:2',
        'combined_min_percentage' => 'decimal:2',
        'excluded_subject_ids' => 'array',
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

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function secondSubject()
    {
        return $this->belongsTo(Subject::class, 'second_subject_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
