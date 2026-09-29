<?php

namespace App\Console\Commands;

use App\Support\CourseUnits;
use Illuminate\Console\Command;

class SyncCourseUnits extends Command
{
    protected $signature = 'courses:sync-units';

    protected $description = 'Apply official lecture/lab units to the Course Catalog (safe to re-run)';

    public function handle(): int
    {
        if (! CourseUnits::available()) {
            $this->error('Unit columns are missing. Run php artisan migrate first.');

            return self::FAILURE;
        }

        $stats = CourseUnits::apply();
        $this->info("Updated {$stats['updated']} course(s); {$stats['unchanged']} already current.");

        foreach ($stats['missing'] as $program => $codes) {
            $this->warn("{$program}: not in catalog — " . implode(', ', $codes));
        }

        return self::SUCCESS;
    }
}
