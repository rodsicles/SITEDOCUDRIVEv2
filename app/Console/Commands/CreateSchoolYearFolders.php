<?php

namespace App\Console\Commands;

use App\Models\Folder;
use App\Models\SchoolYear;
use Database\Seeders\SystemFolderSeeder;
use Illuminate\Console\Command;

class CreateSchoolYearFolders extends Command
{
    protected $signature   = 'folders:create-school-year {year? : The start year of the new school year (e.g. 2026)}';
    protected $description = 'Create Teaching Guide and Exam Questionnaire folders for a new school year';

    public function handle(): int
    {
        // Default: use current year when run on Aug 10
        $startYear = (int) ($this->argument('year') ?? now()->year);
        $endYear   = $startYear + 1;
        $ay        = "AY {$startYear}-{$endYear}";

        $this->info("Creating folders for {$ay}...");

        $roots = [
            'tg' => 'tg-category',
            'eq' => 'eq-category',
        ];

        $schoolYearId = SchoolYear::where('start_year', $startYear)->value('id');

        foreach ($roots as $prefix => $rootSlug) {
            $root = Folder::where('slug', $rootSlug)->first();

            if (!$root) {
                $this->warn("Root folder '{$rootSlug}' not found. Run SystemFolderSeeder first.");
                continue;
            }

            $semesters = SystemFolderSeeder::buildSchoolYearFolders($prefix, $startYear);

            foreach ($semesters as $semOrder => $semData) {
                // Skip if already exists
                if (Folder::where('slug', $semData['slug'])->exists()) {
                    $this->line("  Skipping (exists): {$semData['name']}");
                    continue;
                }

                $semFolder = Folder::create([
                    'folder_name'    => $semData['name'],
                    'slug'           => $semData['slug'],
                    'parent_id'      => $root->folder_id,
                    'user_id'        => null,
                    'color'          => '#028a0f',
                    'is_system'      => true,
                    'level'          => 1,
                    'sort_order'     => $semOrder,
                    'school_year_id' => $schoolYearId,
                ]);

                $this->line("  Created: {$semData['name']}");

                foreach ($semData['children'] as $subOrder => $subData) {
                    Folder::create([
                        'folder_name'    => $subData['name'],
                        'slug'           => $subData['slug'],
                        'parent_id'      => $semFolder->folder_id,
                        'user_id'        => null,
                        'color'          => '#028a0f',
                        'is_system'      => true,
                        'level'          => 2,
                        'sort_order'     => $subOrder,
                        'school_year_id' => $schoolYearId,
                    ]);

                    $this->line("    Created: {$subData['name']}");
                }
            }
        }

        $this->info("Done! School year {$ay} folders created.");
        return self::SUCCESS;
    }
}
