<?php

namespace App\Services;

use App\Models\Document;
use App\Models\ExamQuestionnaire;
use App\Models\Folder;
use App\Models\SchoolYear;
use App\Models\TeachingGuide;
use App\Models\DashboardLog;
use App\Models\User;
use App\Support\AcademicYear;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SchoolYearService
{
    /**
     * Get or create the active school year.
     */
    public function getActive(): SchoolYear
    {
        $active = SchoolYear::active();

        if (!$active) {
            $startYear = AcademicYear::currentStartYear();
            $active = SchoolYear::create([
                'name' => "S.Y. {$startYear}-" . ($startYear + 1),
                'start_year' => $startYear,
                'end_year' => $startYear + 1,
                'is_active' => true,
            ]);
        }

        return $active;
    }

    /**
     * Archive the current school year and start a new one.
     *
     * @return array{schoolYear: SchoolYear, detached: array{teaching_guides: int, exam_questionnaires: int, total: int}}
     */
    public function archive(User $dean, string $archiveName, string $newName, int $newStartYear): array
    {
        return DB::transaction(function () use ($dean, $archiveName, $newName, $newStartYear) {
            $current = $this->getActive();

            // Tag all untagged records with the current school year
            Document::whereNull('school_year_id')->update(['school_year_id' => $current->id]);
            TeachingGuide::whereNull('school_year_id')->update(['school_year_id' => $current->id]);
            ExamQuestionnaire::whereNull('school_year_id')->update(['school_year_id' => $current->id]);

            // Tag user-created folders (non-system) that have no school_year_id
            Folder::where('is_system', false)
                ->whereNull('school_year_id')
                ->update(['school_year_id' => $current->id]);

            // Only approved TG/EQ belong in the official archive; rejected and pending stay active.
            $detached = $this->detachNonApprovedSubmissions($current->id);

            // Mark current school year as archived
            $current->update([
                'name' => $archiveName,
                'is_active' => false,
                'archived_at' => now(),
                'archived_by' => $dean->id,
            ]);

            // Create new active school year
            $newSchoolYear = SchoolYear::create([
                'name' => $newName,
                'start_year' => $newStartYear,
                'end_year' => $newStartYear + 1,
                'is_active' => true,
            ]);

            // Create system folders for the new school year (TG & EQ semester structure)
            app(AcademicHierarchyService::class)->ensureSchoolYearStructure('tg', $newStartYear);
            app(AcademicHierarchyService::class)->ensureSchoolYearStructure('eq', $newStartYear);

            return [
                'schoolYear' => $newSchoolYear,
                'detached' => $detached,
            ];
        });
    }

    /**
     * Remove rejected and pending submissions from the year being archived.
     * They keep school_year_id null so they appear in the new active year's workflows.
     *
     * @return array{teaching_guides: int, exam_questionnaires: int, total: int}
     */
    protected function detachNonApprovedSubmissions(int $schoolYearId): array
    {
        $detachScope = fn ($q) => $q->where('status', 'rejected')
            ->orWhere('status', 'pending')
            ->orWhereNull('status');

        $tgDetached = TeachingGuide::where('school_year_id', $schoolYearId)
            ->where($detachScope)
            ->update(['school_year_id' => null]);

        $eqDetached = ExamQuestionnaire::where('school_year_id', $schoolYearId)
            ->where($detachScope)
            ->update(['school_year_id' => null]);

        return [
            'teaching_guides' => $tgDetached,
            'exam_questionnaires' => $eqDetached,
            'total' => $tgDetached + $eqDetached,
        ];
    }


    /**
     * Restore an archived school year as active and remove the mistaken active year.
     *
     * @return array{schoolYear: SchoolYear, removed: array<string, int>|null}
     */
    public function restoreArchivedAsActive(SchoolYear $archivedYear, User $dean): array
    {
        if (!$archivedYear->isArchived()) {
            throw new InvalidArgumentException('Only archived school years can be restored.');
        }

        return DB::transaction(function () use ($archivedYear, $dean) {
            $active = SchoolYear::active();
            $removed = null;

            if ($active && $active->id !== $archivedYear->id) {
                $this->reattachOrphanedRecords($archivedYear->id);

                $removed = app(SchoolYearArchiveDeletionService::class)->purgeSchoolYearBucket(
                    $active,
                    $dean,
                    "Removed school year {$active->name} while restoring {$archivedYear->name} as active",
                    'archive_restored',
                );
            }

            $archivedYear->update([
                'is_active' => true,
                'archived_at' => null,
                'archived_by' => null,
            ]);

            DashboardLog::create([
                'user_id' => $dean->id,
                'target_user_id' => null,
                'activity' => "Restored archived school year as active: {$archivedYear->name}",
                'activity_type' => 'archive_restored',
                'visibility' => 'dean',
            ]);

            $this->clearAnalyticsCaches();

            return [
                'schoolYear' => $archivedYear->fresh(),
                'removed' => $removed,
            ];
        });
    }

    /**
     * Re-tag records carried forward to the active year back to the restored archive year.
     */
    protected function reattachOrphanedRecords(int $schoolYearId): void
    {
        Document::whereNull('school_year_id')->update(['school_year_id' => $schoolYearId]);
        TeachingGuide::whereNull('school_year_id')->update(['school_year_id' => $schoolYearId]);
        ExamQuestionnaire::whereNull('school_year_id')->update(['school_year_id' => $schoolYearId]);

        Folder::where('is_system', false)
            ->whereNull('school_year_id')
            ->update(['school_year_id' => $schoolYearId]);
    }

    /**
     * Clear cached analytics so dashboards pick up the restored school year immediately.
     */
    protected function clearAnalyticsCaches(): void
    {
        if (config('cache.default') === 'array') {
            return;
        }

        try {
            Cache::flush();
        } catch (\Throwable) {
            // Non-fatal: analytics caches expire within 10 minutes anyway.
        }
    }
    /**
     * Get all archived school years.
     */
    public function getArchived()
    {
        return SchoolYear::archived()->get();
    }

    /**
     * Get an archived school year with counts.
     */
    public function getArchivedWithCounts(int $schoolYearId): ?SchoolYear
    {
        return SchoolYear::withCount(['documents', 'teachingGuides', 'examQuestionnaires'])
            ->find($schoolYearId);
    }
}
