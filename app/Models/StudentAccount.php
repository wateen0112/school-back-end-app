<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAccount extends Model
{
    public function feeInvoice()
    {
        return $this->belongsTo('App\Models\Fee_invoice', 'fee_invoice_id');
    }

    public function receipt()
    {
        return $this->belongsTo('App\Models\ReceiptStudent', 'receipt_id');
    }

    public function studentInstallment()
    {
        return $this->belongsTo('App\Models\StudentInstallment', 'student_installment_id');
    }

    public function student()
    {
        return $this->belongsTo('App\Models\Student', 'student_id');
    }
}
