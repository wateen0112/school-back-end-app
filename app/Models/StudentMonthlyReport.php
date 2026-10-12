<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentMonthlyReport extends Model
{
    protected $fillable = [
        'student_id',
        'month',
        'year',
        'attendance_total_days',
        'attendance_present',
        'attendance_absent',
        'attendance_rate',
        'evaluations_total',
        'evaluations_average_level',
        'evaluations_breakdown',
        'exam_subjects',
        'exam_overall_percentage',
        'quizzes_total',
        'quiz_average_percentage',
        'progress_score',
        'previous_month_score',
        'trend',
        'teacher_notes',
        'generated_at',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'attendance_total_days' => 'integer',
        'attendance_present' => 'integer',
        'attendance_absent' => 'integer',
        'attendance_rate' => 'float',
        'evaluations_total' => 'integer',
        'evaluations_average_level' => 'float',
        'evaluations_breakdown' => 'array',
        'exam_subjects' => 'array',
        'exam_overall_percentage' => 'float',
        'quizzes_total' => 'integer',
        'quiz_average_percentage' => 'float',
        'progress_score' => 'float',
        'previous_month_score' => 'float',
        'trend' => 'string',
        'teacher_notes' => 'array',
        'generated_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}