<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentRewardPenalty extends Model
{
    protected $table = 'student_reward_penalties';

    protected $fillable = [
        'student_id',
        'school_id',
        'type',
        'source',
        'title',
        'reason',
        'points',
        'amount',
        'progress_score_snapshot',
        'period_month',
        'status',
        'severity',
        'meta',
        'created_by',
        'approved_by',
        'awarded_at',
    ];

    protected $casts = [
        'points' => 'float',
        'amount' => 'float',
        'progress_score_snapshot' => 'float',
        'severity' => 'integer',
        'meta' => 'array',
        'awarded_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(\App\User::class, 'approved_by');
    }
}
