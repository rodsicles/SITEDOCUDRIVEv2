<?php

namespace Database\Seeders;

use App\Models\Folder;
use App\Models\SchoolYear;
use Illuminate\Database\Seeder;

class SystemFolderSeeder extends Seeder
{
    public function run(): void
    {
        $tree = [
            [
                'name' => 'Accreditation and Certifications',
                'slug' => 'accreditation-and-certifications',
                'children' => [
                    ['name' => 'PAASCU Documentation Files', 'slug' => 'paascu-documentation-files'],
                    ['name' => 'PRC Results Civil and Sanitary Engineering', 'slug' => 'prc-results-civil-sanitary'],
                    ['name' => 'ISO', 'slug' => 'iso'],
                    ['name' => 'TESTA', 'slug' => 'testa'],
                    [
                        'name' => 'Certification of IT Exam',
                        'slug' => 'certification-it-exam',
                        'children' => [
                            ['name' => 'Cybersecurity', 'slug' => 'cert-cybersecurity'],
                            ['name' => 'Networking', 'slug' => 'cert-networking'],
                            ['name' => 'HTML & CSS', 'slug' => 'cert-html-css'],
                        ],
                    ],
                    ['name' => 'Portfolios', 'slug' => 'accred-portfolios'],
                ],
            ],
            [
                'name' => 'Academics',
                'slug' => 'academics',
                'children' => [
                    ['name' => 'Grading Sheets', 'slug' => 'grading-sheets'],
                    ['name' => 'Portfolios of Faculty', 'slug' => 'portfolios-of-faculty'],
                    [
                        'name' => 'Capstone',
                        'slug' => 'capstone',
                        'children' => [
                            ['name' => 'AI', 'slug' => 'capstone-ai'],
                            ['name' => 'Web', 'slug' => 'capstone-web'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Teaching Guides',
                'slug' => 'tg-category',
                'children' => $this->buildArchiveSchoolYears('tg'),
            ],
            [
                'name' => 'Exam Questionnaires',
                'slug' => 'eq-category',
                'children' => $this->buildArchiveSchoolYears('eq'),
            ],
            [
                'name' => 'Custom Folders',
                'slug' => Folder::CUSTOM_FOLDERS_SLUG,
                'children' => [],
            ],
        ];

        foreach ($tree as $sortOrder => $category) {
            $this->createFolder($category, null, 0, $sortOrder);
        }

        $this->command->info('System folders seeded successfully!');
    }

    /**
     * Build semester + exam subfolders for a given school year start.
     * E.g. startYear=2025 → AY 2025-2026
     */
    public static function buildArchiveSchoolYears(string $prefix): array
    {
        $years = config('academic.school_years', [2025]);
        $folders = [];
        foreach ($years as $startYear) {
            $schoolYearId = SchoolYear::where('start_year', $startYear)->value('id');
            $built = self::buildSchoolYearFolders($prefix, $startYear);
            foreach ($built as &$sem) {
                $sem['school_year_id'] = $schoolYearId;
            }
            unset($sem);
            $folders = array_merge($folders, $built);
        }

        return $folders;
    }

    public static function buildSchoolYearFolders(string $prefix, int $startYear): array
    {
        $endYear = $startYear + 1;
        $ay      = "AY {$startYear}-{$endYear}";

        // Semester folders only; subject tree created on demand (TG/LB or Prelims/Midterms/Finals → TOS/TOQ).
        $subfolders = [];

        return [
            [
                'name' => "1st Semester {$ay}",
                'slug' => "{$prefix}-1st-{$startYear}-{$endYear}",
                'children' => array_map(fn ($sf) => [
                    'name' => $sf['name'],
                    'slug' => "{$prefix}-1st-{$startYear}-{$endYear}-{$sf['slug_suffix']}",
                ], $subfolders),
            ],
            [
                'name' => "2nd Semester {$ay}",
                'slug' => "{$prefix}-2nd-{$startYear}-{$endYear}",
                'children' => array_map(fn ($sf) => [
                    'name' => $sf['name'],
                    'slug' => "{$prefix}-2nd-{$startYear}-{$endYear}-{$sf['slug_suffix']}",
                ], $subfolders),
            ],
        ];
    }

    private function createFolder(array $data, ?int $parentId, int $level, int $sortOrder): void
    {
        $schoolYearId = $data['school_year_id'] ?? null;

        $folder = Folder::firstOrCreate(
            ['slug' => $data['slug']],
            [
                'folder_name'    => $data['name'],
                'parent_id'      => $parentId,
                'user_id'        => null,
                'color'          => '#028a0f',
                'is_system'      => true,
                'level'          => $level,
                'sort_order'     => $sortOrder,
                'school_year_id' => $schoolYearId,
            ]
        );

        if (!empty($data['children'])) {
            foreach ($data['children'] as $childSort => $child) {
                $child['school_year_id'] = $schoolYearId;
                $this->createFolder($child, $folder->folder_id, $level + 1, $childSort);
            }
        }
    }
}
