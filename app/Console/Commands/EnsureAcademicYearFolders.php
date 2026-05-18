<?php

namespace App\Console\Commands;

use App\Services\AcademicHierarchyService;
use App\Support\AcademicYear;
use Illuminate\Console\Command;

class EnsureAcademicYearFolders extends Command
{
    protected $signature = 'folders:ensure-academic-years';
    protected $description = 'Ensure Teaching Guide and Exam Questionnaire folders exist for all configured school years (archive history)';

    public function handle(AcademicHierarchyService $hierarchy): int
    {
        foreach (AcademicYear::availableStartYears() as $year) {
            $this->info('Ensuring folders for ' . AcademicYear::label($year) . '...');
            $hierarchy->ensureSchoolYearStructure('tg', $year);
            $hierarchy->ensureSchoolYearStructure('eq', $year);
        }

        $this->info('All academic year folder structures are ready.');

        return self::SUCCESS;
    }
}
