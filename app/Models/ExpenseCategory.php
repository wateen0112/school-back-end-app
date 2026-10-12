<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    protected $fillable = ['name', 'icon'];

    protected $casts = [
        'name' => 'array',
    ];

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'category_id');
    }
}
