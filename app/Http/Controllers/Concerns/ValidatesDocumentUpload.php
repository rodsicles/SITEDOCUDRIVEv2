<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Http\Request;

trait ValidatesDocumentUpload
{
    protected function validateDocumentUpload(Request $request): array
    {
        $category = app(DocumentService::class)->resolveCategoryForFolder(
            $request->input('folder_id') ? (int) $request->input('folder_id') : null
        );

        $rules = [
            'document_title' => 'required|string|max:13',
            'document_type' => 'required|in:pdf,image,word',
            'documents' => 'required|array|max:3',
            'documents.*' => match ($request->input('document_type')) {
                'pdf' => 'required|file|max:10240|mimes:pdf|mimetypes:application/pdf',
                'word' => 'required|file|max:10240|mimes:doc,docx',
                default => 'required|file|max:10240|mimes:jpg,jpeg,png|mimetypes:image/jpeg,image/png',
            },
            'tags' => 'nullable|string|max:15',
            'folder_id' => 'required|exists:folders,folder_id',
            'subject' => 'nullable|string|max:100',
        ];

        if (
            in_array($category, Document::SHAREABLE_CATEGORIES, true)
            && auth()->user()->canUploadSharedDocuments()
        ) {
            $rules['recipient_ids'] = 'required|array|min:1';
            $rules['recipient_ids.*'] = 'integer|exists:users,id';
        }

        return $request->validate($rules);
    }
}
