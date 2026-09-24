<?php

namespace App\Services;

use App\Models\Folder;
use App\Models\Document;
use App\Models\DashboardLog;
use App\Models\SchoolYear;
use App\Models\User;
use App\Support\CoordinatorDepartment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FolderService
{
    public function __construct(
        protected RecycleBinService $recycleBinService,
    ) {}

    public function visibleFolderOrFail(int $folderId, User $viewer): Folder
    {
        return Folder::with('parent.parent')->visibleTo($viewer)->findOrFail($folderId);
    }
    /**
     * Get the system folder tree: top-level categories with children eager-loaded.
     */
    public function getSystemFolderTree(?User $viewer = null): Collection
    {
        app(AcademicHierarchyService::class)->ensureActiveSchoolYearStructures();

        $activeSchoolYearId = SchoolYear::activeId();

        $documentCount = function ($query) use ($viewer) {
            $query->onlyApprovedShareable();
            if ($viewer) {
                $query->visibleTo($viewer);
            }
        };

        $tree = Folder::system()
            ->topLevel()
            ->when($viewer, fn ($q) => $q->visibleTo($viewer))
            ->with(['children' => function ($query) use ($documentCount) {
                $query->system()->orderBy('sort_order')
                    ->withCount(['documents' => $documentCount])
                    ->with(['children' => function ($q) use ($documentCount) {
                        $q->system()->orderBy('sort_order')->withCount(['documents' => $documentCount]);
                    }]);
            }])
            ->withCount(['documents' => $documentCount])
            ->orderBy('sort_order')
            ->get();

        return $tree->map(function (Folder $category) use ($activeSchoolYearId, $viewer) {
            if ($category->isCustomFoldersCategory()) {
                $category->setRelation('children', $this->getCustomFoldersForViewer($viewer));

                return $category;
            }

            if (!in_array($category->slug, ['tg-category', 'eq-category'], true)) {
                return $category;
            }

            if ($activeSchoolYearId) {
                $category->setRelation(
                    'children',
                    $category->children->filter(
                        fn (Folder $f) => (int) $f->school_year_id === (int) $activeSchoolYearId
                    )->values()
                );
            }

            return $category;
        });
    }

    /**
     * User-created folders directly under the Custom Folders category (one level only).
     */
    public function getCustomFoldersForViewer(?User $viewer): Collection
    {
        $categoryId = Folder::query()
            ->where('slug', Folder::CUSTOM_FOLDERS_SLUG)
            ->value('folder_id');

        if (!$categoryId) {
            return collect();
        }

        $query = Folder::query()
            ->where('parent_id', $categoryId)
            ->where('is_system', false)
            ->orderBy('folder_name');

        if ($viewer) {
            $query->visibleTo($viewer);
        }

        if ($viewer?->isFaculty()) {
            $query->where('user_id', $viewer->id);
        } elseif ($viewer?->isProgramCoordinator()) {
            $dept = optional($viewer->employee)->program;
            if ($dept) {
                $query->whereHas('user.employee', fn ($e) => $e->where('program', $dept));
            } else {
                $query->where('user_id', $viewer->id);
            }
        }

        return $query->get();
    }

    /**
     * System subfolders for navigation, scoped to active school year when applicable.
     */
    public function getDisplayFolders(Folder $parent, ?User $viewer = null): Collection
    {
        if ($viewer && ! $parent->canBeViewedBy($viewer)) {
            abort(404);
        }
        if ($parent->isCustomFoldersCategory()) {
            return $this->attachSubtreeDocumentCounts($this->getCustomFoldersForViewer($viewer), $viewer);
        }

        if ($parent->isCustomSubfolder()) {
            return collect();
        }

        $documentCount = function ($query) use ($viewer) {
            $query->onlyApprovedShareable();
            if ($viewer) {
                $query->visibleTo($viewer);
            }
        };

        $query = $parent->children()->system()->orderBy('sort_order')
            ->withCount(['documents' => $documentCount]);

        if ($viewer) {
            $query->visibleTo($viewer);
        }

        $activeSchoolYearId = SchoolYear::activeId();
        $hierarchy = app(AcademicHierarchyService::class);

        if ($activeSchoolYearId && $hierarchy->isTeachingGuidesOrExamCategory($parent)) {
            $query->where('school_year_id', $activeSchoolYearId);
        }

        $folders = $query->get();

        if ($hierarchy->isTgSemesterFolder($parent)) {
            $folders = $folders->filter(function (Folder $folder) {
                $slug = strtolower((string) $folder->slug);

                return str_contains($slug, '-subject-') || str_contains($slug, '-course-');
            })->values();
        }

        if ($hierarchy->isTgSubjectFolder($parent)) {
            $folders = $folders->filter(function (Folder $folder) {
                return in_array(strtoupper(trim((string) $folder->folder_name)), ['TG', 'LB'], true);
            })->values();
        }

        if ($hierarchy->isEqSemesterFolder($parent)) {
            $folders = $folders->filter(function (Folder $folder) {
                $slug = strtolower((string) $folder->slug);

                return str_contains($slug, '-subject-') || str_contains($slug, '-course-');
            })->values();
        }

        if ($hierarchy->isEqSubjectFolder($parent)) {
            $folders = $folders->filter(function (Folder $folder) {
                return in_array(trim((string) $folder->folder_name), array_values(AcademicHierarchyService::EQ_ASSESSMENT_FOLDERS), true);
            })->values();
        }

        if ($hierarchy->isEqAssessmentFolder($parent)) {
            $folders = $folders->filter(function (Folder $folder) {
                return in_array(strtoupper(trim((string) $folder->folder_name)), ['TOS', 'TOQ'], true);
            })->values();
        }

        if ($viewer
            && ! $viewer->isDeanOrSecretary()
            && ($hierarchy->isTgSemesterFolder($parent) || $hierarchy->isEqSemesterFolder($parent))) {
            $folders = CoordinatorDepartment::filterSubjectFolders($folders, $viewer);
        }

        return $this->attachSubtreeDocumentCounts($folders, $viewer);
    }

    /**
     * Replace documents_count with total visible files in folder + all descendants.
     * Also attaches last_document_at and pending_count for richer folder cards.
     */
    public function attachSubtreeDocumentCounts(Collection $folders, ?User $viewer): Collection
    {
        return $folders->map(function (Folder $folder) use ($viewer) {
            $folder->loadMissing(['children.children']);

            $folderIds = array_merge([$folder->folder_id], $folder->getDescendantIds());
            $query = Document::query()
                ->whereIn('folder_id', $folderIds)
                ->onlyApprovedShareable();

            if ($viewer) {
                $query->visibleTo($viewer);
            }

            $folder->setAttribute('documents_count', (clone $query)->count());
            $folder->setAttribute('last_document_at', (clone $query)->max('created_at'));

            $pendingQuery = Document::query()
                ->whereIn('folder_id', $folderIds)
                ->where(function ($q) {
                    $q->whereHas('teachingGuide', fn ($tg) => $tg->where('status', 'pending'))
                        ->orWhereHas('examQuestionnaire', fn ($eq) => $eq->where('status', 'pending'));
                });

            if ($viewer && ! $viewer->isDeanOrSecretary()) {
                $pendingQuery->where('uploaded_by', $viewer->id);
            }

            $folder->setAttribute('pending_count', $pendingQuery->count());

            return $folder;
        });
    }

    /**
     * Get uploadable folders (leaf folders or folders that accept documents) grouped by category.
     */
    public function getUploadableFolders(): array
    {
        $tree = $this->getSystemFolderTree();
        $grouped = [];

        foreach ($tree as $category) {
            $folders = [];
            foreach ($category->children as $folder) {
                if ($folder->children->isEmpty()) {
                    $folders[] = $folder;
                } else {
                    foreach ($folder->children as $subFolder) {
                        $subFolder->display_name = $folder->folder_name . ' - ' . $subFolder->folder_name;
                        $folders[] = $subFolder;
                    }
                }
            }
            $grouped[$category->folder_name] = $folders;
        }

        return $grouped;
    }

    /**
     * Move a document to a folder. Checks document ownership only.
     */
    public function moveDocument(int $documentId, int $userId, ?int $folderId): string
    {
        $document = Document::findOrFail($documentId);

        abort_unless($document->canView(User::findOrFail($userId)), 404);

        if ($document->uploaded_by !== $userId) {
            abort(403, 'Unauthorized action.');
        }

        if ($folderId) {
            $destination = Folder::visibleTo(User::findOrFail($userId))->findOrFail($folderId);
            abort_if($destination->is_private && (int) $destination->privacy_owner_id !== $userId, 403);
            abort_if($destination->document_category_id && !$destination->managedCategory?->is_active, 422, 'This category is inactive.');
        }

        // Keep documents.category in sync with the destination folder's root
        // category, otherwise the document stays listed under its old tab.
        $document->update([
            'folder_id' => $folderId,
            'category_id' => $folderId ? Folder::find($folderId)?->document_category_id : null,
            'category' => app(DocumentService::class)->resolveCategoryForFolder($folderId),
        ]);

        $folderName = $folderId
            ? Folder::find($folderId)->folder_name
            : 'Uncategorized';

        DashboardLog::create([
            'user_id' => $userId,
            'activity' => "Moved document '{$document->document_title}' to folder '{$folderName}'",
            'activity_type' => 'document_moved',
            'visibility' => 'own',
        ]);

        return $folderName;
    }

    /**
     * Create a folder. If parent_id is a system folder, create as system subfolder.
     */
    public function createFolder(int $userId, string $folderName, string $color = '#028a0f', ?int $parentId = null): Folder
    {
        $data = [
            'user_id' => $userId,
            'folder_name' => $folderName,
            'color' => $color,
        ];

        if ($parentId) {
            $parent = Folder::where('folder_id', $parentId)
                ->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)->orWhere('is_system', true);
                })
                ->firstOrFail();

            abort_unless($parent->canBeViewedBy(User::findOrFail($userId)), 404);
            if ($parent->document_category_id) {
                abort_unless($parent->managedCategory?->is_active, 422, 'This category is inactive.');
                $data['document_category_id'] = $parent->document_category_id;
                $data['is_private'] = $parent->is_private;
                $data['privacy_owner_id'] = $parent->privacy_owner_id;
            }

            if ($parent->isCustomFoldersCategory()) {
                $data['parent_id'] = $parentId;
                $data['is_system'] = false;
                $data['level'] = 1;
                $data['sort_order'] = Folder::where('parent_id', $parentId)->max('sort_order') + 1;
                $data['slug'] = 'custom-'.$userId.'-'.\Illuminate\Support\Str::slug($folderName).'-'.time();
                $folder = Folder::create($data);

                DashboardLog::create([
                    'user_id' => $userId,
                    'activity' => "Created custom folder: {$folderName}",
                    'activity_type' => 'folder_created',
                    'visibility' => 'own',
                ]);

                return $folder;
            }

            if ($parent->isCustomSubfolder()) {
                throw new \InvalidArgumentException('You cannot create a folder inside a custom folder. Only one level is allowed.');
            }

            $hierarchy = app(AcademicHierarchyService::class);
            if ($hierarchy->isTgSemesterFolder($parent)
                || $hierarchy->isTgSubjectFolder($parent)
                || $hierarchy->isTgUploadLeafFolder($parent)
                || $hierarchy->isEqSemesterFolder($parent)
                || $hierarchy->isEqSubjectFolder($parent)
                || $hierarchy->isEqAssessmentFolder($parent)
                || $hierarchy->isEqUploadLeafFolder($parent)) {
                throw new \InvalidArgumentException('You cannot create folders here. Use the provided folders to upload files.');
            }

            $data['parent_id'] = $parentId;
            $data['is_system'] = $parent->is_system;
            $data['level'] = $parent->level + 1;
            $data['sort_order'] = Folder::where('parent_id', $parentId)->max('sort_order') + 1;
            $data['slug'] = \Illuminate\Support\Str::slug($folderName) . '-' . time();
        }

        $folder = Folder::create($data);

        DashboardLog::create([
            'user_id' => $userId,
            'activity' => "Created folder: {$folderName}" . ($parentId ? ' (subfolder)' : ''),
            'activity_type' => 'folder_created',
            'visibility' => 'own',
        ]);

        return $folder;
    }

    public function userOwnsCustomFolder(Folder $folder, User $user): bool
    {
        return $folder->isCustomSubfolder() && (int) $folder->user_id === (int) $user->id;
    }

    public function setPrivacy(Folder $folder, User $actor, bool $private): Folder
    {
        abort_unless(Schema::hasColumns('folders', ['is_private', 'privacy_owner_id', 'locked_at']), 503, 'Folder privacy is temporarily unavailable while the database update completes.');
        abort_unless($actor->isFaculty() && $this->userOwnsCustomFolder($folder, $actor), 403);

        if ($private) {
            $hasPendingRequest = Document::query()
                ->where('folder_id', $folder->folder_id)
                ->whereHas('requestSubmissions', fn ($q) => $q->whereIn('status', ['pending', 'submitted', 'changes_requested']))
                ->exists();

            if ($hasPendingRequest) {
                abort(422, 'This folder contains an unresolved requested submission and cannot be made private.');
            }
        }

        return DB::transaction(function () use ($folder, $actor, $private) {
            $folderIds = array_merge([$folder->folder_id], $folder->getDescendantIds());

            Folder::whereIn('folder_id', $folderIds)->update([
                'is_private' => $private,
                'privacy_owner_id' => $private ? $actor->id : null,
                'locked_at' => $private ? now() : null,
            ]);

            if ($private) {
                Document::whereIn('folder_id', $folderIds)->each(
                    fn (Document $document) => $document->recipients()->detach()
                );
            }

            DashboardLog::create([
                'user_id' => $actor->id,
                'activity' => ($private ? 'Locked' : 'Unlocked').' a private folder (ID '.$folder->folder_id.')',
                'activity_type' => $private ? 'folder_locked' : 'folder_unlocked',
                'visibility' => 'own',
            ]);

            return $folder->fresh();
        });
    }

    /**
     * Update a folder's name and/or color. Verifies ownership.
     */
    public function updateFolder(int $folderId, int $userId, string $folderName, ?string $color = null): Folder
    {
        $folder = Folder::where('folder_id', $folderId)
            ->where('user_id', $userId)
            ->where('is_system', false)
            ->firstOrFail();

        if (!$folder->isCustomSubfolder()) {
            abort(403, 'Only custom folders you created can be renamed here.');
        }

        $folder->update(array_filter([
            'folder_name' => $folderName,
            'color' => $color,
        ], fn($v) => $v !== null));

        DashboardLog::create([
            'user_id' => $userId,
            'activity' => "Renamed folder to: {$folderName}",
            'activity_type' => 'folder_updated',
            'visibility' => 'own',
        ]);

        return $folder->fresh();
    }

    /**
     * Delete a custom folder and move its documents to the Recycle Bin.
     */
    public function deleteFolder(int $folderId, int $userId): void
    {
        $folder = Folder::where('folder_id', $folderId)
            ->where('user_id', $userId)
            ->where('is_system', false)
            ->firstOrFail();

        if (!$folder->isCustomSubfolder()) {
            abort(403, 'Only custom folders you created can be deleted here.');
        }

        $folderName = $folder->folder_name;
        $actor = User::findOrFail($userId);

        $documents = Document::where('folder_id', $folderId)->get();
        foreach ($documents as $document) {
            $this->recycleBinService->moveToRecycleBin($document, $actor);
        }

        $folder->delete();

        DashboardLog::create([
            'user_id' => $userId,
            'activity' => "Deleted custom folder \"{$folderName}\" and moved {$documents->count()} file(s) to Recycle Bin",
            'activity_type' => 'folder_deleted',
            'visibility' => 'own',
        ]);
    }

    /**
     * Get all folders for a user with document counts (legacy support).
     */
    public function getUserFolders(int $userId): Collection
    {
        return Folder::where('user_id', $userId)
            ->withCount('documents')
            ->orderBy('folder_name')
            ->get();
    }
}
