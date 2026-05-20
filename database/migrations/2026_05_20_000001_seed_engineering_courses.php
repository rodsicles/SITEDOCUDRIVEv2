<?php

use App\Models\Course;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $subjects = config('engineering_subjects', []);
        $sort = (int) (Course::where('department', Course::DEPT_ENGINEERING)->max('sort_order') ?? -1) + 1;

        foreach ($subjects as $code => $title) {
            Course::updateOrCreate(
                [
                    'code' => $code,
                    'department' => Course::DEPT_ENGINEERING,
                ],
                [
                    'title' => $title,
                    'is_active' => true,
                    'sort_order' => $sort++,
                ]
            );
        }
    }

    public function down(): void
    {
        $codes = array_keys(config('engineering_subjects', []));
        Course::where('department', Course::DEPT_ENGINEERING)
            ->whereIn('code', $codes)
            ->delete();
    }
};
