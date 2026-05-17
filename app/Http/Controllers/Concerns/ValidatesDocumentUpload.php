<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Document;
use App\Services\DocumentService;
use App\Support\IteSubjects;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'folder_id' => 'required|exists:folders,folder_id',
        ];

        $isShareable = in_array($category, Document::SHAREABLE_CATEGORIES, true);
        $user = auth()->user();

        if ($isShareable) {
            if ($user->canUploadSharedDocuments()) {
                $rules['recipient_ids'] = 'required|array|min:1';
                $rules['recipient_ids.*'] = 'integer|exists:users,id';
            }

            if (IteSubjects::shouldUseSubjectPicker($user, true)) {
                $rules['subject'] = ['required', 'string', Rule::in(IteSubjects::labels())];
            }
        } else {
            $rules['tags'] = 'nullable|string|max:15';
        }

        return $request->validate($rules);
    }
}
