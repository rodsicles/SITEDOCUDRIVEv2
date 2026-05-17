<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeachingGuide extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'file_path',
        'file_type',
        'subject',
        'folder_id',
        'document_id',
        'semester',
        'academic_year',
        'description',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function folder()
    {
        return $this->belongsTo(\App\Models\Folder::class, 'folder_id', 'folder_id');
    }

    public function recipients()
    {
        return $this->belongsToMany(User::class, 'teaching_guide_recipients', 'teaching_guide_id', 'user_id')
            ->withTimestamps();
    }

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id', 'document_id');
    }

    public function scopeForUser($query, User $user)
    {
        if ($user->isDean() || $user->isSecretary() || $user->isProgramCoordinator()) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->whereHas('recipients', fn ($r) => $r->where('users.id', $user->id))
                ->orWhereDoesntHave('recipients');
        });
    }
}
