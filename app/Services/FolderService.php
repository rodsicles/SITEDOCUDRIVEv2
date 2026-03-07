<?php

namespace App\Services;

use App\Models\Folder;
use App\Models\Document;

class FolderService
{
    /**
     * Create a new folder for a user.
     */
    public function createFolder(int $userId, string $folderName, string $color = '#028a0f'): Folder
    {
        return Folder::create([
            'user_id' => $userId,
            'folder_name' => $folderName,
            'color' => $color,
        ]);
    }

    /**
     * Update (rename) a folder. Checks ownership.
     */
    public function updateFolder(int $folderId, int $userId, string $folderName, ?string $color = null): Folder
    {
        $folder = Folder::findOrFail($folderId);

        if ($folder->user_id !== $userId) {
            abort(403, 'Unauthorized action.');
        }

        $folder->update([
            'folder_name' => $folderName,
            'color' => $color ?? $folder->color,
        ]);

        return $folder;
    }

    /**
     * Delete a folder and move its documents to uncategorized. Checks ownership.
     */
    public function deleteFolder(int $folderId, int $userId): void
    {
        $folder = Folder::findOrFail($folderId);

        if ($folder->user_id !== $userId) {
            abort(403, 'Unauthorized action.');
        }

        Document::where('folder_id', $folderId)->update(['folder_id' => null]);
        $folder->delete();
    }

    /**
     * Move a document to a folder (or null for uncategorized). Checks ownership.
     */
    public function moveDocument(int $documentId, int $userId, ?int $folderId): string
    {
        $document = Document::findOrFail($documentId);

        if ($document->uploaded_by !== $userId) {
            abort(403, 'Unauthorized action.');
        }

        if ($folderId) {
            $folder = Folder::findOrFail($folderId);
            if ($folder->user_id !== $userId) {
                abort(403, 'You do not own this folder.');
            }
        }

        $document->update(['folder_id' => $folderId]);

        return $folderId
            ? Folder::find($folderId)->folder_name
            : 'Uncategorized';
    }

    /**
     * Get all folders for a user with document counts.
     */
    public function getUserFolders(int $userId)
    {
        return Folder::where('user_id', $userId)
            ->withCount('documents')
            ->orderBy('folder_name')
            ->get();
    }
}
