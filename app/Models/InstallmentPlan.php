<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class InstallmentPlan extends Model
{
    use SoftDeletes, HasTranslations;

    public $translatable = ['title', 'description'];

    protected $fillable = [
        'title',
        'description',
        'grade_id',
        'classroom_id',
        'fee_id',
        'total_amount',
        'academic_year',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'total_amount' => 'float',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function grade()
    {
        return $this->belongsTo('App\Models\Grade', 'grade_id');
    }

    public function classroom()
    {
        return $this->belongsTo('App\Models\Classroom', 'classroom_id');
    }

    public function fee()
    {
        return $this->belongsTo('App\Models\Fee', 'fee_id');
    }

    public function installments()
    {
        return $this->hasMany('App\Models\Installment', 'installment_plan_id')->orderBy('sort_order');
    }

    public function studentInstallments()
    {
        return $this->hasMany('App\Models\StudentInstallment', 'installment_plan_id');
    }
}
