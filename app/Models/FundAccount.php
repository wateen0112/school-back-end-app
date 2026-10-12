<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FundAccount extends Model
{
    protected $fillable = [
        'date',
        'receipt_id',
        'payment_id',
        'expense_id',
        'salary_payment_id',
        'Debit',
        'credit',
        'description',
    ];
}