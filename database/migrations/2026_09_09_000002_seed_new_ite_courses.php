<?php

use App\Models\Course;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private array $courses = [
        'ITE127' => 'Capstone Project and Research 2 - Project Implementation',
        'ITE128' => 'Systems Administration and Maintenance',
        'ITE129' => 'Free Elective 3 (Project Management)',
        'ITE131' => 'Certification Exam',
    ];

    public function up(): void
    {
        $sort = (int) (Course::where('department', Course::DEPT_IT)->max('sort_order') ?? -1) + 1;

        foreach ($this->courses as $code => $title) {
            Course::updateOrCreate(
                ['code' => $code, 'department' => Course::DEPT_IT],
                ['title' => $title, 'is_active' => true, 'sort_order' => $sort++]
            );
        }
    }

    public function down(): void
    {
        Course::where('department', Course::DEPT_IT)
            ->whereIn('code', array_keys($this->courses))
            ->delete();
    }
};
