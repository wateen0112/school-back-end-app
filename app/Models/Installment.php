<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Installment extends Model
{
    protected $fillable = [
        'installment_plan_id',
        'title',
        'amount',
        'due_date',
        'sort_order',
        'description',
    ];

    protected $casts = [
        'amount' => 'float',
        'due_date' => 'date',
        'sort_order' => 'integer',
    ];

    public function installmentPlan()
    {
        return $this->belongsTo('App\Models\InstallmentPlan', 'installment_plan_id');
    }

    public function studentInstallments()
    {
        return $this->hasMany('App\Models\StudentInstallment', 'installment_id');
    }
}
