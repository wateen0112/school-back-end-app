<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Announcement extends Model
{
    use HasTranslations;

    public $translatable = ['title', 'body'];

    protected $fillable = [
        'title',
        'body',
        'audience',
        'priority',
        'is_active',
        'starts_at',
        'ends_at',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }
}