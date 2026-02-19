<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventAttendee extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'response_status',
        'notified',
    ];

    protected $casts = [
        'notified' => 'boolean',
    ];

    public function event()
    {
        return $this->belongsTo(CalendarEvent::class, 'event_id', 'event_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Mark as notified
    public function markNotified()
    {
        $this->notified = true;
        $this->save();
    }

    // Update response status
    public function updateResponse($status)
    {
        $this->response_status = $status;
        $this->save();
    }
}
