<?php

namespace App\Models;

use App\Models\User;
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

    /**
     * Compute the target audience of this announcement (active users that
     * match the visibility/department filters), excluding the author.
     * Returns a query builder you can chain on (or call ->get()).
     */
    public function targetAudienceQuery()
    {
        return User::query()
            ->where('id', '!=', $this->author_id)
            ->where('status', 'Active')
            ->when($this->visibility !== 'All', function ($q) {
                $q->whereHas('role', function ($r) {
                    $r->where('role_name', $this->visibility);
                });
            })
            ->when($this->department && $this->department !== 'All', function ($q) {
                $q->whereHas('employee', function ($e) {
                    $e->where('department', $this->department);
                });
            })
            ->with(['employee:user_id,full_name,department', 'role:role_id,role_name']);
    }
}
