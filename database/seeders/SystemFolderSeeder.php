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
                    ['name' => 'Teaching Guides', 'slug' => 'teaching-guides'],
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
        ];

        foreach ($tree as $sortOrder => $category) {
            $this->createFolder($category, null, 0, $sortOrder);
        }

        $this->command->info('System folders seeded successfully!');
    }

    private function createFolder(array $data, ?int $parentId, int $level, int $sortOrder): void
    {
        $folder = Folder::firstOrCreate(
            ['slug' => $data['slug']],
            [
                'folder_name' => $data['name'],
                'parent_id' => $parentId,
                'user_id' => null,
                'color' => '#028a0f',
                'is_system' => true,
                'level' => $level,
                'sort_order' => $sortOrder,
            ]
        );

        if (!empty($data['children'])) {
            foreach ($data['children'] as $childSort => $child) {
                $this->createFolder($child, $folder->folder_id, $level + 1, $childSort);
            }
        }
    }
}
