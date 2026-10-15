<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentInstallment extends Model
{
    protected $fillable = [
        'installment_plan_id',
        'installment_id',
        'student_id',
        'fee_invoice_id',
        'amount',
        'paid_amount',
        'remaining_amount',
        'status',
        'due_date',
        'paid_at',
        'reminder_sent_at',
        'overdue_sent_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'paid_amount' => 'float',
        'remaining_amount' => 'float',
        'due_date' => 'date:Y-m-d',
        'paid_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'overdue_sent_at' => 'datetime',
    ];

    public function installmentPlan()
    {
        return $this->belongsTo('App\Models\InstallmentPlan', 'installment_plan_id');
    }

    public function installment()
    {
        return $this->belongsTo('App\Models\Installment', 'installment_id');
    }

    public function student()
    {
        return $this->belongsTo('App\Models\Student', 'student_id');
    }

    public function feeInvoice()
    {
        return $this->belongsTo('App\Models\Fee_invoice', 'fee_invoice_id');
    }

    public function receipts()
    {
        return $this->hasMany('App\Models\ReceiptStudent', 'student_installment_id');
    }

    public function studentAccounts()
    {
        return $this->hasMany('App\Models\StudentAccount', 'student_installment_id');
    }
}
