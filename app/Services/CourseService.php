<?php

namespace App\Services;

use App\Models\Course;
use App\Models\DashboardLog;
use App\Support\IteSubjects;
use Illuminate\Support\Collection;

class CourseService
{
    public function listAll(?string $departmentFilter = null, ?string $search = null): Collection
    {
        $query = Course::query()->ordered();

        if ($departmentFilter && $departmentFilter !== 'all') {
            $map = [
                'it' => Course::DEPT_IT,
                'engineering' => Course::DEPT_ENGINEERING,
            ];
            if (isset($map[$departmentFilter])) {
                $query->where('department', $map[$departmentFilter]);
            }
        }

        if ($search !== null && trim($search) !== '') {
            $term = '%' . trim($search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', $term)
                    ->orWhere('title', 'like', $term);
            });
        }

        return $query->get();
    }

    public function create(array $data, int $deanUserId): Course
    {
        $code = strtoupper(trim($data['code']));
        $department = $data['department'];
        $title = trim($data['title']);

        $course = Course::create([
            'code' => $code,
            'title' => $title,
            'department' => $department,
            'is_active' => true,
            'sort_order' => (int) (Course::max('sort_order') ?? 0) + 1,
        ]);

        DashboardLog::create([
            'user_id' => $deanUserId,
            'activity' => "Added course {$course->label()} ({$department})",
            'activity_type' => 'course_added',
            'visibility' => 'dean',
        ]);

        return $course;
    }

    public function deactivate(Course $course, int $deanUserId): void
    {
        $course->update(['is_active' => false]);

        DashboardLog::create([
            'user_id' => $deanUserId,
            'activity' => "Removed course {$course->label()} from faculty choices",
            'activity_type' => 'course_removed',
            'visibility' => 'dean',
        ]);
    }

    public function reactivate(Course $course, int $deanUserId): void
    {
        $course->update(['is_active' => true]);

        DashboardLog::create([
            'user_id' => $deanUserId,
            'activity' => "Restored course {$course->label()} to faculty choices",
            'activity_type' => 'course_restored',
            'visibility' => 'dean',
        ]);
    }

    public static function departments(): array
    {
        return [
            Course::DEPT_IT => 'Information Technology',
            Course::DEPT_ENGINEERING => 'Engineering',
        ];
    }
}
