<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $guarded = [];

    protected $casts = [
        'status' => 'string',
        'priority' => 'string',
    ];

    public function complainant()
    {
        return $this->morphTo();
    }

    public function replies()
    {
        return $this->hasMany(ComplaintReply::class, 'complaint_id');
    }

    public function attachments()
    {
        return $this->hasMany(ComplaintAttachment::class, 'complaint_id');
    }

    public function latestReply()
    {
        return $this->hasOne(ComplaintReply::class, 'complaint_id')->latest('created_at');
    }
}
