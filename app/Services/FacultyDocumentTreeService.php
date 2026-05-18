<?php

namespace App\Services;

use App\Models\Document;
use App\Models\ExamQuestionnaire;
use App\Models\Folder;
use App\Models\TeachingGuide;
use App\Support\AcademicYear;
use Illuminate\Support\Collection;

class FacultyDocumentTreeService
{
    public function __construct(
        protected AcademicHierarchyService $hierarchy,
    ) {}

    /**
     * Build a nested tree for Dean/Coordinator faculty profile document display.
     *
     * @return array<string, mixed>
     */
    public function buildForUser(int $userId): array
    {
        $documents = Document::with(['folder.parent.parent.parent.parent.parent'])
            ->where('uploaded_by', $userId)
            ->orderByDesc('created_at')
            ->get();

        $teachingGuides = TeachingGuide::where('user_id', $userId)->get()->keyBy('document_id');
        $examQuestionnaires = ExamQuestionnaire::where('submitted_by', $userId)->get()->keyBy('document_id');

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
                $this->insertHierarchical($tree[$category], $document, $node);
            } else {
                $this->insertFlatCategory($tree[$category], $document, $node);
            }
        }

        // Include exam questionnaires not yet mirrored to documents
        foreach ($examQuestionnaires as $eq) {
            if ($eq->document_id) {
                continue;
            }
            $node = [
                'id' => 'eq-' . $eq->id,
                'title' => $eq->title,
                'type' => $eq->file_type,
                'status' => $eq->status,
                'created_at' => $eq->created_at,
                'view_url' => null,
                'download_url' => null,
                'is_questionnaire' => true,
            ];
            $pseudo = new Document([
                'subject' => $eq->subject,
                'academic_year' => $eq->academic_year,
                'semester' => $eq->semester,
            ]);
            $pseudo->setRelation('folder', null);
            $this->insertHierarchical($tree['Exam Questionnaires'], $pseudo, $node, [
                'academic_year' => $eq->academic_year,
                'semester' => $eq->semester,
                'subject' => $eq->subject,
                'assessment' => $this->hierarchy->examTypeToAssessmentSlug($eq->exam_type),
                'folder_type' => 'Submissions',
            ]);
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
        return [
            'id' => $document->document_id,
            'title' => $document->document_title,
            'subject' => $document->subject,
            'type' => $document->document_type,
            'created_at' => $document->created_at,
            'file_name' => basename($document->file_path ?? ''),
            'folder_path' => $this->folderBreadcrumb($document->folder),
            'view_url' => null,
            'download_url' => null,
            'status' => $examQuestionnaires->get($document->document_id)?->status,
        ];
    }

    protected function insertHierarchical(array &$branch, Document $document, array $node, ?array $override = null): void
    {
        $meta = $override ?? $this->parseFolderMeta($document);
        $ay = $meta['academic_year'] ?? AcademicYear::currentRange();
        $sem = $meta['semester'] ?? '1st';
        $subject = $meta['subject'] ?? ($document->subject ?: 'General');
        $assessment = $meta['assessment'] ?? 'prelims';
        $folderType = $meta['folder_type'] ?? 'Files';
        $version = $meta['version'] ?? 'final';

        $branch[$ay] ??= [];
        $branch[$ay][$sem] ??= [];
        $branch[$ay][$sem][$subject] ??= [];
        $branch[$ay][$sem][$subject][$assessment] ??= [];
        $branch[$ay][$sem][$subject][$assessment][$folderType] ??= [];
        $branch[$ay][$sem][$subject][$assessment][$folderType][$version] ??= [];
        $branch[$ay][$sem][$subject][$assessment][$folderType][$version][] = $node;
    }

    protected function insertFlatCategory(array &$branch, Document $document, array $node): void
    {
        $folderName = $document->folder?->folder_name ?? 'Uncategorized';
        $branch[$folderName] ??= [];
        $branch[$folderName][] = $node;
    }

    /** @return array<string, string|null> */
    protected function parseFolderMeta(Document $document): array
    {
        $folder = $document->folder;
        $ancestors = $folder ? array_merge($folder->getAncestors(), [$folder]) : [];
        $slugTrail = implode(' ', array_map(fn ($f) => $f->slug ?? '', $ancestors));
        $nameTrail = implode(' ', array_map(fn ($f) => $f->folder_name ?? '', $ancestors));

        $academicYear = null;
        if (preg_match('/(\d{4})-(\d{4})/', $slugTrail . ' ' . $nameTrail, $m)) {
            $academicYear = $m[1] . '-' . $m[2];
        }

        $semester = str_contains($slugTrail, '-2nd-') || str_contains($nameTrail, '2nd Semester') ? '2nd' : '1st';

        $assessment = 'prelims';
        foreach (['finals', 'midterms', 'prelims'] as $period) {
            if (str_contains($slugTrail, $period)) {
                $assessment = $period;
                break;
            }
        }

        $guideType = 'Files';
        foreach (config('academic.guide_types', []) as $slug => $label) {
            if (str_contains($slugTrail, $slug)) {
                $guideType = $label;
                break;
            }
        }

        $version = 'final';
        foreach (config('academic.version_types', []) as $slug => $label) {
            if (str_contains($slugTrail, $slug)) {
                $version = $label;
                break;
            }
        }

        return [
            'academic_year' => $academicYear ?? AcademicYear::currentRange(),
            'semester' => $semester,
            'subject' => $document->subject ?: ($folder?->folder_name ?? 'General'),
            'assessment' => ucfirst($assessment),
            'folder_type' => $guideType,
            'version' => $version,
        ];
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
}
