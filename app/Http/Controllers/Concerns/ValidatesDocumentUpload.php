<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Document;
use App\Models\Folder;
use App\Services\AcademicHierarchyService;
use App\Services\DocumentService;
use App\Support\IteSubjects;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

trait ValidatesDocumentUpload
{
    protected function validateDocumentUpload(Request $request): array
    {
        $folderId = $request->input('folder_id') ? (int) $request->input('folder_id') : null;
        $folder = $folderId ? Folder::find($folderId) : null;
        $hierarchy = app(AcademicHierarchyService::class);
        $category = app(DocumentService::class)->resolveCategoryForFolder($folderId);
        $isTypeLeafFolder = $folder
            && in_array($category, Document::SHAREABLE_CATEGORIES, true)
            && $hierarchy->isSemesterTypeLeafFolder($folder);
        $isCourseFolder = $folder && $hierarchy->isCourseSubfolder($folder);
        $isTgUploadLeaf = $folder && $hierarchy->isTgUploadLeafFolder($folder);
        $useCourseSelect = $isTypeLeafFolder;
        $user = auth()->user();

        $rules = [
            'document_type' => 'required|in:pdf,image,word',
            'documents' => 'required|array|max:3',
            'documents.*' => match ($request->input('document_type')) {
                'pdf' => 'required|file|max:10240|mimes:pdf|mimetypes:application/pdf',
                'word' => 'required|file|max:10240|mimes:doc,docx',
                default => 'required|file|max:10240|mimes:jpg,jpeg,png|mimetypes:image/jpeg,image/png',
            },
            'folder_id' => 'required|exists:folders,folder_id',
        ];

        if ($useCourseSelect) {
            $rules['subject'] = ['required', 'string', Rule::in(IteSubjects::labelsForUser($user))];
            $rules['document_title'] = 'required|string|max:13';
        } elseif ($isTgUploadLeaf) {
            $rules['document_title'] = 'required|string|max:13';
        } elseif ($isCourseFolder) {
            $rules['document_title'] = 'required|string|max:13';
        } else {
            $rules['document_title'] = 'required|string|max:13';
        }

        $isShareable = in_array($category, Document::SHAREABLE_CATEGORIES, true);

        if ($isShareable) {
            if ($user->canUploadSharedDocuments()) {
                $rules['recipient_ids'] = 'required|array|min:1';
                $rules['recipient_ids.*'] = 'integer|exists:users,id';
            }

            if (!$useCourseSelect && !$isCourseFolder && !$isTgUploadLeaf && IteSubjects::shouldUseSubjectPicker($user, true)) {
                $rules['subject'] = ['required', 'string', Rule::in(IteSubjects::labelsForUser($user))];
            }

            if ($category === 'Teaching Guides' && $isTgUploadLeaf) {
                $rules['documents.*'] = 'required|file|max:10240|mimes:pdf,doc,docx|mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            }

            if ($category === 'Exam Questionnaires') {
                $rules['exam_type'] = 'required|in:Quiz,Prelim,Midterm,Pre-Final,Final';
                $rules['documents.*'] = 'required|file|max:10240|mimes:pdf|mimetypes:application/pdf';
            }
        } else {
            $rules['tags'] = 'nullable|string|max:15';
        }

        $validated = $request->validate($rules);

        if ($isCourseFolder && empty($validated['subject'] ?? null)) {
            $validated['subject'] = $folder->folder_name;
        }

        if ($isTgUploadLeaf) {
            $validated['subject'] = $hierarchy->subjectLabelFromTgUploadFolder($folder)
                ?? $folder->parent?->folder_name;
        }

        return $validated;
    }
}
