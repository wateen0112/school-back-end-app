<?php

namespace App\Models;

use App\Models\Concerns\HasInvoiceNumber;
use Illuminate\Database\Eloquent\Model;

class ReceiptStudent extends Model
{
    use HasInvoiceNumber, Concerns\Auditable;

    public static $invoicePrefix = 'RCP';
    public function student()
    {
        return $this->belongsTo('App\Models\Student', 'student_id');
    }

    public function studentInstallment()
    {
        return $this->belongsTo('App\Models\StudentInstallment', 'student_installment_id');
    }
}
