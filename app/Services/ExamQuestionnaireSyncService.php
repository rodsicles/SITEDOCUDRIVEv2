<?php

namespace App\Services;

use App\Models\Document;
use App\Models\ExamQuestionnaire;
use App\Models\Folder;
use App\Models\SchoolYear;
use App\Support\UploadStorage;

class ExamQuestionnaireSyncService
{
    public function __construct(
        protected AcademicHierarchyService $hierarchy,
    ) {}

    public function examFolderSlugSegment(string $examType): string
    {
        return $this->hierarchy->examTypeToAssessmentSlug($examType);
    }

    public function submissionTypeFromFolder(Folder $folder): string
    {
        if ($this->hierarchy->isEqUploadLeafFolder($folder)) {
            return strtoupper(trim((string) $folder->folder_name)) === 'TOS' ? 'tos' : 'toq';
        }

        if ($this->hierarchy->isCourseSubfolder($folder) && $folder->parent) {
            $folder = $folder->parent;
        }

        $slug = strtolower($folder->slug ?? '');
        $name = strtolower($folder->folder_name ?? '');

        if (str_contains($slug, '-tos') || str_contains($name, 'tos')) {
            return 'tos';
        }

        return 'toq';
    }

    public function semesterFromFolder(Folder $folder): string
    {
        $current = $folder;
        while ($current) {
            if ($this->hierarchy->isEqSemesterFolder($current) || $this->hierarchy->isTgSemesterFolder($current)) {
                return str_contains($current->folder_name, '2nd') ? '2nd' : '1st';
            }
            $current = $current->parent;
        }

        if ($this->hierarchy->isCourseSubfolder($folder) && $folder->parent) {
            $folder = $folder->parent;
        }

        $name = $folder->folder_name . ' ' . ($folder->parent?->folder_name ?? '');
        if (str_contains($name, '2nd')) {
            return '2nd';
        }

        return '1st';
    }

    public function academicYearFromFolder(Folder $folder): string
    {
        $current = $folder;
        while ($current) {
            if ($this->hierarchy->isEqSemesterFolder($current) || $this->hierarchy->isTgSemesterFolder($current)) {
                if (preg_match('/(\d{4})-(\d{4})/', $current->folder_name, $m)) {
                    return $m[1] . '-' . $m[2];
                }
                if (preg_match('/(\d{4})-(\d{4})/', (string) $current->slug, $m)) {
                    return $m[1] . '-' . $m[2];
                }
            }
            $current = $current->parent;
        }

        if ($this->hierarchy->isCourseSubfolder($folder) && $folder->parent) {
            $folder = $folder->parent;
        }

        $text = $folder->folder_name . ' ' . ($folder->parent?->folder_name ?? '');
        if (preg_match('/(\d{4})-(\d{4})/', $text, $m)) {
            return $m[1] . '-' . $m[2];
        }
        $y = now()->month >= 8 ? now()->year : now()->year - 1;

        return $y . '-' . ($y + 1);
    }

    /**
     * Create a pending (or approved) exam questionnaire from a Documents-tab folder upload.
     */
    public function createFromFolderUpload(
        int $userId,
        Folder $folder,
        string $title,
        string $storedPath,
        string $fileType,
        string $examType,
        ?string $subject = null,
        string $status = 'pending',
        ?int $reviewedBy = null,
    ): ExamQuestionnaire {
        $subjectLabel = $subject ?? $title;

        return ExamQuestionnaire::create([
            'submitted_by' => $userId,
            'title' => $title,
            'file_path' => $storedPath,
            'file_type' => $fileType === 'word' ? 'word' : 'pdf',
            'subject' => $subjectLabel,
            'exam_type' => $examType,
            'submission_type' => $this->submissionTypeFromFolder($folder),
            'school_year_id' => SchoolYear::activeId(),
            'semester' => $this->semesterFromFolder($folder),
            'academic_year' => $this->academicYearFromFolder($folder),
            'status' => $status,
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => $status !== 'pending' ? now() : null,
        ]);
    }

    public function resolveTargetFolder(ExamQuestionnaire $questionnaire): ?Folder
    {
        if (!preg_match('/^(\d{4})-(\d{4})$/', $questionnaire->academic_year, $matches)) {
            return null;
        }

        $startYear = (int) $matches[1];
        $semester = match ($questionnaire->semester) {
            '2nd' => '2nd',
            'Summer' => null,
            default => '1st',
        };

        if ($semester === null) {
            return null;
        }

        $subfolder = $questionnaire->submission_type ?? 'toq';

        if ($questionnaire->subject) {
            $leaf = $this->hierarchy->resolveEqUploadFolder(
                $startYear,
                $semester,
                $questionnaire->subject,
                $questionnaire->exam_type ?? 'Prelim',
                $subfolder,
            );
            if ($leaf) {
                return $leaf;
            }
        }

        return $this->hierarchy->resolveExamQuestionnaireFolder(
            $startYear,
            $semester,
            $subfolder,
        );
    }

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
                'school_year_id' => $questionnaire->school_year_id,
                'tags' => 'exam-questionnaire',
            ]
        );

        if (!$questionnaire->document_id) {
            $questionnaire->update(['document_id' => $document->document_id]);
        }

        return $document;
    }
}
