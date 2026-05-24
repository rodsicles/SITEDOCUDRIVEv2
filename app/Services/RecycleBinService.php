<?php

namespace App\Services;

use App\Models\DashboardLog;
use App\Models\Document;
use App\Models\Folder;
use App\Models\User;
use App\Support\UploadStorage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RecycleBinService
{
    public function __construct(
        protected NotificationService $notificationService,
    ) {}

    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->baseQuery($user)
            ->latest('deleted_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function moveToRecycleBin(Document $document, User $actor): void
    {
        $document->trashed_folder_id = $document->folder_id;
        $document->deleted_by = $actor->id;
        $document->save();

        $document->delete();
    }

    public function restore(int $documentId, User $user): array
    {
        $document = Document::onlyTrashed()->findOrFail($documentId);

        if (!$this->canRestore($document, $user)) {
            abort(403, 'You cannot restore this document.');
        }

        $targetFolderId = $document->trashed_folder_id;
        $folderMissing = false;

        if ($targetFolderId && !Folder::where('folder_id', $targetFolderId)->exists()) {
            $targetFolderId = null;
            $folderMissing = true;
        }

        $document->folder_id = $targetFolderId;
        $document->trashed_folder_id = null;
        $document->deleted_by = null;
        $document->restore();

        DashboardLog::create([
            'user_id' => $user->id,
            'activity' => 'Restored document from Recycle Bin: ' . $document->document_title,
            'activity_type' => 'document_restored',
            'visibility' => $user->isDeanOrSecretary() ? 'dean' : 'own',
        ]);

        return [
            'document' => $document,
            'folder_missing' => $folderMissing,
        ];
    }

    public function forceDelete(int $documentId, User $user): void
    {
        if (!$user->isDean()) {
            abort(403, 'Only the Dean can permanently delete documents.');
        }

        $document = Document::onlyTrashed()->findOrFail($documentId);

        $this->notificationService->notifyDocumentPermanentlyDeleted($document, $user);

        if ($document->file_path && UploadStorage::exists($document->file_path)) {
            UploadStorage::delete($document->file_path);
        }

        DashboardLog::create([
            'user_id' => $user->id,
            'activity' => 'Permanently deleted document: ' . $document->document_title,
            'activity_type' => 'document_force_deleted',
            'visibility' => 'dean',
        ]);

        $document->forceDelete();
    }

    /**
     * @param  list<int>  $documentIds
     * @return int Number permanently deleted
     */
    public function bulkForceDelete(array $documentIds, User $user): int
    {
        if (!$user->isDean()) {
            abort(403, 'Only the Dean can permanently delete documents.');
        }

        $documentIds = array_values(array_unique(array_map('intval', $documentIds)));
        $documentIds = array_filter($documentIds, fn ($id) => $id > 0);

        $deleted = 0;

        foreach ($documentIds as $documentId) {
            $document = Document::onlyTrashed()->find($documentId);
            if (!$document) {
                continue;
            }

            $this->forceDelete($documentId, $user);
            $deleted++;
        }

        return $deleted;
    }

    public function canRestore(Document $document, User $user): bool
    {
        if ($user->isDeanOrSecretary()) {
            return true;
        }

        return (int) $document->deleted_by === (int) $user->id
            || (int) $document->uploaded_by === (int) $user->id;
    }

    public function canViewInRecycleBin(Document $document, User $user): bool
    {
        if ($user->isDeanOrSecretary()) {
            return true;
        }

        return (int) $document->deleted_by === (int) $user->id;
    }

    protected function baseQuery(User $user)
    {
        $query = Document::onlyTrashed()
            ->with([
                'uploader.employee',
                'deletedBy.employee',
                'trashedFolder.parent.parent',
            ]);

        if ($user->isDeanOrSecretary()) {
            return $query;
        }

        return $query->where('deleted_by', $user->id);
    }
}
