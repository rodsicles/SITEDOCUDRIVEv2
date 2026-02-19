<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $primaryKey = 'leave_id';

    protected $fillable = [
        'user_id',
        'leave_type',
        'start_date',
        'end_date',
        'days_count',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Check if leave is pending
    public function isPending()
    {
        return $this->status === 'Pending';
    }

    // Check if leave is approved
    public function isApproved()
    {
        return $this->status === 'Approved';
    }

    // Check if leave is rejected
    public function isRejected()
    {
        return $this->status === 'Rejected';
    }

    // Get leave duration in days
    public function getDuration()
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    // Check if leave is currently active
    public function isActive()
    {
        return $this->isApproved() && 
               now()->between($this->start_date, $this->end_date);
    }
}
