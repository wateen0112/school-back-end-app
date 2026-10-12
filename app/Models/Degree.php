<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Degree extends Model
{
    protected $guarded = [];
    public $timestamps = true;

    protected $casts = [
        'score' => 'float',
        'abuse' => 'string',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'time_spent_seconds' => 'integer',
        'status' => 'string',
    ];

    public function student()
    {
        return $this->belongsTo('App\Models\Student', 'student_id');
    }

    public function quizze()
    {
        return $this->belongsTo('App\Models\Quizze', 'quizze_id');
    }

    public function question()
    {
        return $this->belongsTo('App\Models\Question', 'question_id');
    }
}
