<?php

namespace App\Services;

use App\Models\Folder;
use App\Models\SchoolYear;
use App\Support\IteSubjects;
use Database\Seeders\SystemFolderSeeder;

class AcademicHierarchyService
{
    public const TG_SUBFOLDERS = ['tg', 'lb'];
    public const EQ_SUBFOLDERS = ['tos', 'toq'];

    public function subjectCodeFromLabel(string $subjectLabel): string
    {
        if (preg_match('/^([A-Z]{2,4}\d{2,4})/i', trim($subjectLabel), $m)) {
            return strtolower($m[1]);
        }

        return strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($subjectLabel)));
    }

    public function examTypeToAssessmentSlug(string $examType): string
    {
        return match ($examType) {
            'Midterm' => 'midterms',
            'Final', 'Pre-Final' => 'finals',
            default => 'prelims',
        };
    }

    /**
     * Ensure semester + TG/LB or TOS/TOQ folders exist for a school year.
     */
    public function ensureSchoolYearStructure(string $prefix, int $startYear): void
    {
        $rootSlug = $prefix === 'eq' ? 'eq-category' : 'tg-category';
        $root = Folder::where('slug', $rootSlug)->first();
        if (!$root) {
            return;
        }

        $schoolYearId = SchoolYear::where('start_year', $startYear)->value('id');

        $semesters = SystemFolderSeeder::buildSchoolYearFolders($prefix, $startYear);
        foreach ($semesters as $semOrder => $semData) {
            $semFolder = $this->firstOrCreateSystemFolder($semData, $root->folder_id, 1, $semOrder, $schoolYearId);
            foreach ($semData['children'] ?? [] as $subOrder => $subData) {
                $this->firstOrCreateSystemFolder($subData, $semFolder->folder_id, 2, $subOrder, $schoolYearId);
            }
        }
    }

    public function ensureActiveSchoolYearStructures(): void
    {
        $active = SchoolYear::active();
        if (!$active) {
            return;
        }

        $this->ensureSchoolYearStructure('tg', $active->start_year);
        $this->ensureSchoolYearStructure('eq', $active->start_year);
    }

    /**
     * Resolve a semester leaf folder (TG, LB, TOS, or TOQ).
     */
    public function resolveSemesterSubfolder(
        string $prefix,
        int $startYear,
        string $semester,
        string $subfolderSlug,
    ): ?Folder {
        $this->ensureSchoolYearStructure($prefix, $startYear);

        $endYear = $startYear + 1;
        $sem = $semester === '2nd' ? '2nd' : '1st';
        $subfolderSlug = strtolower($subfolderSlug);
        $slug = "{$prefix}-{$sem}-{$startYear}-{$endYear}-{$subfolderSlug}";

        return Folder::where('slug', $slug)->where('is_system', true)->first();
    }

    /**
     * Teaching guide uploads: Semester → TG or LB.
     */
    public function resolveTeachingGuideFolder(
        int $startYear,
        string $semester,
        string $guideTypeSlug,
    ): ?Folder {
        $subfolder = $guideTypeSlug === 'lab-manual' ? 'lb' : 'tg';

        return $this->resolveSemesterSubfolder('tg', $startYear, $semester, $subfolder);
    }

    /**
     * Approved exam questionnaires: Semester → TOS or TOQ.
     */
    public function resolveExamQuestionnaireFolder(
        int $startYear,
        string $semester,
        string $subfolder = 'toq',
    ): ?Folder {
        $subfolder = in_array($subfolder, self::EQ_SUBFOLDERS, true) ? $subfolder : 'toq';

        return $this->resolveSemesterSubfolder('eq', $startYear, $semester, $subfolder);
    }

    /** @deprecated Use resolveSemesterSubfolder() — kept for legacy callers */
    public function resolveAssessmentFolder(string $prefix, int $startYear, string $semester, string $assessmentSlug): ?Folder
    {
        if ($prefix === 'eq') {
            return $this->resolveExamQuestionnaireFolder($startYear, $semester, 'toq');
        }

        return $this->resolveTeachingGuideFolder($startYear, $semester, 'teaching-guides');
    }

    /** @return list<int> Folder IDs under a school year for filtering */
    public function folderIdsForSchoolYear(string $prefix, int $startYear): array
    {
        $endYear = $startYear + 1;
        $pattern = "{$prefix}-%{$startYear}-{$endYear}%";

        return Folder::where('is_system', true)
            ->where('slug', 'like', $pattern)
            ->pluck('folder_id')
            ->all();
    }

    public function isTeachingGuidesOrExamCategory(?Folder $folder): bool
    {
        if (!$folder) {
            return false;
        }

        $slugs = ['tg-category', 'eq-category'];
        $current = $folder;
        while ($current) {
            if (in_array($current->slug, $slugs, true)) {
                return true;
            }
            $current = $current->parent;
        }

        return false;
    }

    protected function firstOrCreateSystemFolder(array $data, int $parentId, int $level, int $sortOrder, ?int $schoolYearId = null): Folder
    {
        $folder = Folder::firstOrCreate(
            ['slug' => $data['slug']],
            [
                'folder_name' => $data['name'],
                'parent_id' => $parentId,
                'user_id' => null,
                'color' => '#028a0f',
                'is_system' => true,
                'level' => $level,
                'sort_order' => $sortOrder,
                'school_year_id' => $schoolYearId,
            ]
        );

        if ($schoolYearId && !$folder->school_year_id) {
            $folder->update(['school_year_id' => $schoolYearId]);
        }

        return $folder;
    }

    public function validateSubjectLabel(string $label): bool
    {
        return IteSubjects::isValidLabel($label);
    }
}
