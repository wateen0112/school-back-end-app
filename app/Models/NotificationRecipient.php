<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationRecipient extends Model
{
    protected $fillable = [
        'notification_id',
        'recipient_id',
        'recipient_type',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function notification()
    {
        return $this->belongsTo('App\Models\Notification', 'notification_id');
    }

    public function recipient()
    {
        return $this->morphTo();
    }
}
