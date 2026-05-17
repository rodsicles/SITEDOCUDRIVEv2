<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Folder;
use App\Models\TeachingGuide;
use App\Models\User;

class TeachingGuideSyncService
{
    public function semesterFromFolder(Folder $folder): string
    {
        $name = $folder->folder_name . ($folder->parent ? ' ' . $folder->parent->folder_name : '');
        if (str_contains($name, '1st')) {
            return '1st';
        }
        if (str_contains($name, '2nd')) {
            return '2nd';
        }

        return '1st';
    }

    public function academicYearFromFolder(Folder $folder): string
    {
        $text = $folder->folder_name . ' ' . ($folder->parent?->folder_name ?? '');
        if (preg_match('/(\d{4})-(\d{4})/', $text, $m)) {
            return $m[1] . '-' . $m[2];
        }
        $y = now()->month >= 8 ? now()->year : now()->year - 1;

        return $y . '-' . ($y + 1);
    }

    /**
     * Create or update a teaching guide row linked to a documents-tab upload.
     */
    public function syncFromDocument(Document $document, Folder $folder, array $recipientIds = [], ?string $subject = null): ?TeachingGuide
    {
        if (!in_array($document->document_type, ['pdf', 'word'], true)) {
            return null;
        }

        $guide = TeachingGuide::updateOrCreate(
            ['document_id' => $document->document_id],
            [
                'user_id' => $document->uploaded_by,
                'title' => $document->document_title,
                'file_path' => $document->file_path,
                'file_type' => $document->document_type === 'pdf' ? 'pdf' : 'word',
                'subject' => $subject ?? $document->document_title,
                'folder_id' => $document->folder_id,
                'semester' => $this->semesterFromFolder($folder),
                'academic_year' => $this->academicYearFromFolder($folder),
            ]
        );

        $this->syncRecipients($guide, $recipientIds);

        return $guide;
    }

    /**
     * Mirror a teaching-guides page upload into the documents table for folder navigation.
     */
    public function syncDocumentFromGuide(TeachingGuide $guide, User $uploader, array $recipientIds = []): Document
    {
        $document = Document::updateOrCreate(
            ['document_id' => $guide->document_id],
            [
                'uploaded_by' => $guide->user_id,
                'folder_id' => $guide->folder_id,
                'document_title' => mb_substr($guide->title, 0, 13),
                'file_path' => $guide->file_path,
                'file_size' => 0,
                'document_type' => $guide->file_type,
                'category' => 'Teaching Guides',
                'tags' => 'teaching-guide',
            ]
        );

        if (!$guide->document_id) {
            $guide->update(['document_id' => $document->document_id]);
        }

        $document->recipients()->sync($recipientIds);
        $this->syncRecipients($guide, $recipientIds);

        return $document;
    }

    public function syncRecipients(TeachingGuide $guide, array $recipientIds): void
    {
        $recipientIds = array_values(array_unique(array_filter(array_map('intval', $recipientIds))));
        $guide->recipients()->sync($recipientIds);
    }
}
