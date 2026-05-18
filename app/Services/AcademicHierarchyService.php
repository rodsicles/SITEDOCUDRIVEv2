<?php

namespace App\Services;

use App\Models\Course;
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
            $this->firstOrCreateSystemFolder($semData, $root->folder_id, 1, $semOrder, $schoolYearId);
            if ($prefix !== 'tg') {
                $semFolder = Folder::where('slug', $semData['slug'])->first();
                if ($semFolder) {
                    foreach ($semData['children'] ?? [] as $subOrder => $subData) {
                        $this->firstOrCreateSystemFolder($subData, $semFolder->folder_id, 2, $subOrder, $schoolYearId);
                    }
                }
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

    public function isUnderTgCategory(?Folder $folder): bool
    {
        if (!$folder) {
            return false;
        }

        $current = $folder;
        while ($current) {
            if ($current->slug === 'tg-category') {
                return true;
            }
            $current = $current->parent;
        }

        return false;
    }

    /** Semester folder under Teaching Guides (e.g. tg-2nd-2025-2026). */
    public function isTgSemesterFolder(?Folder $folder): bool
    {
        if (!$folder) {
            return false;
        }

        return (bool) preg_match('/^tg-(1st|2nd)-\d{4}-\d{4}$/i', (string) $folder->slug);
    }

    /** Subject folder under a TG semester (children: TG, LB). */
    public function isTgSubjectFolder(?Folder $folder): bool
    {
        if (!$folder || !$folder->parent_id) {
            return false;
        }

        $slug = strtolower((string) ($folder->slug ?? ''));

        if (!str_contains($slug, '-subject-') && !str_contains($slug, '-course-')) {
            return false;
        }

        $parent = $folder->relationLoaded('parent') ? $folder->parent : Folder::find($folder->parent_id);

        return $parent && $this->isTgSemesterFolder($parent);
    }

    /** Final upload path: TG or LB under a subject folder. */
    public function isTgUploadLeafFolder(?Folder $folder): bool
    {
        if (!$folder || !$folder->parent_id) {
            return false;
        }

        $name = strtoupper(trim((string) $folder->folder_name));
        if (!in_array($name, ['TG', 'LB'], true)) {
            return false;
        }

        $parent = $folder->relationLoaded('parent') ? $folder->parent : Folder::find($folder->parent_id);

        return $parent && $this->isTgSubjectFolder($parent);
    }

    /**
     * Create subject folder + mandatory TG and LB children under a semester.
     */
    public function ensureSubjectWithTgLb(Folder $semesterFolder, string $subjectLabel): Folder
    {
        if (!$this->isTgSemesterFolder($semesterFolder)) {
            return $semesterFolder;
        }

        $code = IteSubjects::codeFromLabel($subjectLabel) ?? 'course';
        $baseSlug = ($semesterFolder->slug ?? 'tg-sem') . '-subject-' . strtolower($code);
        $sortOrder = (int) Course::query()
            ->where('code', strtoupper($code))
            ->value('sort_order');

        $subject = Folder::firstOrCreate(
            ['slug' => $baseSlug],
            [
                'folder_name' => $subjectLabel,
                'parent_id' => $semesterFolder->folder_id,
                'user_id' => null,
                'color' => '#028a0f',
                'is_system' => true,
                'level' => ($semesterFolder->level ?? 1) + 1,
                'sort_order' => $sortOrder ?: 999,
                'school_year_id' => $semesterFolder->school_year_id,
            ]
        );

        foreach (['tg' => 'TG', 'lb' => 'LB'] as $suffix => $name) {
            Folder::firstOrCreate(
                ['slug' => $baseSlug . '-' . $suffix],
                [
                    'folder_name' => $name,
                    'parent_id' => $subject->folder_id,
                    'user_id' => null,
                    'color' => '#028a0f',
                    'is_system' => true,
                    'level' => ($subject->level ?? 2) + 1,
                    'sort_order' => $suffix === 'tg' ? 0 : 1,
                    'school_year_id' => $semesterFolder->school_year_id,
                ]
            );
        }

        return $subject;
    }

    public function subjectLabelFromTgUploadFolder(Folder $folder): ?string
    {
        if (!$this->isTgUploadLeafFolder($folder)) {
            return null;
        }

        $subjectFolder = $folder->relationLoaded('parent') ? $folder->parent : Folder::find($folder->parent_id);

        return $subjectFolder?->folder_name;
    }

    /**
     * EQ: TOS / TOQ folders — upload picks a course; a child folder is created per course.
     */
    public function isSemesterTypeLeafFolder(?Folder $folder): bool
    {
        if (!$folder || !$this->isTeachingGuidesOrExamCategory($folder)) {
            return false;
        }

        if ($this->isUnderTgCategory($folder)) {
            return false;
        }

        if ($this->isCourseSubfolder($folder)) {
            return false;
        }

        $slug = strtolower($folder->slug ?? '');
        foreach (array_merge(self::TG_SUBFOLDERS, self::EQ_SUBFOLDERS) as $suffix) {
            if (str_ends_with($slug, '-' . $suffix)) {
                return true;
            }
        }

        $name = strtolower($folder->folder_name ?? '');

        return str_contains($name, 'table of')
            || in_array(trim($name), ['tg', 'lb', 'tos', 'toq'], true);
    }

    /** @deprecated Use isSemesterTypeLeafFolder() */
    public function isSemesterCourseLeafFolder(?Folder $folder): bool
    {
        return $this->isSemesterTypeLeafFolder($folder);
    }

    /**
     * Child folder under TG/LB/TOS/TOQ named after an ITE/Engineering course.
     */
    public function isCourseSubfolder(?Folder $folder): bool
    {
        if (!$folder || !$folder->parent_id) {
            return false;
        }

        $parent = $folder->relationLoaded('parent') ? $folder->parent : Folder::find($folder->parent_id);

        return $parent && $this->isSemesterTypeLeafFolder($parent);
    }

    /**
     * Find or create a course-named folder under a TG/LB/TOS/TOQ folder.
     */
    public function ensureCourseFolder(Folder $typeLeafFolder, string $subjectLabel): Folder
    {
        if (!$this->isSemesterTypeLeafFolder($typeLeafFolder)) {
            return $typeLeafFolder;
        }

        $code = IteSubjects::codeFromLabel($subjectLabel) ?? 'course';
        $slug = ($typeLeafFolder->slug ?? 'leaf') . '-course-' . strtolower($code);
        $sortOrder = (int) Course::query()
            ->where('code', strtoupper($code))
            ->value('sort_order');

        return Folder::firstOrCreate(
            ['slug' => $slug],
            [
                'folder_name' => $subjectLabel,
                'parent_id' => $typeLeafFolder->folder_id,
                'user_id' => null,
                'color' => '#028a0f',
                'is_system' => true,
                'level' => ($typeLeafFolder->level ?? 2) + 1,
                'sort_order' => $sortOrder ?: 999,
                'school_year_id' => $typeLeafFolder->school_year_id,
            ]
        );
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

    public function validateSubjectLabel(string $label, ?\App\Models\User $user = null): bool
    {
        return IteSubjects::isValidLabel($label, $user);
    }
}
