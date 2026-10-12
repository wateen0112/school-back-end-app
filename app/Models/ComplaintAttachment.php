<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplaintAttachment extends Model
{
    protected $guarded = [];

    public function complaint()
    {
        return $this->belongsTo(Complaint::class, 'complaint_id');
    }

    public function reply()
    {
        return $this->belongsTo(ComplaintReply::class, 'complaint_reply_id');
    }
}
