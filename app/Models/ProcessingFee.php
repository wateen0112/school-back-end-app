<?php

namespace App\Models;

use App\Models\Concerns\HasInvoiceNumber;
use Illuminate\Database\Eloquent\Model;

class ProcessingFee extends Model
{
    use HasInvoiceNumber, Concerns\Auditable;

    public static $invoicePrefix = 'PRC';
    public function student()
    {
        return $this->belongsTo('App\Models\Student', 'student_id');
    }
}
