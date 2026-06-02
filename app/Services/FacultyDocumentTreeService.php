<?php

namespace App\Services;

use App\Models\Document;
use App\Models\ExamQuestionnaire;
use App\Models\Folder;
use App\Models\TeachingGuide;
use App\Support\AcademicYear;
use App\Support\IteSubjects;
use App\Support\UploadStorage;
use Illuminate\Support\Collection;

class FacultyDocumentTreeService
{
    public function __construct(
        protected AcademicHierarchyService $hierarchy,
    ) {}

    /**
     * Build a nested tree for Dean/Coordinator faculty profile document display.
     * TG/EQ mirror Documents folder layout: Semester → TG|LB|TOS|TOQ → ITE Course → files.
     *
     * @return array<string, mixed>
     */
    /**
     * Active uploads only (excludes recycle bin and missing / purged files).
     */
    public function displayableDocumentsForUser(int $userId): Collection
    {
        return Document::with('folder')
            ->where('uploaded_by', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn (Document $document) => $this->isDisplayable($document))
            ->values();
    }

    public function buildForUser(int $userId): array
    {
        $documents = Document::with(['folder.parent.parent.parent.parent.parent'])
            ->where('uploaded_by', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn (Document $document) => $this->isDisplayable($document));

        $documentIds = $documents->pluck('document_id')->all();

        $teachingGuides = TeachingGuide::with('folder.parent.parent')
            ->where('user_id', $userId)
            ->where(function ($q) use ($documentIds) {
                $q->whereNull('document_id')
                    ->orWhereIn('document_id', $documentIds);
            })
            ->get()
            ->keyBy('document_id');
        $examQuestionnaires = ExamQuestionnaire::where('submitted_by', $userId)
            ->where(function ($q) use ($documentIds) {
                $q->whereNull('document_id')
                    ->orWhereIn('document_id', $documentIds);
            })
            ->get()
            ->keyBy('document_id');

        $tree = [
            'Teaching Guides' => [],
            'Exam Questionnaires' => [],
            'Accreditation and Certifications' => [],
            'Academics' => [],
            'Other' => [],
        ];

        foreach ($documents as $document) {
            $category = $this->resolveCategory($document);
            $node = $this->buildDocumentNode($document, $teachingGuides, $examQuestionnaires);

            if ($category === 'Teaching Guides' || $category === 'Exam Questionnaires') {
                $this->insertShareableBranch(
                    $tree[$category],
                    $document,
                    $node,
                );
            } else {
                $this->insertFlatCategory($tree[$category], $document, $node);
            }
        }

        foreach ($teachingGuides as $guide) {
            if ($guide->document_id) {
                continue;
            }

            $node = [
                'id' => 'tg-' . $guide->id,
                'title' => $this->courseLabel($guide->subject, $guide->title),
                'type' => $guide->file_type,
                'status' => $guide->status,
                'created_at' => $guide->created_at,
                'view_url' => null,
                'download_url' => null,
                'is_pending_submission' => true,
            ];

            $this->insertShareableBranch(
                $tree['Teaching Guides'],
                null,
                $node,
                $guide->folder,
                $guide->semester,
                $guide->academic_year,
                null,
                $guide->subject ?: $guide->title,
            );
        }

        foreach ($examQuestionnaires as $eq) {
            if ($eq->document_id) {
                continue;
            }

            $node = [
                'id' => 'eq-' . $eq->id,
                'title' => $this->courseLabel($eq->subject, $eq->title),
                'type' => $eq->file_type,
                'status' => $eq->status,
                'created_at' => $eq->created_at,
                'view_url' => null,
                'download_url' => null,
                'is_questionnaire' => true,
            ];

            $this->insertShareableBranch(
                $tree['Exam Questionnaires'],
                null,
                $node,
                null,
                $eq->semester,
                $eq->academic_year,
                $eq->submission_type,
                $eq->subject,
            );
        }

        return array_filter($tree, fn ($branch) => !empty($branch));
    }

    protected function resolveCategory(Document $document): string
    {
        $allowed = [
            'Teaching Guides',
            'Exam Questionnaires',
            'Accreditation and Certifications',
            'Academics',
        ];

        $cat = $document->category;
        if ($cat && in_array($cat, $allowed, true)) {
            return $cat;
        }

        $root = $document->folder?->top_level_category;

        return in_array($root, $allowed, true) ? $root : ($cat ?: 'Other');
    }

    protected function buildDocumentNode(
        Document $document,
        Collection $teachingGuides,
        Collection $examQuestionnaires,
    ): array {
        $eq = $examQuestionnaires->get($document->document_id);
        $tg = $teachingGuides->get($document->document_id);

        return [
            'id' => $document->document_id,
            'title' => $this->courseLabel(
                $document->subject,
                $document->document_title,
            ),
            'subject' => $document->subject,
            'type' => $document->document_type,
            'created_at' => $document->created_at,
            'file_name' => basename($document->file_path ?? ''),
            'folder_path' => $this->folderBreadcrumb($document->folder),
            'view_url' => null,
            'download_url' => null,
            'status' => $eq?->status ?? $tg?->status,
        ];
    }

    /**
     * Semester folder → TG|LB|TOS|TOQ → ITE course → files.
     */
    protected function insertShareableBranch(
        array &$branch,
        ?Document $document,
        array $node,
        ?Folder $folderOverride = null,
        ?string $semester = null,
        ?string $academicYear = null,
        ?string $submissionTypeSlug = null,
        ?string $courseLabel = null,
    ): void {
        $folder = $folderOverride ?? $document?->folder;
        $course = $this->courseLabel(
            $courseLabel ?? $document?->subject,
            $document?->document_title ?? ($node['title'] ?? 'General'),
        );

        $semesterKey = $this->resolveSemesterFolderLabel($folder, $semester, $academicYear);

        if ($folder && $this->hierarchy->isTgUploadLeafFolder($folder)) {
            $subjectFolder = $folder->parent;
            $subjectKey = $subjectFolder?->folder_name ?? $course;
            $leafKey = $folder->folder_name;
            $branch[$semesterKey] ??= [];
            $branch[$semesterKey][$subjectKey] ??= [];
            $branch[$semesterKey][$subjectKey][$leafKey] ??= [];
            $branch[$semesterKey][$subjectKey][$leafKey][] = $node;

            return;
        }

        if ($folder && $this->hierarchy->isEqUploadLeafFolder($folder)) {
            $assessmentFolder = $folder->parent;
            $subjectFolder = $assessmentFolder?->parent;
            $subjectKey = $subjectFolder?->folder_name ?? $course;
            $assessmentKey = $assessmentFolder?->folder_name ?? 'Assessment';
            $typeKey = $folder->folder_name;
            $branch[$semesterKey] ??= [];
            $branch[$semesterKey][$subjectKey] ??= [];
            $branch[$semesterKey][$subjectKey][$assessmentKey] ??= [];
            $branch[$semesterKey][$subjectKey][$assessmentKey][$typeKey] ??= [];
            $branch[$semesterKey][$subjectKey][$assessmentKey][$typeKey][] = $node;

            return;
        }

        $leafKey = $this->resolveLeafFolderLabel($folder, $submissionTypeSlug);

        $branch[$semesterKey] ??= [];
        $branch[$semesterKey][$leafKey] ??= [];
        $branch[$semesterKey][$leafKey][$course] ??= [];
        $branch[$semesterKey][$leafKey][$course][] = $node;
    }

    protected function courseLabel(?string $subject, ?string $fallback): string
    {
        if ($subject && trim($subject) !== '') {
            if (IteSubjects::isValidLabel($subject)) {
                return $subject;
            }

            return trim($subject);
        }

        return trim($fallback ?? '') ?: 'General';
    }

    protected function resolveSemesterFolderLabel(?Folder $leafFolder, ?string $semester, ?string $academicYear): string
    {
        if ($leafFolder) {
            foreach (array_reverse($leafFolder->getAncestors()) as $ancestor) {
                if (str_contains($ancestor->folder_name, 'Semester')) {
                    return $ancestor->folder_name;
                }
            }
        }

        if ($academicYear && $semester) {
            $semLabel = $semester === '2nd' ? '2nd' : '1st';

            return "{$semLabel} Semester AY {$academicYear}";
        }

        return 'Uncategorized';
    }

    protected function resolveLeafFolderLabel(?Folder $folder, ?string $typeSlug): string
    {
        if ($folder) {
            return $folder->folder_name;
        }

        return match (strtolower((string) $typeSlug)) {
            'tos' => 'TOS (Table of Specification)',
            'toq' => 'TOQ (Table of Question)',
            'tg' => 'TG',
            'lb' => 'LB',
            default => 'Files',
        };
    }

    protected function insertFlatCategory(array &$branch, Document $document, array $node): void
    {
        $folderName = $document->folder?->folder_name ?? 'Uncategorized';
        $branch[$folderName] ??= [];
        $branch[$folderName][] = $node;
    }

    protected function folderBreadcrumb(?Folder $folder): string
    {
        if (!$folder) {
            return '';
        }

        return collect($folder->getAncestors())
            ->pluck('folder_name')
            ->push($folder->folder_name)
            ->implode(' › ');
    }

    protected function isDisplayable(Document $document): bool
    {
        if ($document->trashed()) {
            return false;
        }

        if (!$document->file_path) {
            return false;
        }

        try {
            return UploadStorage::exists($document->file_path);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
