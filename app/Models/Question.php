<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $guarded = [];

    public function quizze()
    {
        return $this->belongsTo('App\Models\Quizze');
    }

    public function studentAnswers()
    {
        return $this->hasMany('App\Models\StudentAnswer');
    }
}
