<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $sort = 0;
        foreach (config('ite_subjects', []) as $code => $title) {
            Course::updateOrCreate(
                [
                    'code' => strtoupper($code),
                    'department' => Course::DEPT_IT,
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
