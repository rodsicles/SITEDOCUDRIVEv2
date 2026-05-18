<?php

namespace App\Services;

use App\Models\Folder;
use App\Models\Document;
use App\Models\DashboardLog;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Support\Collection;

class FolderService
{
    /**
     * Get the system folder tree: top-level categories with children eager-loaded.
     */
    public function getSystemFolderTree(?User $viewer = null): Collection
    {
        app(AcademicHierarchyService::class)->ensureActiveSchoolYearStructures();

        $activeSchoolYearId = SchoolYear::activeId();

        $documentCount = function ($query) use ($viewer) {
            if ($viewer) {
                $query->visibleTo($viewer);
            }
        };

        $tree = Folder::system()
            ->topLevel()
            ->with(['children' => function ($query) use ($documentCount, $activeSchoolYearId) {
                $query->system()->orderBy('sort_order')
                    ->when($activeSchoolYearId, fn ($q) => $q->where('school_year_id', $activeSchoolYearId))
                    ->withCount(['documents' => $documentCount])
                    ->with(['children' => function ($q) use ($documentCount) {
                        $q->system()->orderBy('sort_order')->withCount(['documents' => $documentCount]);
                    }]);
            }])
            ->withCount(['documents' => $documentCount])
            ->orderBy('sort_order')
            ->get();

        return $tree->map(function (Folder $category) use ($activeSchoolYearId) {
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
     * System subfolders for navigation, scoped to active school year when applicable.
     */
    public function getDisplayFolders(Folder $parent, ?User $viewer = null): Collection
    {
        $documentCount = function ($query) use ($viewer) {
            if ($viewer) {
                $query->visibleTo($viewer);
            }
        };

        $query = $parent->children()->system()->orderBy('sort_order')
            ->withCount(['documents' => $documentCount]);

        $activeSchoolYearId = SchoolYear::activeId();
        $hierarchy = app(AcademicHierarchyService::class);

        if ($activeSchoolYearId && $hierarchy->isTeachingGuidesOrExamCategory($parent)) {
            $query->where('school_year_id', $activeSchoolYearId);
        }

        $folders = $query->get();

        return $this->attachSubtreeDocumentCounts($folders, $viewer);
    }

    /**
     * Replace documents_count with total visible files in folder + all descendants.
     */
    public function attachSubtreeDocumentCounts(Collection $folders, ?User $viewer): Collection
    {
        return $folders->map(function (Folder $folder) use ($viewer) {
            $folder->loadMissing(['children.children']);

            $folderIds = array_merge([$folder->folder_id], $folder->getDescendantIds());
            $query = Document::query()->whereIn('folder_id', $folderIds);

            if ($viewer) {
                $query->visibleTo($viewer);
            }

            $folder->setAttribute('documents_count', $query->count());

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

        if ($document->uploaded_by !== $userId) {
            abort(403, 'Unauthorized action.');
        }

        if ($folderId) {
            Folder::findOrFail($folderId);
        }

        $document->update(['folder_id' => $folderId]);

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

    /**
     * Update a folder's name and/or color. Verifies ownership.
     */
    public function updateFolder(int $folderId, int $userId, string $folderName, ?string $color = null): Folder
    {
        $folder = Folder::where('folder_id', $folderId)
            ->where('user_id', $userId)
            ->where('is_system', false)
            ->firstOrFail();

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
     * Delete a folder. Moves its documents to Uncategorized. Verifies ownership.
     */
    public function deleteFolder(int $folderId, int $userId): void
    {
        $folder = Folder::where('folder_id', $folderId)
            ->where('user_id', $userId)
            ->where('is_system', false)
            ->firstOrFail();

        $folderName = $folder->folder_name;

        // Move all documents in this folder (and descendant folders) to uncategorized
        $descendantIds = $folder->getDescendantIds();
        $allFolderIds = array_merge([$folderId], $descendantIds);

        Document::whereIn('folder_id', $allFolderIds)->update(['folder_id' => null]);

        // Delete descendant folders first, then the folder itself
        if (!empty($descendantIds)) {
            Folder::whereIn('folder_id', $descendantIds)->delete();
        }

        $folder->delete();

        DashboardLog::create([
            'user_id' => $userId,
            'activity' => "Deleted folder: {$folderName}",
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
