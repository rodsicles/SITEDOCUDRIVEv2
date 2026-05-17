<?php

namespace App\Services;

use App\Models\Document;
use App\Models\ExamQuestionnaire;
use App\Models\Folder;
use App\Support\UploadStorage;

class ExamQuestionnaireSyncService
{
    /**
     * Map exam_type values to system folder names (Prelims, Midterms, Finals).
     */
    public function examFolderSlugSegment(string $examType): string
    {
        return match ($examType) {
            'Prelim' => 'prelims',
            'Midterm' => 'midterms',
            'Final', 'Pre-Final' => 'finals',
            'Quiz' => 'prelims',
            default => 'prelims',
        };
    }

    /**
     * Resolve the Exam Questionnaires subfolder for semester / AY / exam type.
     */
    public function resolveTargetFolder(ExamQuestionnaire $questionnaire): ?Folder
    {
        if (!preg_match('/^(\d{4})-(\d{4})$/', $questionnaire->academic_year, $matches)) {
            return null;
        }

        $startYear = (int) $matches[1];
        $endYear = (int) $matches[2];
        $semester = match ($questionnaire->semester) {
            '2nd' => '2nd',
            'Summer' => null,
            default => '1st',
        };

        if ($semester === null) {
            return null;
        }

        $examSegment = $this->examFolderSlugSegment($questionnaire->exam_type);
        $slug = "eq-{$semester}-{$startYear}-{$endYear}-{$examSegment}";

        return Folder::where('slug', $slug)->where('is_system', true)->first();
    }

    /**
     * Mirror an approved exam questionnaire into the Documents folder tree.
     */
    public function syncToDocument(ExamQuestionnaire $questionnaire): ?Document
    {
        if ($questionnaire->status !== 'approved') {
            return null;
        }

        $folder = $this->resolveTargetFolder($questionnaire);
        if (!$folder) {
            return null;
        }

        $fileSize = 0;
        if ($questionnaire->file_path && UploadStorage::exists($questionnaire->file_path)) {
            $fileSize = (int) UploadStorage::size($questionnaire->file_path);
        }

        $document = Document::updateOrCreate(
            ['document_id' => $questionnaire->document_id],
            [
                'uploaded_by' => $questionnaire->submitted_by,
                'folder_id' => $folder->folder_id,
                'document_title' => mb_substr($questionnaire->title, 0, 13),
                'subject' => $questionnaire->subject,
                'file_path' => $questionnaire->file_path,
                'file_size' => $fileSize,
                'document_type' => $questionnaire->file_type === 'word' ? 'word' : 'pdf',
                'category' => 'Exam Questionnaires',
                'tags' => 'exam-questionnaire',
            ]
        );

        if (!$questionnaire->document_id) {
            $questionnaire->update(['document_id' => $document->document_id]);
        }

        return $document;
    }
}
