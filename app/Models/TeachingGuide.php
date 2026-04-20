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

    public function scopeForUser($query, User $user)
    {
        // Faculty sees ALL teaching guides (they are the recipients)
        // Dean, Secretary, Coordinator see all
        return $query;
    }
}
