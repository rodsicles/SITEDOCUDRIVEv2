<?php

use App\Models\SchoolYear;
use App\Services\AcademicHierarchyService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $service = app(AcademicHierarchyService::class);

        foreach (SchoolYear::query()->get() as $schoolYear) {
            $service->ensureSchoolYearStructure('tg', $schoolYear->start_year);
            $service->ensureSchoolYearStructure('eq', $schoolYear->start_year);
        }
    }

    public function down(): void
    {
        // Subfolders are data, not rolled back.
    }
};
