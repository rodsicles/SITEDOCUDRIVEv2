<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentRequest extends Model
{
    protected $fillable = [
        'requested_by', 'title', 'instructions', 'document_type', 'course_id',
        'department', 'school_year_id', 'semester', 'due_at',
        'allow_late_submission', 'status',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'allow_late_submission' => 'boolean',
    ];

    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }
    public function course() { return $this->belongsTo(Course::class); }
    public function schoolYear() { return $this->belongsTo(SchoolYear::class); }
    public function recipients() { return $this->hasMany(DocumentRequestRecipient::class); }

    public function scopeVisibleTo($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('requested_by', $user->id)
                ->orWhereHas('recipients', fn ($r) => $r->where('user_id', $user->id));
        });
    }
}
