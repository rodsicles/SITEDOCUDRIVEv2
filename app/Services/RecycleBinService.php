<?php

namespace App\Services;

use App\Models\DashboardLog;
use App\Models\Document;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RecycleBinService
{
    public function __construct(
        protected NotificationService $notificationService,
        protected DocumentPurgeService $documentPurge,
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
        $this->assertCanPermanentlyDelete($document);

        $this->notificationService->notifyDocumentPermanentlyDeleted($document, $user);

        DashboardLog::create([
            'user_id' => $user->id,
            'activity' => 'Permanently deleted document: ' . $document->document_title,
            'activity_type' => 'document_force_deleted',
            'visibility' => 'dean',
        ]);

        $this->documentPurge->purge($document);
    }

    public function forceDeleteActive(int $documentId, User $user): void
    {
        abort_unless($user->isDean(), 403);
        $document = Document::findOrFail($documentId);
        abort_unless($document->canView($user), 404);
        $this->assertCanPermanentlyDelete($document);
        $this->moveToRecycleBin($document, $user);
        $this->forceDelete($documentId, $user);
    }

    public function permanentDeleteImpact(Document $document): array
    {
        $id = $document->document_id;
        $impact = [
            'title' => $document->document_title,
            'request_submissions' => DB::table('document_request_recipients')->where('submitted_document_id', $id)->count(),
            'teaching_guides' => DB::table('teaching_guides')->where('document_id', $id)->count(),
            'exam_questionnaires' => DB::table('exam_questionnaires')->where('document_id', $id)->count(),
            'saved_versions' => DB::table('document_versions')->where('document_id', $id)->count(),
            'stored_file' => $document->file_path ? 1 : 0,
        ];
        $path = $document->file_path;
        $impact['shared_file_records'] = 0;
        if ($path) {
            $impact['shared_file_records'] = Document::withTrashed()->where('document_id', '!=', $id)->where('file_path', $path)->count()
                + DB::table('teaching_guides')->where('file_path', $path)->count()
                + DB::table('exam_questionnaires')->where('file_path', $path)->count()
                + DB::table('reports')->where('file_path', $path)->count()
                + DB::table('task_attachments')->where('file_path', $path)->count()
                + DB::table('professional_developments')->where('certificate_path', $path)->count();
        }
        $impact['blocked'] = array_sum(array_intersect_key($impact, array_flip([
            'request_submissions', 'teaching_guides', 'exam_questionnaires', 'saved_versions', 'shared_file_records',
        ]))) > 0;
        return $impact;
    }

    private function assertCanPermanentlyDelete(Document $document): void
    {
        abort_if($this->permanentDeleteImpact($document)['blocked'], 422,
            'This file has a request submission, academic record, saved version, or shared stored file. Resolve those links before permanent deletion.');
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
