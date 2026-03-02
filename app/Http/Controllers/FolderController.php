<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\Document;
use App\Http\Requests\StoreFolderRequest;
use App\Http\Requests\UpdateFolderRequest;
use App\Http\Requests\MoveDocumentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FolderController extends Controller
{
    /**
     * Store a newly created folder
     */
    public function store(StoreFolderRequest $request)
    {
        try {
            $folder = Folder::create([
                'user_id' => auth()->id(),
                'folder_name' => $request->folder_name,
                'color' => $request->color ?? '#028a0f',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Folder created successfully',
                'folder' => $folder,
            ]);
        } catch (\Exception $e) {
            \Log::error('Folder creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create folder: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified folder (rename)
     */
    public function update(UpdateFolderRequest $request, $id)
    {
        try {
            $folder = Folder::findOrFail($id);

            // Authorization check
            if ($folder->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized action.',
                ], 403);
            }

            $folder->update([
                'folder_name' => $request->folder_name,
                'color' => $request->color ?? $folder->color,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Folder renamed successfully',
                'folder' => $folder,
            ]);
        } catch (\Exception $e) {
            \Log::error('Folder update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update folder: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified folder
     */
    public function destroy($id)
    {
        try {
            $folder = Folder::findOrFail($id);

            // Authorization check
            if ($folder->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized action.',
                ], 403);
            }

            // Move all documents in this folder to "No Folder" (null)
            Document::where('folder_id', $id)->update(['folder_id' => null]);

            $folder->delete();

            return response()->json([
                'success' => true,
                'message' => 'Folder deleted successfully. Documents moved to Uncategorized.',
            ]);
        } catch (\Exception $e) {
            \Log::error('Folder deletion error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete folder: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Move a document to a folder
     */
    public function moveDocument(MoveDocumentRequest $request, $documentId)
    {
        $document = Document::findOrFail($documentId);

        // Authorization check - user must own the document
        if ($document->uploaded_by !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        // If folder_id is provided, verify ownership
        if ($request->folder_id) {
            $folder = Folder::findOrFail($request->folder_id);
            if ($folder->user_id !== auth()->id()) {
                abort(403, 'You do not own this folder.');
            }
        }

        $document->update([
            'folder_id' => $request->folder_id,
        ]);

        $folderName = $request->folder_id 
            ? Folder::find($request->folder_id)->folder_name 
            : 'Uncategorized';

        return response()->json([
            'success' => true,
            'message' => "Document moved to {$folderName}",
            'document' => $document,
        ]);
    }

    /**
     * Get all folders for the authenticated user (API endpoint)
     */
    public function getUserFolders()
    {
        $folders = Folder::where('user_id', auth()->id())
            ->withCount('documents')
            ->orderBy('folder_name')
            ->get();

        return response()->json([
            'success' => true,
            'folders' => $folders,
        ]);
    }
}
