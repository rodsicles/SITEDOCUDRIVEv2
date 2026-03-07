<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFolderRequest;
use App\Http\Requests\UpdateFolderRequest;
use App\Http\Requests\MoveDocumentRequest;
use App\Services\FolderService;
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
                $request->color ?? '#028a0f'
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
                'message' => 'Folder deleted successfully. Documents moved to Uncategorized.',
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
}
