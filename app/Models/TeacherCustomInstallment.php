<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One manually-scheduled slice of a teacher's "custom" salary contract.
 * Payroll generation turns each pending installment into a SalaryPayment
 * (source = custom) once, tracked via salary_payments.installment_id.
 */
class TeacherCustomInstallment extends Model
{
    protected $fillable = [
        'teacher_id',
        'title',
        'amount',
        'due_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date:Y-m-d',
    ];

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function salaryPayment()
    {
        return $this->hasOne(SalaryPayment::class, 'installment_id');
    }
}
