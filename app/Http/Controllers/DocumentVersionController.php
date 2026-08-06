<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentVersionService;
use App\Support\UploadStorageException;
use Illuminate\Http\Request;

class DocumentVersionController extends Controller
{
    public function __construct(protected DocumentVersionService $versionService) {}

    public function store(Request $request, int $id)
    {
        $document = Document::findOrFail($id);

        $validated = $request->validate([
            'file' => 'required|file|max:10240|mimes:pdf,doc,docx',
            'note' => 'nullable|string|max:255',
        ]);

        try {
            $this->versionService->uploadNewVersion(
                $document,
                $request->file('file'),
                auth()->user(),
                $validated['note'] ?? null,
            );
        } catch (UploadStorageException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'New version uploaded. The previous file is kept in the version history.');
    }

    public function restore(int $id, int $versionId)
    {
        $document = Document::findOrFail($id);

        $version = $this->versionService->restoreVersion($document, $versionId, auth()->user());

        return back()->with('success', "Version {$version->version_number} restored as the current file.");
    }

    public function download(int $id, int $versionId)
    {
        $document = Document::findOrFail($id);

        return $this->versionService->downloadVersion($document, $versionId, auth()->user());
    }
}
