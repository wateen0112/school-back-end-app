<?php

namespace App\Models;

use App\Models\Concerns\HasInvoiceNumber;
use Illuminate\Database\Eloquent\Model;

class Fee_invoice extends Model
{
    use HasInvoiceNumber, Concerns\Auditable;

    public static $invoicePrefix = 'INV';

    public function grade()
    {
        return $this->belongsTo('App\Models\Grade', 'Grade_id');
    }


    public function classroom()
    {
        return $this->belongsTo('App\Models\Classroom', 'Classroom_id');
    }


    public function section()
    {
        return $this->belongsTo('App\Models\Section', 'section_id');
    }

    public function student()
    {
        return $this->belongsTo('App\Models\Student', 'student_id');
    }

    public function fees()
    {
        return $this->belongsTo('App\Models\Fee', 'fee_id');
    }
}
