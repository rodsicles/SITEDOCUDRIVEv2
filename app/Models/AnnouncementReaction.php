<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnouncementReaction extends Model
{
    protected $fillable = [
        'announcement_id',
        'user_id',
        'emoji',
    ];

    public function announcement()
    {
        return $this->belongsTo(Announcement::class, 'announcement_id', 'announcement_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
