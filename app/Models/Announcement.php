<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Announcement extends Model
{
    use HasFactory;

    protected $primaryKey = 'announcement_id';

    protected $fillable = [
        'author_id',
        'title',
        'body',
        'is_pinned',
        'visibility',
        'department',
        'expires_at',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function reads()
    {
        return $this->hasMany(AnnouncementRead::class, 'announcement_id', 'announcement_id');
    }

    public function reactions()
    {
        return $this->hasMany(AnnouncementReaction::class, 'announcement_id', 'announcement_id');
    }

    public function isReadBy(User $user): bool
    {
        return $this->reads()->where('user_id', $user->id)->exists();
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeVisibleTo($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('visibility', 'All')
              ->orWhere('visibility', $user->role->role_name);
        })->where(function ($q) use ($user) {
            $q->where('department', 'All');
            if ($user->employee && $user->employee->department) {
                $q->orWhere('department', $user->employee->department);
            }
        });
    }

    public function scopeOrdered($query)
    {
        return $query->orderByDesc('is_pinned')->orderByDesc('created_at');
    }
}
