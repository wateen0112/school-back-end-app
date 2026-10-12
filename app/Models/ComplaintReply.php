<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplaintReply extends Model
{
    protected $guarded = [];

    public function complaint()
    {
        return $this->belongsTo(Complaint::class, 'complaint_id');
    }

    public function user()
    {
        return $this->morphTo();
    }

    public function attachments()
    {
        return $this->hasMany(ComplaintAttachment::class, 'complaint_reply_id');
    }
}
