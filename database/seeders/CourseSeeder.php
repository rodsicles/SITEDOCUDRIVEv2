<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedDepartment(Course::DEPT_IT, 'ite_subjects');
        $this->seedDepartment(Course::DEPT_ENGINEERING, 'engineering_subjects');
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
