<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptStudent extends Model
{
    public function student()
    {
        return $this->belongsTo('App\Models\Student', 'student_id');
    }

    public function studentInstallment()
    {
        return $this->belongsTo('App\Models\StudentInstallment', 'student_installment_id');
    }
}
