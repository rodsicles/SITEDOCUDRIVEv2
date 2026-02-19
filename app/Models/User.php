<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'username',
        'name',
        'email',
        'password',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id');
    }

    public function tasksAssigned()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function tasksCreated()
    {
        return $this->hasMany(Task::class, 'assigned_by');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id', 'notification_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'user_id');
    }

    public function reviewedLeaves()
    {
        return $this->hasMany(LeaveRequest::class, 'reviewed_by');
    }

    public function leaveBalance()
    {
        return $this->hasOne(LeaveBalance::class, 'user_id')->where('year', date('Y'));
    }

    public function calendarEvents()
    {
        return $this->hasMany(CalendarEvent::class, 'created_by');
    }

    public function eventAttendances()
    {
        return $this->hasMany(EventAttendee::class, 'user_id');
    }

    public function documentFavorites()
    {
        return $this->hasMany(DocumentFavorite::class, 'user_id');
    }

    public function documentViews()
    {
        return $this->hasMany(DocumentView::class, 'user_id');
    }

    public function isDean()
    {
        return $this->role->role_name === 'Dean';
    }

    public function isProgramCoordinator()
    {
        return $this->role->role_name === 'Program Coordinator';
    }

    public function isFaculty()
    {
        return $this->role->role_name === 'Faculty Employee';
    }
}
