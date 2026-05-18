<?php

namespace App\Services;

use App\Models\Folder;
use App\Models\SchoolYear;
use App\Support\AcademicYear;
use App\Support\IteSubjects;
use Database\Seeders\SystemFolderSeeder;

class AcademicHierarchyService
{
    public const ASSESSMENT_SLUGS = ['prelims', 'midterms', 'finals'];

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
     * Ensure semester + assessment folders exist for a school year (archive-safe).
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

    /**
     * Resolve or create full path: Semester → Assessment → Subject → Guide Type → Version.
     */
    public function resolveTeachingGuideFolder(
        int $startYear,
        string $semester,
        string $subjectLabel,
        string $assessmentSlug,
        string $guideTypeSlug,
        string $versionSlug,
    ): ?Folder {
        $this->ensureSchoolYearStructure('tg', $startYear);

        $assessmentFolder = $this->resolveAssessmentFolder('tg', $startYear, $semester, $assessmentSlug);
        if (!$assessmentFolder) {
            return null;
        }

        return $this->ensureSubjectPath($assessmentFolder, $subjectLabel, $guideTypeSlug, $versionSlug);
    }

    /**
     * Resolve or create: Semester → Assessment → Subject (for exam questionnaires).
     */
    public function resolveExamQuestionnaireFolder(
        int $startYear,
        string $semester,
        string $subjectLabel,
        string $assessmentSlug,
    ): ?Folder {
        $this->ensureSchoolYearStructure('eq', $startYear);

        $assessmentFolder = $this->resolveAssessmentFolder('eq', $startYear, $semester, $assessmentSlug);
        if (!$assessmentFolder) {
            return null;
        }

        $subjectSlug = $this->subjectCodeFromLabel($subjectLabel);

        return $this->firstOrCreateSystemFolder([
            'name' => $subjectLabel,
            'slug' => $assessmentFolder->slug . '-' . $subjectSlug,
        ], $assessmentFolder->folder_id, 3, 0);
    }

    public function resolveAssessmentFolder(string $prefix, int $startYear, string $semester, string $assessmentSlug): ?Folder
    {
        $endYear = $startYear + 1;
        $sem = $semester === '2nd' ? '2nd' : '1st';
        $assessmentSlug = in_array($assessmentSlug, self::ASSESSMENT_SLUGS, true) ? $assessmentSlug : 'prelims';
        $slug = "{$prefix}-{$sem}-{$startYear}-{$endYear}-{$assessmentSlug}";

        return Folder::where('slug', $slug)->where('is_system', true)->first();
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

    protected function ensureSubjectPath(
        Folder $assessmentFolder,
        string $subjectLabel,
        string $guideTypeSlug,
        string $versionSlug,
    ): Folder {
        $subjectSlug = $this->subjectCodeFromLabel($subjectLabel);
        $subjectFolder = $this->firstOrCreateSystemFolder([
            'name' => $subjectLabel,
            'slug' => $assessmentFolder->slug . '-' . $subjectSlug,
        ], $assessmentFolder->folder_id, 3, 0);

        $guideTypes = config('academic.guide_types', []);
        $guideName = $guideTypes[$guideTypeSlug] ?? 'Teaching Guides';
        $guideFolder = $this->firstOrCreateSystemFolder([
            'name' => $guideName,
            'slug' => $subjectFolder->slug . '-' . $guideTypeSlug,
        ], $subjectFolder->folder_id, 4, 0);

        $versionTypes = config('academic.version_types', []);
        $versionName = $versionTypes[$versionSlug] ?? ucfirst($versionSlug);

        return $this->firstOrCreateSystemFolder([
            'name' => $versionName,
            'slug' => $guideFolder->slug . '-' . $versionSlug,
        ], $guideFolder->folder_id, 5, 0);
    }

    protected function firstOrCreateSystemFolder(array $data, int $parentId, int $level, int $sortOrder, ?int $schoolYearId = null): Folder
    {
        return Folder::firstOrCreate(
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
    }

    public function validateSubjectLabel(string $label): bool
    {
        return IteSubjects::isValidLabel($label);
    }
}
