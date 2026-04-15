<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentView;
use App\Models\DocumentFavorite;
use App\Models\DashboardLog;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DocumentService
{
    /**
     * Document categories available in the system.
     */
    const CATEGORIES = ['Accreditation and Certifications', 'Academics'];

    /**
     * Get filtered and paginated documents for a user.
     */
    public function getFilteredDocuments(User $user, ?string $categoryFilter, ?string $folderFilter, array $queryParams = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Document::getFilteredDocuments($user, $categoryFilter);

        if ($folderFilter !== null) {
            if ($folderFilter === 'uncategorized') {
                $query->whereNull('folder_id');
            } else {
                // Include documents in child folders
                $folderIds = [(int) $folderFilter];
                $childIds = Folder::where('parent_id', $folderFilter)->pluck('folder_id')->toArray();
                $folderIds = array_merge($folderIds, $childIds);
                // Also include grandchildren
                if (!empty($childIds)) {
                    $grandchildIds = Folder::whereIn('parent_id', $childIds)->pluck('folder_id')->toArray();
                    $folderIds = array_merge($folderIds, $grandchildIds);
                }
                $query->whereIn('folder_id', $folderIds);
            }
        }

        // Search by document title or tags
        $search = $queryParams['search'] ?? null;
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('document_title', 'like', "%{$search}%")
                  ->orWhere('tags', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage)->appends($queryParams);
    }

    /**
     * Get user's folders with document counts.
     */
    public function getUserFolders(int $userId): Collection
    {
        return Folder::where('user_id', $userId)
            ->withCount('documents')
            ->orderBy('folder_name')
            ->get();
    }

    /**
     * Get recently viewed documents for a user.
     */
    public function getRecentDocuments(int $userId, int $limit = 5): Collection
    {
        return DocumentView::getRecentDocuments($userId, $limit);
    }

    /**
     * Get user's favorite documents.
     */
    public function getFavoriteDocuments(User $user): Collection
    {
        return $user->documentFavorites()->with('document')->get()->pluck('document');
    }

    /**
     * View a document (returns file response).
     */
    public function viewDocument(int $documentId, User $user, bool $trackView = false)
    {
        $document = Document::findOrFail($documentId);

        if (!$document->canView($user)) {
            abort(403, 'Unauthorized access');
        }

        // Path traversal protection
        if (str_contains($document->file_path, '..') || str_contains($document->file_path, './')) {
            abort(403, 'Invalid file path');
        }

        $allowedDir = Storage::disk('local')->path('documents');
        $realFilePath = realpath(Storage::disk('local')->path($document->file_path));
        if (!$realFilePath || !str_starts_with($realFilePath, realpath($allowedDir))) {
            abort(403, 'Unauthorized file access');
        }

        if (!Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'File not found');
        }

        if ($trackView) {
            DocumentView::trackView($user->id, $documentId);

            DashboardLog::create([
                'user_id' => $user->id,
                'activity' => 'Viewed document: ' . $document->document_title,
                'activity_type' => 'document_viewed',
                'visibility' => 'own',
            ]);
        }

        $mimeType = Storage::disk('local')->mimeType($document->file_path);
        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
        if (!in_array($mimeType, $allowedMimes)) {
            $mimeType = 'application/octet-stream';
        }

        return Storage::disk('local')->response($document->file_path, null, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($document->file_path) . '"',
        ]);
    }

    /**
     * Download a document.
     */
    public function downloadDocument(int $documentId, User $user)
    {
        $document = Document::findOrFail($documentId);

        if (!$document->canView($user)) {
            abort(403, 'Unauthorized access');
        }

        // Path traversal protection
        if (str_contains($document->file_path, '..') || str_contains($document->file_path, './')) {
            abort(403, 'Invalid file path');
        }

        $allowedDir = Storage::disk('local')->path('documents');
        $realFilePath = realpath(Storage::disk('local')->path($document->file_path));
        if (!$realFilePath || !str_starts_with($realFilePath, realpath($allowedDir))) {
            abort(403, 'Unauthorized file access');
        }

        if (!Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'File not found');
        }

        DashboardLog::create([
            'user_id' => $user->id,
            'activity' => 'Downloaded document: ' . $document->document_title,
            'activity_type' => 'document_downloaded',
            'visibility' => 'own',
        ]);

        return Storage::disk('local')->download($document->file_path, basename($document->file_path));
    }

    /**
     * Upload one or more documents and return the count of uploaded files.
     */
    public function uploadDocuments(array $validated, array $files, int $userId): int
    {
        $tags = !empty($validated['tags']) ? implode(',', array_map('trim', explode(',', $validated['tags']))) : '';

        $uploadedCount = 0;
        foreach ($files as $index => $file) {
            // Sanitize filename: use hash only, no original name
            $filename = time() . '_' . $index . '_' . $file->hashName();
            Storage::disk('local')->putFileAs('documents', $file, $filename);

            // Auto-derive category from folder's top-level ancestor
            $category = 'Other';
            if (!empty($validated['folder_id'])) {
                $folder = Folder::find($validated['folder_id']);
                if ($folder) {
                    $category = $folder->top_level_category ?? 'Other';
                }
            }

            Document::create([
                'uploaded_by' => $userId,
                'folder_id' => $validated['folder_id'] ?? null,
                'document_title' => $validated['document_title'] . ($uploadedCount > 0 ? ' (' . ($uploadedCount + 1) . ')' : ''),
                'file_path' => 'documents/' . $filename,
                'document_type' => $validated['document_type'],
                'category' => $category,
                'tags' => $tags,
            ]);
            $uploadedCount++;
        }

        DashboardLog::create([
            'user_id' => $userId,
            'activity' => 'Uploaded ' . $uploadedCount . ' document(s): ' . $validated['document_title'],
            'activity_type' => 'document_upload',
            'visibility' => 'own',
        ]);

        return $uploadedCount;
    }

    /**
     * Toggle document favorite for a user.
     */
    public function toggleFavorite(int $documentId, int $userId): array
    {
        $document = Document::findOrFail($documentId);
        $isFavorited = $document->toggleFavorite($userId);

        DashboardLog::create([
            'user_id' => $userId,
            'activity' => ($isFavorited ? 'Favorited' : 'Unfavorited') . ' document: ' . $document->document_title,
            'activity_type' => $isFavorited ? 'document_favorited' : 'document_unfavorited',
            'visibility' => 'own',
        ]);

        return [
            'favorited' => $isFavorited,
            'message' => $isFavorited ? 'Document added to favorites' : 'Document removed from favorites',
        ];
    }

    /**
     * Get all document categories.
     */
    public function getCategories(): array
    {
        return self::CATEGORIES;
    }
}
