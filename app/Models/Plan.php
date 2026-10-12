<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Plan extends Model
{
    use HasTranslations;

    public $translatable = ['name'];

    protected $fillable = [
        'name',
        'code',
        'description',
        'price',
        'currency',
        'billing_cycle',
        'features',
        'max_students',
        'max_teachers',
        'max_branches',
        'max_storage_gb',
        'max_api_calls_monthly',
        'ai_enabled',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'float',
        'features' => 'array',
        'ai_enabled' => 'boolean',
        'is_active' => 'boolean',
        'max_students' => 'integer',
        'max_teachers' => 'integer',
        'max_branches' => 'integer',
        'max_storage_gb' => 'integer',
        'max_api_calls_monthly' => 'integer',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}