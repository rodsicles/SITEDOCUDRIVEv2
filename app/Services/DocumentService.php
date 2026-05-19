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
use App\Models\SchoolYear;
use App\Support\AcademicYear;
use App\Support\UploadStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DocumentService
{
    public function __construct(
        protected TeachingGuideSyncService $teachingGuideSync,
        protected ExamQuestionnaireSyncService $examQuestionnaireSync,
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
        $query = $this->buildDocumentListQuery($user, $categoryFilter, $folderFilter, $queryParams);

        $sort = $queryParams['sort'] ?? 'date';
        match ($sort) {
            'size' => $query->orderByDesc('file_size'),
            'title' => $query->orderBy('document_title'),
            'author' => $query->orderBy('uploaded_by'),
            'category' => $query->orderBy('category'),
            default => $query->latest('created_at'),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Title suggestions for the documents list search (min 3 characters).
     *
     * @return list<string>
     */
    public function suggestDocumentTitles(
        User $user,
        ?string $categoryFilter,
        ?string $folderFilter,
        array $queryParams,
        string $term,
        int $limit = 8,
    ): array {
        $term = trim($term);
        if (mb_strlen($term) < 3) {
            return [];
        }

        $params = $queryParams;
        unset($params['search'], $params['name'], $params['page']);

        $query = $this->buildDocumentListQuery($user, $categoryFilter, $folderFilter, $params, applyTextSearch: false);

        return $query
            ->where('document_title', 'like', '%'.$term.'%')
            ->orderBy('document_title')
            ->limit($limit * 3)
            ->pluck('document_title')
            ->unique()
            ->take($limit)
            ->values()
            ->all();
    }

    protected function buildDocumentListQuery(
        User $user,
        ?string $categoryFilter,
        ?string $folderFilter,
        array $queryParams,
        bool $applyTextSearch = true,
    ): Builder {
        $query = Document::getFilteredDocuments($user, $categoryFilter)
            ->onlyApprovedShareable();

        if ($folderFilter !== null) {
            if ($folderFilter === 'uncategorized') {
                $query->whereNull('folder_id');
            } else {
                $folderIds = [(int) $folderFilter];
                $childIds = Folder::where('parent_id', $folderFilter)->pluck('folder_id')->toArray();
                $folderIds = array_merge($folderIds, $childIds);
                if (!empty($childIds)) {
                    $grandchildIds = Folder::whereIn('parent_id', $childIds)->pluck('folder_id')->toArray();
                    $folderIds = array_merge($folderIds, $grandchildIds);
                }
                $query->whereIn('folder_id', $folderIds);
            }
        }

        if ($applyTextSearch) {
            $name = $queryParams['name'] ?? $queryParams['search'] ?? null;
            if ($name) {
                $query->where(function ($q) use ($name) {
                    $q->where('document_title', 'like', "%{$name}%")
                        ->orWhere('tags', 'like', "%{$name}%");
                });
            }

            $title = $queryParams['title'] ?? null;
            if ($title) {
                $query->where('document_title', 'like', "%{$title}%");
            }
        }

        $fileType = $queryParams['file_type'] ?? null;
        if ($fileType === 'pdf') {
            $query->where(function ($q) {
                $q->where('file_path', 'like', '%.pdf')
                    ->orWhere('document_type', 'like', '%pdf%');
            });
        } elseif ($fileType === 'word') {
            $query->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->where('file_path', 'like', '%.doc')
                        ->orWhere('file_path', 'like', '%.docx');
                })->orWhere('document_type', 'like', '%doc%');
            });
        }

        $sizeRange = $queryParams['size_range'] ?? null;
        if ($sizeRange === 'small') {
            $query->where('file_size', '<', 1048576);
        } elseif ($sizeRange === 'medium') {
            $query->whereBetween('file_size', [1048576, 5242880]);
        } elseif ($sizeRange === 'large') {
            $query->where('file_size', '>', 5242880);
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

        $academicYearStart = AcademicYear::startYearFromQuery($queryParams['academic_year'] ?? null);
        if ($academicYearStart) {
            $hierarchy = app(AcademicHierarchyService::class);
            $endYear = $academicYearStart + 1;
            $folderIds = array_merge(
                $hierarchy->folderIdsForSchoolYear('tg', $academicYearStart),
                $hierarchy->folderIdsForSchoolYear('eq', $academicYearStart),
            );
            $folderIds = array_merge(
                $folderIds,
                Folder::where('is_system', true)
                    ->where(function ($q) use ($academicYearStart, $endYear) {
                        $q->where('slug', 'like', "%{$academicYearStart}-{$endYear}%")
                            ->orWhere('folder_name', 'like', "%{$academicYearStart}-{$endYear}%");
                    })
                    ->pluck('folder_id')
                    ->all()
            );
            $folderIds = array_values(array_unique(array_filter($folderIds)));
            if (!empty($folderIds)) {
                $query->whereIn('folder_id', $folderIds);
            }
        } else {
            $activeId = SchoolYear::activeId();
            $query->where(function ($q) use ($activeId) {
                $q->where('school_year_id', $activeId)
                    ->orWhereNull('school_year_id');
            });
        }

        return $query;
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

        UploadStorage::assertPathAllowed($document->file_path);

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

        UploadStorage::assertPathAllowed($serveFilePath);

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

        UploadStorage::assertPathAllowed($document->file_path);

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

        UploadStorage::assertPathAllowed($downloadPath);

        DashboardLog::create([
            'user_id' => $user->id,
            'activity' => 'Downloaded document: ' . $document->document_title,
            'activity_type' => 'document_downloaded',
            'visibility' => 'own',
        ]);

        return UploadStorage::downloadResponse($downloadPath, basename($downloadPath));
    }

    /**
     * Upload one or more documents.
     *
     * @return array{count: int, submitted_for_approval: bool}
     */
    public function uploadDocuments(array $validated, array $files, int $userId, array $recipientIds = []): array
    {
        $tags = !empty($validated['tags']) ? implode(',', array_map('trim', explode(',', $validated['tags']))) : '';

        $category = $this->resolveCategoryForFolder($validated['folder_id'] ?? null);
        $folder = !empty($validated['folder_id']) ? Folder::find($validated['folder_id']) : null;
        $recipientIds = array_values(array_unique(array_filter(array_map('intval', $recipientIds))));
        $subject = $validated['subject'] ?? null;
        $uploader = User::findOrFail($userId);
        $hierarchy = app(AcademicHierarchyService::class);

        if ($folder && $subject && $hierarchy->isSemesterTypeLeafFolder($folder)
            && !$hierarchy->isUnderTgCategory($folder) && !$hierarchy->isUnderEqCategory($folder)) {
            $folder = $hierarchy->ensureCourseFolder($folder, $subject);
            $validated['folder_id'] = $folder->folder_id;
        }
        $autoApprove = $uploader->isDean() || $uploader->isSecretary();
        $submittedForApproval = false;

        $uploadedCount = 0;

        foreach ($files as $index => $file) {
            $title = $validated['document_title'] . ($uploadedCount > 0 ? ' (' . ($uploadedCount + 1) . ')' : '');
            $extension = strtolower($file->getClientOriginalExtension());
            $fileType = $validated['document_type'] === 'pdf' || $extension === 'pdf' ? 'pdf' : 'word';

            if ($category === 'Exam Questionnaires' && $folder) {
                $filename = time() . '_' . $index . '_' . $file->hashName();
                $storedPath = UploadStorage::storeAs($file, 'exam-questionnaires', $filename);
                $examType = $validated['exam_type'] ?? 'Quiz';
                $status = $autoApprove ? 'approved' : 'pending';
                $submissionTitle = $validated['document_title'];

                $questionnaire = $this->examQuestionnaireSync->createFromFolderUpload(
                    $userId,
                    $folder,
                    $submissionTitle,
                    $storedPath,
                    $fileType,
                    $examType,
                    $subject,
                    $status,
                    $autoApprove ? $userId : null,
                );

                if ($autoApprove) {
                    $this->examQuestionnaireSync->syncToDocument($questionnaire->fresh());
                } else {
                    $submittedForApproval = true;
                    $this->notificationService->notifySupervisors(
                        "Exam questionnaire pending approval: \"{$submissionTitle}\" in {$folder->folder_name}."
                    );
                }

                $uploadedCount++;
                continue;
            }

            if ($category === 'Teaching Guides' && $folder) {
                $filename = time() . '_' . $index . '_' . $file->hashName();
                $storedPath = UploadStorage::storeAs($file, 'teaching-guides', $filename);
                $status = $autoApprove ? 'approved' : 'pending';
                $guideTitle = $validated['document_title'];

                $guide = $this->teachingGuideSync->createFromFolderUpload(
                    $userId,
                    $folder,
                    $guideTitle,
                    $storedPath,
                    $fileType,
                    $subject,
                    $status,
                    $autoApprove ? $userId : null,
                    $recipientIds,
                );

                if (!$guide) {
                    continue;
                }

                if ($autoApprove) {
                    $this->teachingGuideSync->syncDocumentFromGuide($guide, $uploader, $recipientIds);
                } else {
                    $submittedForApproval = true;
                    $this->notificationService->notifySupervisors(
                        "Teaching guide pending approval: \"{$guideTitle}\" in {$folder->folder_name}."
                    );
                }

                $uploadedCount++;
                continue;
            }

            $filename = time() . '_' . $index . '_' . $file->hashName();
            UploadStorage::putFileAs('documents', $file, $filename);

            $document = Document::create([
                'uploaded_by' => $userId,
                'folder_id' => $validated['folder_id'] ?? null,
                'document_title' => $title,
                'subject' => $subject,
                'file_path' => 'documents/' . $filename,
                'file_size' => (int) ($file->getSize() ?? 0),
                'document_type' => $validated['document_type'],
                'category' => $category,
                'school_year_id' => SchoolYear::activeId(),
                'tags' => in_array($category, Document::SHAREABLE_CATEGORIES, true) ? '' : $tags,
            ]);

            if (!empty($recipientIds)) {
                $document->recipients()->sync($recipientIds);
            }

            $uploadedCount++;
        }

        if (!empty($recipientIds) && $uploadedCount > 0 && !$submittedForApproval) {
            $label = $uploadedCount === 1
                ? "\"{$validated['document_title']}\""
                : "{$uploadedCount} document(s)";
            $this->notificationService->notifyMany(
                $recipientIds,
                "New shared document: {$label} in {$category}. Check Documents or Teaching Guides."
            );
        }

        $activity = $submittedForApproval
            ? 'Submitted ' . $uploadedCount . ' file(s) for Dean approval: ' . $validated['document_title']
            : 'Uploaded ' . $uploadedCount . ' document(s): ' . $validated['document_title'];

        DashboardLog::create([
            'user_id' => $userId,
            'activity' => $activity,
            'activity_type' => 'document_upload',
            'visibility' => 'own',
        ]);

        return [
            'count' => $uploadedCount,
            'submitted_for_approval' => $submittedForApproval,
        ];
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
     * Move a document to the Recycle Bin (soft delete).
     */
    public function userCanRenameDocument(Document $document, User $user): bool
    {
        if ($user->isDean() || $user->isSecretary()) {
            return true;
        }

        if ((int) $document->uploaded_by === (int) $user->id) {
            return true;
        }

        if ($user->isProgramCoordinator()) {
            return Document::query()
                ->where('document_id', $document->document_id)
                ->visibleTo($user)
                ->exists();
        }

        return false;
    }

    public function renameDocument(int $documentId, User $user, string $title): Document
    {
        $document = Document::with(['teachingGuide', 'examQuestionnaire'])->findOrFail($documentId);

        if (!$this->userCanRenameDocument($document, $user)) {
            abort(403, 'You are not allowed to rename this document.');
        }

        $oldTitle = $document->document_title;
        $title = trim($title);

        $document->update(['document_title' => $title]);

        if ($document->teachingGuide) {
            $document->teachingGuide->update(['title' => $title]);
        }

        if ($document->examQuestionnaire) {
            $document->examQuestionnaire->update(['title' => $title]);
        }

        DashboardLog::create([
            'user_id' => $user->id,
            'activity' => "Renamed document from \"{$oldTitle}\" to \"{$title}\"",
            'activity_type' => 'document_renamed',
            'visibility' => $user->isDeanOrSecretary() ? 'dean' : 'own',
        ]);

        return $document->fresh();
    }

    public function deleteDocument(int $documentId, User $user): void
    {
        $document = Document::findOrFail($documentId);

        if (!($user->isDean() || $user->isSecretary() || $document->uploaded_by === $user->id)) {
            abort(403, 'Unauthorized');
        }

        DashboardLog::create([
            'user_id' => $user->id,
            'activity' => 'Moved document to Recycle Bin: ' . $document->document_title,
            'activity_type' => 'document_deleted',
            'visibility' => $user->isDeanOrSecretary() ? 'dean' : 'own',
        ]);

        app(RecycleBinService::class)->moveToRecycleBin($document, $user);
    }
}
