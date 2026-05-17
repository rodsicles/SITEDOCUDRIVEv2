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
        'document_title',
        'subject',
        'file_path',
        'file_size',
        'document_type',
        'category',
        'category_id',
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

        if ($this->uploaded_by === $user->id) {
            return true;
        }

        if ($this->recipients()->where('users.id', $user->id)->exists()) {
            return true;
        }

        if ($user->isProgramCoordinator()) {
            if (optional($this->uploader)->role_id === 3) {
                $coordinatorDept = optional($user->employee)->department;
                $uploaderDept = optional(optional($this->uploader)->employee)->department;

                return $coordinatorDept && $uploaderDept && $coordinatorDept === $uploaderDept;
            }

            return false;
        }

        if ($this->isSharedWithAllFaculty()) {
            return true;
        }

        return false;
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
                        $subQ->where('role_id', 3)
                            ->whereHas('employee', function ($empQ) use ($coordinatorDept) {
                                $empQ->where('department', $coordinatorDept);
                            });
                    });
                }
            });
        }

        return $query->where(function ($q) use ($user) {
            $q->where('uploaded_by', $user->id)
                ->orWhereHas('recipients', fn ($r) => $r->where('users.id', $user->id))
                ->orWhere(function ($shared) {
                    $shared->whereIn('category', self::SHAREABLE_CATEGORIES)
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

        return $query->latest();
    }
}
