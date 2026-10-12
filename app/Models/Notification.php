<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'title',
        'body',
        'type',
        'sender_id',
        'sender_type',
        'target_role',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Resolve a stored title/body value for the current app locale.
     * Values written by older code are plain strings; newer code stores
     * bilingual JSON: {"en": "...", "ar": "..."}.
     */
    public static function localizeText($value): string
    {
        if (is_string($value) && str_starts_with(trim($value), '{')) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $ar = $decoded['ar'] ?? null;
                $en = $decoded['en'] ?? null;
                if (app()->getLocale() === 'ar') {
                    return $ar ?: $en ?: $value;
                }
                return $en ?: $ar ?: $value;
            }
        }

        return (string) $value;
    }

    public function sender()
    {
        return $this->morphTo();
    }

    public function recipients()
    {
        return $this->hasMany('App\Models\NotificationRecipient', 'notification_id');
    }

    public function readRecipients()
    {
        return $this->hasMany('App\Models\NotificationRecipient', 'notification_id')
                    ->where('is_read', true);
    }
}
