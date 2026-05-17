<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentView;
use App\Models\DocumentFavorite;
use App\Models\DashboardLog;
use App\Models\Folder;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\TeachingGuideSyncService;
use App\Support\UploadStorage;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DocumentService
{
    public function __construct(
        protected TeachingGuideSyncService $teachingGuideSync,
        protected NotificationService $notificationService,
    ) {}
    /**
     * Document categories available in the system (must match documents.category ENUM).
     */
    const CATEGORIES = [
        'Accreditation and Certifications',
        'Academics',
        'Teaching Guides',
        'Exam Questionnaires',
    ];

    /**
     * All values allowed in documents.category column.
     */
    public static function allowedCategories(): array
    {
        return array_merge(self::CATEGORIES, ['Other']);
    }

    /**
     * Resolve the documents.category value for a folder (walks to root system category).
     */
    public function resolveCategoryForFolder(?int $folderId): string
    {
        if (!$folderId) {
            return 'Other';
        }

        $folder = Folder::find($folderId);
        if (!$folder) {
            return 'Other';
        }

        $rootName = $folder->top_level_category;

        return in_array($rootName, self::allowedCategories(), true) ? $rootName : 'Other';
    }

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

        $tag = $queryParams['tag'] ?? null;
        if ($tag) {
            $query->where('tags', 'like', "%{$tag}%");
        }

        $uploadedBy = $queryParams['uploaded_by'] ?? null;
        if ($uploadedBy) {
            $query->where('uploaded_by', $uploadedBy);
        }

        $dateFrom = $queryParams['date_from'] ?? null;
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        $dateTo = $queryParams['date_to'] ?? null;
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        return $query->paginate($perPage)->appends($queryParams);
    }

    /**
     * Get uploaders visible to the current user for filter dropdowns.
     */
    public function getAvailableUploaders(User $user): Collection
    {
        $uploaderIds = Document::getFilteredDocuments($user)
            ->reorder()
            ->select('uploaded_by')
            ->distinct()
            ->pluck('uploaded_by');

        return User::with('employee')
            ->whereIn('id', $uploaderIds)
            ->get()
            ->sortBy(fn (User $uploader) => $uploader->employee->full_name ?? $uploader->username)
            ->values();
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

        $storageDir = str_starts_with($document->file_path, 'teaching-guides/') ? 'teaching-guides' : 'documents';
        UploadStorage::assertResolvedPath($document->file_path, $storageDir);

        if (!UploadStorage::exists($document->file_path)) {
            throw new \RuntimeException('This file is no longer available. It was uploaded to a previous storage provider and no longer exists in the current storage.');
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

        $mimeType = UploadStorage::mimeType($document->file_path);
        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];

        // For Word docs: serve the sibling PDF for inline preview
        $serveFilePath = $document->file_path;
        if (!in_array($mimeType, $allowedMimes)) {
            $pdfSibling = preg_replace('/\.[^.]+$/', '.pdf', $document->file_path);
            if (UploadStorage::exists($pdfSibling)) {
                $serveFilePath = $pdfSibling;
                $mimeType      = 'application/pdf';
            } else {
                $mimeType = 'application/octet-stream';
            }
        }

        return UploadStorage::inlineResponse($serveFilePath, basename($serveFilePath), $mimeType);
    }

    /**
     * Download a document.
     */
    public function downloadDocument(int $documentId, User $user, string $format = 'word')
    {
        $document = Document::findOrFail($documentId);

        if (!$document->canView($user)) {
            abort(403, 'Unauthorized access');
        }

        $storageDir = str_starts_with($document->file_path, 'teaching-guides/') ? 'teaching-guides' : 'documents';
        UploadStorage::assertResolvedPath($document->file_path, $storageDir);

        if (!UploadStorage::exists($document->file_path)) {
            throw new \RuntimeException('This file is no longer available. It was uploaded to a previous storage provider and no longer exists in the current storage.');
        }

        // Determine which file to download
        $downloadPath = $document->file_path;
        if ($format === 'pdf') {
            $pdfPath = preg_replace('/\.[^.]+$/', '.pdf', $document->file_path);
            if (UploadStorage::exists($pdfPath)) {
                $downloadPath = $pdfPath;
            }
        }

        DashboardLog::create([
            'user_id' => $user->id,
            'activity' => 'Downloaded document: ' . $document->document_title,
            'activity_type' => 'document_downloaded',
            'visibility' => 'own',
        ]);

        return UploadStorage::downloadResponse($downloadPath, basename($downloadPath));
    }

    /**
     * Upload one or more documents and return the count of uploaded files.
     */
    public function uploadDocuments(array $validated, array $files, int $userId, array $recipientIds = []): int
    {
        $tags = !empty($validated['tags']) ? implode(',', array_map('trim', explode(',', $validated['tags']))) : '';

        $category = $this->resolveCategoryForFolder($validated['folder_id'] ?? null);
        $folder = !empty($validated['folder_id']) ? Folder::find($validated['folder_id']) : null;
        $recipientIds = array_values(array_unique(array_filter(array_map('intval', $recipientIds))));
        $subject = $validated['subject'] ?? null;

        $uploadedCount = 0;
        $createdDocuments = [];

        foreach ($files as $index => $file) {
            $filename = time() . '_' . $index . '_' . $file->hashName();
            UploadStorage::putFileAs('documents', $file, $filename);

            $document = Document::create([
                'uploaded_by' => $userId,
                'folder_id' => $validated['folder_id'] ?? null,
                'document_title' => $validated['document_title'] . ($uploadedCount > 0 ? ' (' . ($uploadedCount + 1) . ')' : ''),
                'file_path' => 'documents/' . $filename,
                'file_size' => (int) ($file->getSize() ?? 0),
                'document_type' => $validated['document_type'],
                'category' => $category,
                'tags' => $tags,
            ]);

            if (!empty($recipientIds)) {
                $document->recipients()->sync($recipientIds);
            }

            if ($category === 'Teaching Guides' && $folder) {
                $this->teachingGuideSync->syncFromDocument($document, $folder, $recipientIds, $subject);
            }

            $createdDocuments[] = $document;
            $uploadedCount++;
        }

        if (!empty($recipientIds) && $uploadedCount > 0) {
            $label = $uploadedCount === 1
                ? "\"{$validated['document_title']}\""
                : "{$uploadedCount} document(s)";
            $this->notificationService->notifyMany(
                $recipientIds,
                "New shared document: {$label} in {$category}. Check Documents or Teaching Guides."
            );
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
     * Search faculty and coordinators for recipient picker (dean/coordinator uploads).
     */
    public function searchRecipients(string $term, int $limit = 20): Collection
    {
        $term = trim($term);

        return User::query()
            ->with(['employee', 'role'])
            ->where('status', 'Active')
            ->whereHas('role', fn ($q) => $q->whereIn('role_name', ['Faculty Employee', 'Program Coordinator']))
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('username', 'like', "%{$term}%")
                        ->orWhereHas('employee', fn ($e) => $e->where('full_name', 'like', "%{$term}%"));
                });
            })
            ->orderBy('username')
            ->limit($limit)
            ->get();
    }

    public function isShareableCategory(?string $category): bool
    {
        return in_array($category, Document::SHAREABLE_CATEGORIES, true);
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

    /**
     * Soft delete a document.
     */
    public function deleteDocument(int $documentId, User $user): void
    {
        $document = Document::findOrFail($documentId);

        if (!($user->isDean() || $user->isSecretary() || $document->uploaded_by === $user->id)) {
            abort(403, 'Unauthorized');
        }

        DashboardLog::create([
            'user_id' => $user->id,
            'activity' => 'Deleted document: ' . $document->document_title,
            'activity_type' => 'document_deleted',
            'visibility' => 'own',
        ]);

        $document->delete();
    }
}
