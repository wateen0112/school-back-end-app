<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class SalaryPayment extends Model
{
    protected $fillable = [
        'employee_type',
        'employee_id',
        'period_year',
        'period_month',
        'amount',
        'due_date',
        'paid_at',
        'status',
        'payment_method',
        'notes',
        'paid_by',
        'source',
        'installment_id',
    ];

    protected $casts = [
        'due_date' => 'date:Y-m-d',
        'paid_at' => 'date:Y-m-d',
    ];

    /** Employee: teacher (teachers) or staff (users). */
    public function employee()
    {
        if ($this->employee_type === 'teacher') {
            return $this->belongsTo(Teacher::class, 'employee_id');
        }

        return $this->belongsTo(User::class, 'employee_id');
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function installment()
    {
        return $this->belongsTo(TeacherCustomInstallment::class, 'installment_id');
    }

    public function fundEntry()
    {
        return $this->hasOne(FundAccount::class, 'salary_payment_id');
    }

    /** Mutator-friendly accessor used by API resources. */
    public function getEmployeeNameAttribute()
    {
        $employee = $this->employee;
        if (! $employee) {
            return null;
        }

        return $this->employee_type === 'teacher'
            ? $employee->Name
            : $employee->name;
    }
}
