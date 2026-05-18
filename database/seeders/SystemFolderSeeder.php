<?php

namespace Database\Seeders;

use App\Models\Folder;
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
        $years = config('academic.school_years', [2023, 2024, 2025, 2026, 2027]);
        $folders = [];
        foreach ($years as $startYear) {
            $folders = array_merge($folders, self::buildSchoolYearFolders($prefix, $startYear));
        }

        return $folders;
    }

    public static function buildSchoolYearFolders(string $prefix, int $startYear): array
    {
        $endYear = $startYear + 1;
        $ay      = "AY {$startYear}-{$endYear}";

        return [
            [
                'name' => "1st Semester {$ay} (Aug {$startYear} - Jan {$endYear})",
                'slug' => "{$prefix}-1st-{$startYear}-{$endYear}",
                'children' => [
                    ['name' => 'Prelims',  'slug' => "{$prefix}-1st-{$startYear}-{$endYear}-prelims"],
                    ['name' => 'Midterms', 'slug' => "{$prefix}-1st-{$startYear}-{$endYear}-midterms"],
                    ['name' => 'Finals',   'slug' => "{$prefix}-1st-{$startYear}-{$endYear}-finals"],
                ],
            ],
            [
                'name' => "2nd Semester {$ay} (Feb {$endYear} - Jun {$endYear})",
                'slug' => "{$prefix}-2nd-{$startYear}-{$endYear}",
                'children' => [
                    ['name' => 'Prelims',  'slug' => "{$prefix}-2nd-{$startYear}-{$endYear}-prelims"],
                    ['name' => 'Midterms', 'slug' => "{$prefix}-2nd-{$startYear}-{$endYear}-midterms"],
                    ['name' => 'Finals',   'slug' => "{$prefix}-2nd-{$startYear}-{$endYear}-finals"],
                ],
            ],
        ];
    }

    private function createFolder(array $data, ?int $parentId, int $level, int $sortOrder): void
    {
        $folder = Folder::firstOrCreate(
            ['slug' => $data['slug']],
            [
                'folder_name' => $data['name'],
                'parent_id'   => $parentId,
                'user_id'     => null,
                'color'       => '#028a0f',
                'is_system'   => true,
                'level'       => $level,
                'sort_order'  => $sortOrder,
            ]
        );

        if (!empty($data['children'])) {
            foreach ($data['children'] as $childSort => $child) {
                $this->createFolder($child, $folder->folder_id, $level + 1, $childSort);
            }
        }
    }
}
