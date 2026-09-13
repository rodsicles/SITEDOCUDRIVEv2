<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFolderRequest;
use App\Http\Requests\UpdateFolderRequest;
use App\Http\Requests\MoveDocumentRequest;
use App\Models\Document;
use App\Models\Folder;
use App\Services\DocumentService;
use App\Services\FolderService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FolderController extends Controller
{
    public function __construct(
        protected FolderService $folderService
    ) {}

    public function store(StoreFolderRequest $request)
    {
        try {
            $folder = $this->folderService->createFolder(
                auth()->id(),
                $request->folder_name,
                $request->color ?? '#028a0f',
                $request->parent_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Folder created successfully',
                'folder' => $folder,
            ]);
        } catch (\Exception $e) {
            \Log::error('Folder creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create folder. Please try again.',
            ], 500);
        }
    }

    public function update(UpdateFolderRequest $request, $id)
    {
        try {
            $folder = $this->folderService->updateFolder($id, auth()->id(), $request->folder_name, $request->color);

            return response()->json([
                'success' => true,
                'message' => 'Folder renamed successfully',
                'folder' => $folder,
            ]);
        } catch (HttpException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getStatusCode());
        } catch (\Exception $e) {
            \Log::error('Folder update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update folder. Please try again.',
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->folderService->deleteFolder($id, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Folder deleted. Files inside were moved to the Recycle Bin.',
            ]);
        } catch (HttpException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getStatusCode());
        } catch (\Exception $e) {
            \Log::error('Folder deletion error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete folder. Please try again.',
            ], 500);
        }
    }

    public function moveDocument(MoveDocumentRequest $request, $documentId)
    {
        $folderName = $this->folderService->moveDocument($documentId, auth()->id(), $request->folder_id);

        return response()->json([
            'success' => true,
            'message' => "Document moved to {$folderName}",
        ]);
    }

    public function getUserFolders()
    {
        $folders = $this->folderService->getUserFolders(auth()->id());

        return response()->json([
            'success' => true,
            'folders' => $folders,
        ]);
    }

    public function privacy(Request $request, Folder $folder)
    {
        $validated = $request->validate(['private' => ['required', 'boolean']]);

        try {
            $folder = $this->folderService->setPrivacy($folder, $request->user(), (bool) $validated['private']);

            return response()->json([
                'success' => true,
                'private' => $folder->is_private,
                'message' => $folder->is_private
                    ? 'Folder is now private. Only you can access its contents.'
                    : 'Folder privacy removed.',
            ]);
        } catch (HttpException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getStatusCode());
        }
    }

    /**
     * Copy a document to another folder (creates a new physical file + DB record).
     */
    public function copyDocument(Request $request, $documentId)
    {
        $request->validate([
            'folder_id' => 'nullable|exists:folders,folder_id',
        ]);

        $document = Document::findOrFail($documentId);
        $user     = auth()->user();

        abort_unless($document->canView($user), 404);
        if ($request->folder_id) {
            Folder::visibleTo($user)->findOrFail((int) $request->folder_id);
        }

        $copy = app(DocumentService::class)->copyDocument(
            $document,
            $request->folder_id ? (int) $request->folder_id : null,
            $user
        );

        $folderName = $request->folder_id
            ? (Folder::find((int) $request->folder_id)?->folder_name ?? 'Destination')
            : 'Documents';

        return response()->json([
            'success' => true,
            'message' => "Document copied to \"{$folderName}\" successfully.",
        ]);
    }
}
