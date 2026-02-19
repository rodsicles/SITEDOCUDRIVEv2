<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DocumentCategory;

class DocumentCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['category_name' => 'Reports', 'color' => '#4CAF50'],
            ['category_name' => 'Assignments', 'color' => '#2196F3'],
            ['category_name' => 'Research', 'color' => '#9C27B0'],
            ['category_name' => 'Presentations', 'color' => '#FF9800'],
            ['category_name' => 'Certificates', 'color' => '#F44336'],
            ['category_name' => 'Forms', 'color' => '#00BCD4'],
            ['category_name' => 'Proposals', 'color' => '#3F51B5'],
            ['category_name' => 'Other', 'color' => '#607D8B'],
        ];

        foreach ($categories as $category) {
            DocumentCategory::create($category);
        }
    }
}
