<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaperExam extends Model
{
    // أنواع الاختبارات الورقية
    public const TYPE_WRITTEN_REVIEW_1 = 'written_review_1'; // مذاكرة كتابية أولى
    public const TYPE_WRITTEN_REVIEW_2 = 'written_review_2'; // مذاكرة كتابية ثانية
    public const TYPE_ORAL = 'oral';                         // اختبار شفهي
    public const TYPE_COURSEWORK = 'coursework';             // درجة أعمال
    public const TYPE_FINAL_EXAM = 'final_exam';             // امتحان

    public const EXAM_TYPES = [
        self::TYPE_WRITTEN_REVIEW_1,
        self::TYPE_WRITTEN_REVIEW_2,
        self::TYPE_ORAL,
        self::TYPE_COURSEWORK,
        self::TYPE_FINAL_EXAM,
    ];

    public const EXAM_TYPE_LABELS_AR = [
        self::TYPE_WRITTEN_REVIEW_1 => 'مذاكرة كتابية أولى',
        self::TYPE_WRITTEN_REVIEW_2 => 'مذاكرة كتابية ثانية',
        self::TYPE_ORAL => 'اختبار شفهي',
        self::TYPE_COURSEWORK => 'درجة أعمال',
        self::TYPE_FINAL_EXAM => 'امتحان',
    ];

    protected $guarded = [];

    protected $casts = [
        'exam_date' => 'date:Y-m-d',
        'max_score' => 'decimal:2',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function marks()
    {
        return $this->hasMany(PaperExamMark::class, 'paper_exam_id');
    }

    public function students()
    {
        return $this->hasManyThrough(
            Student::class,
            PaperExamMark::class,
            'paper_exam_id',
            'id',
            'id',
            'student_id'
        );
    }
}
