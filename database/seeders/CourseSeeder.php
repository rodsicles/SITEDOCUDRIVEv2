<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        // Courses are entered through Course Catalog using verified curriculum data.
        // Never regenerate courses or repopulate intentionally empty programs here.
    }

    private function seedDepartment(string $department, string $configKey): void
    {
        $sort = 0;

        foreach (config($configKey, []) as $code => $title) {
            Course::updateOrCreate(
                [
                    'code' => $code,
                    'department' => $department,
                ],
                [
                    'title' => $title,
                    'is_active' => true,
                    'sort_order' => $sort++,
                ]
            );
        }
    }
}
