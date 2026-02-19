<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    use HasFactory;

    protected $primaryKey = 'event_id';

    protected $fillable = [
        'created_by',
        'title',
        'description',
        'event_type',
        'start_datetime',
        'end_datetime',
        'location',
        'all_day',
        'visibility',
        'send_reminder',
        'reminder_minutes',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'all_day' => 'boolean',
        'send_reminder' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendees()
    {
        return $this->hasMany(EventAttendee::class, 'event_id', 'event_id');
    }

    public function acceptedAttendees()
    {
        return $this->attendees()->where('response_status', 'Accepted');
    }

    // Check if user is an attendee
    public function hasAttendee($userId)
    {
        return $this->attendees()->where('user_id', $userId)->exists();
    }

    // Check if event is upcoming
    public function isUpcoming()
    {
        return $this->start_datetime->isFuture();
    }

    // Check if event is currently happening
    public function isOngoing()
    {
        return now()->between($this->start_datetime, $this->end_datetime);
    }

    // Get events for a specific user
    public static function getEventsForUser($userId, $startDate = null, $endDate = null)
    {
        $query = self::where(function($q) use ($userId) {
            $q->where('created_by', $userId)
              ->orWhere('visibility', 'Public')
              ->orWhereHas('attendees', function($q2) use ($userId) {
                  $q2->where('user_id', $userId);
              });
        });

        if ($startDate) {
            $query->where('start_datetime', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('end_datetime', '<=', $endDate);
        }

        return $query->orderBy('start_datetime')->get();
    }
}
