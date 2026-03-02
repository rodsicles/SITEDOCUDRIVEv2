<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Folder extends Model
{
    protected $primaryKey = 'folder_id';
    
    protected $fillable = [
        'user_id',
        'folder_name',
        'color',
    ];

    /**
     * Get the user that owns the folder
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all documents in this folder
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'folder_id', 'folder_id');
    }

    /**
     * Get document count for this folder
     */
    public function getDocumentCountAttribute(): int
    {
        return $this->documents()->count();
    }
}
