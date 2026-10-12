<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAnswer extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_correct' => 'boolean',
        'score' => 'float',
    ];

    public function quizze()
    {
        return $this->belongsTo('App\Models\Quizze', 'quizze_id');
    }

    public function student()
    {
        return $this->belongsTo('App\Models\Student', 'student_id');
    }

    public function question()
    {
        return $this->belongsTo('App\Models\Question', 'question_id');
    }
}
