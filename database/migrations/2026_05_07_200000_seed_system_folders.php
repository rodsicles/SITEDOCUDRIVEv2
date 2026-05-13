<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
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
                'children' => $this->buildSchoolYearFolders('tg', 2025),
            ],
            [
                'name' => 'Exam Questionnaires',
                'slug' => 'eq-category',
                'children' => $this->buildSchoolYearFolders('eq', 2025),
            ],
        ];

        foreach ($tree as $sortOrder => $category) {
            $this->createFolder($category, null, 0, $sortOrder);
        }
    }

    private function buildSchoolYearFolders(string $prefix, int $startYear): array
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
        $existing = DB::table('folders')->where('slug', $data['slug'])->first();

        if ($existing) {
            $folderId = $existing->folder_id;
        } else {
            $now = now();
            $folderId = DB::table('folders')->insertGetId([
                'folder_name' => $data['name'],
                'slug'        => $data['slug'],
                'parent_id'   => $parentId,
                'user_id'     => null,
                'color'       => '#028a0f',
                'is_system'   => true,
                'level'       => $level,
                'sort_order'  => $sortOrder,
                'created_at'  => $now,
                'updated_at'  => $now,
            ], 'folder_id');
        }

        if (!empty($data['children'])) {
            foreach ($data['children'] as $childSort => $child) {
                $this->createFolder($child, $folderId, $level + 1, $childSort);
            }
        }
    }

    public function down(): void
    {
        DB::table('folders')->where('is_system', true)->delete();
    }
};
