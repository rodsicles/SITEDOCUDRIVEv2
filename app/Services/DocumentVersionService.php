<?php

namespace App\Services;

use App\Models\DashboardLog;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Support\DocumentNaming;
use App\Support\UploadStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Keeps a full history of a document's files.
 *
 * Replacing a file never deletes anything: the file that is live at the time of
 * the replacement is archived as a version row first, then the document is
 * pointed at the newly uploaded file.
 */
class DocumentVersionService
{
    public function nextVersionNumber(Document $document): int
    {
        return (int) DocumentVersion::where('document_id', $document->document_id)
            ->max('version_number') + 1;
    }

    /**
     * Upload a replacement file and archive the current one as a version.
     */
    public function uploadNewVersion(Document $document, UploadedFile $file, User $user, ?string $note = null): DocumentVersion
    {
        if (!$document->canManageVersions($user)) {
            abort(403, 'You are not allowed to replace this file.');
        }

        UploadStorage::assertPathAllowed($document->file_path);

        $directory = UploadStorage::uploadDirectoryForPath($document->file_path);
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = time() . '_v' . $this->nextVersionNumber($document) . '_' . $file->hashName();
        $storedPath = UploadStorage::storeAs($file, $directory, $filename);

        return DB::transaction(function () use ($document, $user, $note, $storedPath, $extension, $file) {
            $archived = $this->archiveCurrentFile($document, $user, $note);

            $document->update([
                'file_path' => $storedPath,
                'file_size' => $file->getSize(),
                'document_type' => $extension === 'pdf' ? 'pdf' : 'word',
            ]);

            DashboardLog::create([
                'user_id' => $user->id,
                'activity' => 'Uploaded new version of document: ' . $document->document_title,
                'activity_type' => 'document_version_uploaded',
                'visibility' => 'own',
            ]);

            return $archived;
        });
    }

    /**
     * Point the document back at an older version. The file that is currently
     * live is archived first so the restore itself is reversible.
     */
    public function restoreVersion(Document $document, int $versionId, User $user): DocumentVersion
    {
        if (!$document->canManageVersions($user)) {
            abort(403, 'You are not allowed to restore this file.');
        }

        $version = DocumentVersion::where('document_id', $document->document_id)
            ->findOrFail($versionId);

        UploadStorage::assertPathAllowed($version->file_path);

        if (!UploadStorage::exists($version->file_path)) {
            abort(404, 'The file for this version is no longer available in storage.');
        }

        return DB::transaction(function () use ($document, $version, $user) {
            $this->archiveCurrentFile($document, $user, 'Replaced by restore of v' . $version->version_number);

            $document->update([
                'file_path' => $version->file_path,
                'file_size' => $version->file_size,
                'document_type' => $version->document_type,
            ]);

            DashboardLog::create([
                'user_id' => $user->id,
                'activity' => "Restored version {$version->version_number} of document: " . $document->document_title,
                'activity_type' => 'document_version_restored',
                'visibility' => 'own',
            ]);

            return $version;
        });
    }

    /**
     * Download a specific archived version.
     */
    public function downloadVersion(Document $document, int $versionId, User $user)
    {
        if (!$document->canView($user)) {
            abort(403, 'Unauthorized access');
        }

        $version = DocumentVersion::where('document_id', $document->document_id)
            ->findOrFail($versionId);

        UploadStorage::assertPathAllowed($version->file_path);

        if (!UploadStorage::exists($version->file_path)) {
            abort(404, 'The file for this version is no longer available in storage.');
        }

        $title = ($version->document_title ?: $document->document_title) . ' (v' . $version->version_number . ')';

        return UploadStorage::downloadResponse(
            $version->file_path,
            DocumentNaming::downloadFilename($title, $version->file_path),
        );
    }

    /**
     * Snapshot the document's current file into the version history.
     */
    private function archiveCurrentFile(Document $document, User $user, ?string $note): DocumentVersion
    {
        return DocumentVersion::create([
            'document_id' => $document->document_id,
            'version_number' => $this->nextVersionNumber($document),
            'document_title' => $document->document_title,
            'file_path' => $document->file_path,
            'file_size' => $document->file_size,
            'document_type' => $document->document_type,
            'uploaded_by' => $user->id,
            'note' => $note,
        ]);
    }
}
