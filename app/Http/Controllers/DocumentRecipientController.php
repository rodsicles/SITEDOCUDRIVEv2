<?php

namespace App\Http\Controllers;

use App\Services\DocumentService;
use Illuminate\Http\Request;

class DocumentRecipientController extends Controller
{
    public function __construct(protected DocumentService $documentService) {}

    public function search(Request $request)
    {
        if (!auth()->user()->canUploadSharedDocuments()) {
            abort(403);
        }

        $validated = $request->validate([
            'q' => 'nullable|string|max:100',
        ]);

        $users = $this->documentService->searchRecipients($validated['q'] ?? '');

        return response()->json([
            'results' => $users->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->employee->full_name ?? $user->username,
                'email' => $user->username,
                'role' => $user->role->role_name ?? '',
            ]),
        ]);
    }
}
