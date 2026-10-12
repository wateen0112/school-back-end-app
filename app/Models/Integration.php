<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Integration extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'config',
        'is_enabled',
        'school_id',
        'direction',
        'webhook_token',
        'hmac_secret',
        'last_status',
        'last_sync_at',
    ];

    protected $casts = [
        'config' => 'array',
        'is_enabled' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->webhook_token)) {
                $model->webhook_token = Str::random(48);
            }
            if (empty($model->hmac_secret)) {
                $model->hmac_secret = Str::random(64);
            }
        });
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function verifySignature(string $payload, string $signature): bool
    {
        return hash_equals(
            hash_hmac('sha256', $payload, (string) $this->hmac_secret),
            (string) $signature
        );
    }
}