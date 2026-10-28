<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectGradeSetting extends Model
{
    protected $fillable = ['subject_id', 'grade_id', 'classroom_id', 'max_mark', 'fail_mark'];

    protected $casts = [
        'max_mark' => 'decimal:2',
        'fail_mark' => 'decimal:2',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }
}
