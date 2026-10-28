<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use Concerns\HasInvoiceNumber, Concerns\Auditable;

    public static $invoicePrefix = 'EXP';

    protected $fillable = [
        'category_id',
        'title',
        'amount',
        'expense_date',
        'reference_no',
        'invoice_number',
        'description',
        'created_by',
    ];

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fundEntry()
    {
        return $this->hasOne(FundAccount::class, 'expense_id');
    }
}
