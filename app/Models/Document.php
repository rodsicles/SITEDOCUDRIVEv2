<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'document_id';
    
    protected $fillable = [
        'uploaded_by',
        'folder_id',
        'trashed_folder_id',
        'deleted_by',
        'document_title',
        'subject',
        'file_path',
        'file_size',
        'document_type',
        'category',
        'category_id',
        'school_year_id',
        'tags',
    ];

    // Tags are stored as comma-separated string
    public function getTagsArrayAttribute()
    {
        return $this->tags ? explode(',', $this->tags) : [];
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function category()
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id', 'category_id');
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class, 'folder_id', 'folder_id');
    }

    public function trashedFolder()
    {
        return $this->belongsTo(Folder::class, 'trashed_folder_id', 'folder_id');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Human-readable original folder path stored when moved to Recycle Bin.
     */
    public function getRecycleBinFolderLabelAttribute(): string
    {
        if (!$this->trashed_folder_id) {
            return 'Uncategorized';
        }

        $folder = $this->relationLoaded('trashedFolder')
            ? $this->trashedFolder
            : Folder::find($this->trashed_folder_id);

        if (!$folder) {
            return 'Uncategorized';
        }

        $names = array_map(fn (Folder $f) => $f->folder_name, $folder->getAncestors());
        $names[] = $folder->folder_name;

        return implode(' / ', $names);
    }

    public function comments()
    {
        return $this->hasMany(DocumentComment::class, 'document_id', 'document_id');
    }

    public function favorites()
    {
        return $this->hasMany(DocumentFavorite::class, 'document_id', 'document_id');
    }

    public function views()
    {
        return $this->hasMany(DocumentView::class, 'document_id', 'document_id');
    }

    public function recipients()
    {
        return $this->belongsToMany(User::class, 'document_recipients', 'document_id', 'user_id')
            ->withTimestamps();
    }

    public function teachingGuide()
    {
        return $this->hasOne(TeachingGuide::class, 'document_id', 'document_id');
    }

    public const SHAREABLE_CATEGORIES = ['Teaching Guides', 'Exam Questionnaires'];

    // Check if document is favorited by user
    public function isFavoritedBy($userId)
    {
        return $this->favorites()->where('user_id', $userId)->exists();
    }

    // Toggle favorite for user
    public function toggleFavorite($userId)
    {
        $favorite = $this->favorites()->where('user_id', $userId)->first();
        
        if ($favorite) {
            $favorite->delete();
            return false; // Unfavorited
        } else {
            $fav = new DocumentFavorite(['document_id' => $this->document_id]);
            $fav->user_id = $userId;
            $fav->save();
            return true; // Favorited
        }
    }

    /**
     * Check if a user is allowed to view/download this document.
     */
    public function canView(User $user): bool
    {
        if ($user->isDean() || $user->isSecretary()) {
            return true;
        }

        if ((int) $this->uploaded_by === (int) $user->id) {
            return true;
        }

        if ($this->recipients()->where('users.id', $user->id)->exists()) {
            return true;
        }

        if ($user->isProgramCoordinator()) {
            $uploader = $this->relationLoaded('uploader') ? $this->uploader : $this->uploader()->with('employee')->first();

            if ($uploader && ($uploader->isFaculty() || (int) $uploader->role_id === 3)) {
                $coordinatorDept = optional($user->employee)->department;
                $uploaderDept = optional($uploader->employee)->department;

                return $coordinatorDept && $uploaderDept && $coordinatorDept === $uploaderDept;
            }

            return false;
        }

        if ($this->isSharedWithAllFaculty()) {
            return true;
        }

        return static::query()
            ->whereKey($this->getKey())
            ->visibleTo($user)
            ->exists();
    }

    /**
     * Teaching Guides / Exam Questionnaires with no explicit recipients are visible to all faculty.
     */
    public function isSharedWithAllFaculty(): bool
    {
        if (!in_array($this->category, self::SHAREABLE_CATEGORIES, true)) {
            return false;
        }

        if ($this->recipients()->exists()) {
            return false;
        }

        return $this->uploader && $this->uploader->canUploadSharedDocuments();
    }

    public function examQuestionnaire()
    {
        return $this->hasOne(ExamQuestionnaire::class, 'document_id', 'document_id');
    }

    /**
     * Hide Teaching Guides / Exam Questionnaires until linked submission is approved.
     */
    public function scopeOnlyApprovedShareable($query)
    {
        return $query->where(function ($q) {
            $q->whereNotIn('category', self::SHAREABLE_CATEGORIES)
                ->orWhereNull('category')
                ->orWhere(function ($sub) {
                    $sub->where('category', 'Teaching Guides')
                        ->whereHas('teachingGuide', fn ($tg) => $tg->where('status', 'approved'));
                })
                ->orWhere(function ($sub) {
                    $sub->where('category', 'Exam Questionnaires')
                        ->whereHas('examQuestionnaire', fn ($eq) => $eq->where('status', 'approved'));
                });
        });
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isDean() || $user->isSecretary()) {
            return $query;
        }

        if ($user->isProgramCoordinator()) {
            $coordinatorDept = optional($user->employee)->department;

            return $query->where(function ($q) use ($user, $coordinatorDept) {
                $q->where('uploaded_by', $user->id)
                    ->orWhereHas('recipients', fn ($r) => $r->where('users.id', $user->id));

                if ($coordinatorDept) {
                    $q->orWhereHas('uploader', function ($subQ) use ($coordinatorDept) {
                        $subQ->whereHas('role', fn ($r) => $r->where('role_name', 'Faculty Employee'))
                            ->whereHas('employee', fn ($empQ) => $empQ->where('department', $coordinatorDept));
                    });
                }
            });
        }

        // Faculty: own uploads, explicit shares, or dean/secretary/coordinator broadcasts only.
        return $query->where(function ($q) use ($user) {
            $q->where('uploaded_by', $user->id)
                ->orWhereHas('recipients', fn ($r) => $r->where('users.id', $user->id))
                ->orWhere(function ($broadcast) {
                    $broadcast->whereIn('category', self::SHAREABLE_CATEGORIES)
                        ->whereDoesntHave('recipients')
                        ->whereHas('uploader', fn ($u) => $u->whereHas('role', function ($roleQ) {
                            $roleQ->whereIn('role_name', ['Dean', 'Secretary', 'Program Coordinator']);
                        }));
                });
        });
    }

    /**
     * Get filtered documents based on user role
     * Faculty uploads are visible to: owner, coordinator, and dean
     * Coordinator uploads are visible to: owner and dean
     */
    public static function getFilteredDocuments($user, $categoryFilter = null)
    {
        $query = self::with(['uploader.employee', 'category'])->visibleTo($user);

        if ($categoryFilter) {
            $query->where('category', $categoryFilter);
        }

        return $query;
    }
}
