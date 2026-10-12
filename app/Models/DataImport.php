<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataImport extends Model
{
    protected $fillable = [
        'school_id',
        'user_id',
        'entity',
        'file_name',
        'original_name',
        'status',
        'total_rows',
        'imported_rows',
        'skipped_rows',
        'errors',
        'report',
    ];

    protected $casts = [
        'errors' => 'array',
        'report' => 'array',
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }
}