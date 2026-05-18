<?php

namespace App\Services;

use App\Models\Document;
use App\Models\ExamQuestionnaire;
use App\Models\Folder;
use App\Models\SchoolYear;
use App\Models\TeachingGuide;
use App\Models\User;
use App\Support\AcademicYear;
use Illuminate\Support\Facades\DB;

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
     */
    public function archive(User $dean, string $archiveName, string $newName, int $newStartYear): SchoolYear
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

            return $newSchoolYear;
        });
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
