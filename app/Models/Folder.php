<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\SchoolYear;

class Folder extends Model
{
    protected $primaryKey = 'folder_id';

    protected $fillable = [
        'user_id',
        'folder_name',
        'color',
        'parent_id',
        'is_system',
        'level',
        'sort_order',
        'slug',
        'school_year_id',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * Get the user that owns the folder
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent folder
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_id', 'folder_id');
    }

    /**
     * Get child folders
     */
    public function children(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_id', 'folder_id')->orderBy('sort_order');
    }

    /**
     * Get all documents in this folder
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'folder_id', 'folder_id');
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /**
     * Get document count for this folder
     */
    public function getDocumentCountAttribute(): int
    {
        return $this->documents()->count();
    }

    /**
     * Scope: only system folders
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope: top-level categories only
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Get ancestor chain for breadcrumbs (ordered from root to immediate parent)
     */
    public function getAncestors(): array
    {
        $ancestors = [];
        $current = $this->parent;
        while ($current) {
            array_unshift($ancestors, $current);
            $current = $current->parent;
        }
        return $ancestors;
    }

    /**
     * Get the root system category name for this folder (level-0 ancestor).
     */
    public function getTopLevelCategoryAttribute(): ?string
    {
        $folder = $this;
        while ($folder->parent_id !== null) {
            if (!$folder->relationLoaded('parent')) {
                $folder->load('parent');
            }
            $parent = $folder->parent;
            if (!$parent) {
                break;
            }
            $folder = $parent;
        }

        return $folder->folder_name;
    }

    /**
     * Get all descendant folder IDs (children + grandchildren)
     */
    public function getDescendantIds(): array
    {
        $ids = [];
        foreach ($this->children as $child) {
            $ids[] = $child->folder_id;
            $ids = array_merge($ids, $child->getDescendantIds());
        }
        return $ids;
    }
}
