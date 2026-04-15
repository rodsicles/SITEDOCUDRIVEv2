<?php

namespace App\Services;

use App\Models\Folder;
use App\Models\Document;
use App\Models\DashboardLog;
use Illuminate\Support\Collection;

class FolderService
{
    /**
     * Get the system folder tree: top-level categories with children eager-loaded.
     */
    public function getSystemFolderTree(): Collection
    {
        return Folder::system()
            ->topLevel()
            ->with(['children' => function ($query) {
                $query->system()->orderBy('sort_order')
                    ->withCount('documents')
                    ->with(['children' => function ($q) {
                        $q->system()->orderBy('sort_order')->withCount('documents');
                    }]);
            }])
            ->withCount('documents')
            ->orderBy('sort_order')
            ->get();
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
            $parent = Folder::findOrFail($parentId);
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
