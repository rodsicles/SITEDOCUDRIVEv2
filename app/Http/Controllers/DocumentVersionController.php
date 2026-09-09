<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\DocumentService;
use App\Support\UploadStorage;
use App\Support\DocumentNaming;
use Illuminate\Http\Request;

class DocumentVersionController extends Controller
{
    /**
     * Upload a new version of an existing document.
     * Snapshots the current file into document_versions, then replaces the live record.
     */
    public function store(Request $request, $documentId)
    {
        $document = Document::findOrFail($documentId);
        $user     = auth()->user();

        if (!app(DocumentService::class)->userCanVersionDocument($document, $user)) {
            abort(403, 'You are not allowed to upload a new version of this document.');
        }

        $request->validate([
            'file'  => 'required|file|max:20480|mimes:pdf,doc,docx,jpg,jpeg,png,gif,webp',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            app(DocumentService::class)->uploadNewVersion(
                $document,
                $request->file('file'),
                $user,
                $request->input('notes')
            );

            return redirect()->back()->with('success', 'New version uploaded and renamed successfully.');
        } catch (\Exception $e) {
            \Log::error('[DocumentVersionController] Version upload failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to upload new version. Please try again.');
        }
    }

    /**
     * In-app preview of an archived version (read-only).
     */
    public function viewVersion(Request $request, $versionId)
    {
        $version = DocumentVersion::with(['document.folder.parent.parent', 'document.uploader'])->findOrFail($versionId);
        $user    = auth()->user();
        $document = $version->document;

        if (!$document || !$document->canView($user)) {
            abort(403, 'Unauthorized access to this version.');
        }

        UploadStorage::assertPathAllowed($version->file_path);

        if (!UploadStorage::exists($version->file_path)) {
            return back()->with('error', 'This version\'s file no longer exists in storage.');
        }

        $displayTitle = DocumentNaming::titleWithVersion(
            (string) ($version->document_title ?: $document->document_title),
            (int) $version->version_number,
        );

        if ($request->boolean('stream')) {
            $mime = UploadStorage::mimeType($version->file_path) ?? 'application/octet-stream';

            return UploadStorage::inlineResponse(
                $version->file_path,
                DocumentNaming::downloadFilename($displayTitle, $version->file_path),
                $mime,
            );
        }

        $routePrefix = match (true) {
            $user->isFaculty() => 'faculty',
            $user->isProgramCoordinator() => 'coordinator',
            default => 'dean',
        };

        $fileMime = UploadStorage::mimeType($version->file_path) ?? '';

        return view('submissions.file-preview', [
            'title'            => $displayTitle,
            'folderPath'       => 'Archived version · '.$document->document_title,
            'streamUrl'        => route('document-versions.view', ['id' => $versionId, 'stream' => 1]),
            'downloadUrl'      => route('document-versions.download', $versionId),
            'backUrl'          => route($routePrefix.'.view-document', $document->document_id),
            'isImage'          => str_starts_with($fileMime, 'image/'),
            'fileMime'         => $fileMime,
            'canVersion'       => false,
            'canCopy'          => false,
            'isArchivedVersion'=> true,
            'currentVersion'   => (int) $version->version_number,
            'versions'         => collect(),
            'viewers'          => null,
        ]);
    }

    /**
     * Download a specific historical version (shared route, auth + ownership check).
     */
    public function downloadVersion($versionId)
    {
        $version = DocumentVersion::with(['document', 'document.uploader'])->findOrFail($versionId);
        $user    = auth()->user();

        if (!$version->document->canView($user)) {
            abort(403, 'Unauthorized access to this version.');
        }

        UploadStorage::assertPathAllowed($version->file_path);

        if (!UploadStorage::exists($version->file_path)) {
            return back()->with('error', 'This version\'s file no longer exists in storage.');
        }

        $downloadName = DocumentNaming::downloadFilename(
            DocumentNaming::titleWithVersion(
                (string) ($version->document_title ?: $version->document->document_title),
                (int) $version->version_number,
            ),
            $version->file_path,
        );

        return UploadStorage::downloadResponse($version->file_path, $downloadName);
    }
}
