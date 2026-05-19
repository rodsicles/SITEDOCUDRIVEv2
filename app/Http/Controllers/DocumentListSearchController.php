<?php

namespace App\Http\Controllers;

use App\Services\DocumentService;
use Illuminate\Http\Request;

class DocumentListSearchController extends Controller
{
    public function __construct(protected DocumentService $documentService) {}

    public function suggest(Request $request)
    {
        $validated = $request->validate([
            'q' => 'required|string|min:3|max:80',
            'category' => 'nullable|string|max:100',
            'folder' => 'nullable|string|max:50',
        ]);

        $titles = $this->documentService->suggestDocumentTitles(
            $request->user(),
            $validated['category'] ?? null,
            $validated['folder'] ?? $request->query('folder'),
            $request->query(),
            $validated['q'],
        );

        return response()->json([
            'results' => array_map(fn (string $title) => ['title' => $title], $titles),
        ]);
    }
}
