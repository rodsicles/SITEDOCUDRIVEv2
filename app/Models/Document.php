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
        if ($user->isDean()) {
            return true;
        }

        if ($user->role_id === 2) { // Program Coordinator
            if ($this->uploaded_by === $user->id) {
                return true;
            }

            // Faculty uploads from same department only
            if (optional($this->uploader)->role_id === 3) {
                $coordinatorDept = optional($user->employee)->department;
                $uploaderDept = optional(optional($this->uploader)->employee)->department;

                return $coordinatorDept && $uploaderDept && $coordinatorDept === $uploaderDept;
            }

            return false;
        }

        // Faculty – own documents only
        return $this->uploaded_by === $user->id;
    }

    /**
     * Get filtered documents based on user role
     * Faculty uploads are visible to: owner, coordinator, and dean
     * Coordinator uploads are visible to: owner and dean
     */
    public static function getFilteredDocuments($user, $categoryFilter = null)
    {
        $query = self::with(['uploader.employee', 'category']);

        if ($user->isDean()) {
            // Dean sees all documents from both departments
        } elseif ($user->role_id === 2) { // Program Coordinator
            $coordinatorDept = optional($user->employee)->department;

            $query->where(function($q) use ($user, $coordinatorDept) {
                // Own documents
                $q->where('uploaded_by', $user->id);

                // Faculty documents from same department only
                if ($coordinatorDept) {
                    $q->orWhereHas('uploader', function($subQ) use ($coordinatorDept) {
                        $subQ->where('role_id', 3)
                             ->whereHas('employee', function($empQ) use ($coordinatorDept) {
                                 $empQ->where('department', $coordinatorDept);
                             });
                    });
                }
            });
        } else { // Faculty
            // Faculty sees only their own documents
            $query->where('uploaded_by', $user->id);
        }

        if ($categoryFilter) {
            $query->where('category', $categoryFilter);
        }

        return $query->latest();
    }
}
