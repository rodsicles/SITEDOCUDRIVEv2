<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Document;
use App\Models\Folder;
use App\Services\AcademicHierarchyService;
use App\Services\DocumentService;
use App\Support\DocumentNaming;
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
        $isEqUploadLeaf = $folder && $hierarchy->isEqUploadLeafFolder($folder);
        $useCourseSelect = $isTypeLeafFolder;
        $user = auth()->user();

        $titleMax = DocumentNaming::TITLE_MAX_LENGTH;

        $rules = [
            'document_type' => 'required|in:pdf,word,image',
            'documents' => 'required|array|max:3',
            'documents.*' => match ($request->input('document_type')) {
                'pdf'   => 'required|file|max:10240|mimes:pdf|mimetypes:application/pdf',
                'word'  => 'required|file|max:10240|mimes:doc,docx',
                'image' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,webp|mimetypes:image/jpeg,image/png,image/gif,image/webp',
                default => 'required|file|max:10240|mimes:doc,docx',
            },
            'folder_id' => 'required|exists:folders,folder_id',
        ];

        // Document Title is optional — blank falls back to each uploaded file's original name.
        $rules['document_title'] = 'nullable|string|max:'.$titleMax;

        if ($useCourseSelect) {
            $rules['subject'] = ['required', 'string', Rule::in(IteSubjects::labelsForUser($user))];
        }

        $isShareable = in_array($category, Document::SHAREABLE_CATEGORIES, true);

        if ($isShareable) {
            // Teaching Guides / Exam Questionnaires: PDF & Word only
            $rules['document_type'] = 'required|in:pdf,word';
            $rules['documents.*'] = 'required|file|max:10240|mimes:pdf,doc,docx|mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document';

            if ($user->canUploadSharedDocuments()) {
                $rules['recipient_ids'] = 'required|array|min:1';
                $rules['recipient_ids.*'] = 'integer|exists:users,id';
            }

            if (!$useCourseSelect && !$isCourseFolder && !$isTgUploadLeaf && !$isEqUploadLeaf && IteSubjects::shouldUseSubjectPicker($user, true)) {
                $rules['subject'] = ['required', 'string', Rule::in(IteSubjects::labelsForUser($user))];
            }

            if ($category === 'Exam Questionnaires' && !$isEqUploadLeaf) {
                $rules['exam_type'] = 'required|in:Quiz,Prelim,Midterm,Pre-Final,Final';
            }
        } else {
            $rules['tags'] = 'nullable|string|max:15';
        }

        $messages = [
            'documents.*.mimes' => 'This folder only accepts PDF or Word files (.pdf, .doc, .docx). Images are not allowed here.',
            'documents.*.mimetypes' => 'This folder only accepts PDF or Word files (.pdf, .doc, .docx). Images are not allowed here.',
            'document_type.in' => 'Please choose PDF or Word for this folder.',
            'documents.required' => 'Please choose at least one file to upload.',
            'documents.max' => 'You can upload a maximum of 3 files at a time.',
            'documents.*.max' => 'Each file must be 10 MB or smaller.',
        ];

        $validated = $request->validate($rules, $messages);

        if ($isCourseFolder && empty($validated['subject'] ?? null)) {
            $validated['subject'] = $folder->folder_name;
        }

        if ($isTgUploadLeaf) {
            $validated['subject'] = $hierarchy->subjectLabelFromTgUploadFolder($folder)
                ?? $folder->parent?->folder_name;
        }

        if ($isEqUploadLeaf) {
            $validated['subject'] = $hierarchy->subjectLabelFromEqUploadFolder($folder)
                ?? $folder->parent?->folder_name;
            $validated['exam_type'] = $hierarchy->examTypeFromEqUploadFolder($folder);
        }

        return $validated;
    }
}
