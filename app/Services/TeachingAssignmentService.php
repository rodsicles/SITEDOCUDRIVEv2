<?php

namespace App\Services;

use App\Models\{SchoolYear, TeacherLoad, User};
use App\Support\SchoolTerm;

class TeachingAssignmentService
{
    /** Current account assignments plus the employee's published timetable. No backfill is needed. */
    public function current(User $teacher, ?string $semester = null): array
    {
        $semester ??= SchoolTerm::current();
        $year = SchoolYear::active();
        $employee = $teacher->employee;
        $courses = $employee
            ? app(TeacherLoadService::class)->assignedCourses($employee, $semester)
            : collect();
        $load = $employee && $year
            ? TeacherLoad::query()->where('employee_id', $employee->employee_id)
                ->where('school_year_id', $year->id)->where('semester', $semester)
                ->where('status', 'finalized')->with('items')->first()
            : null;

        $published = $load?->items->where('kind', 'course') ?? collect();
        $rows = $published->map(fn ($item) => [
            'code' => $item->course_code,
            'title' => $item->title,
            'section' => $item->section,
            'schedules' => $item->schedules ?? [],
        ])->values();
        $publishedIds = $published->pluck('course_id')->filter()->all();
        foreach ($courses as $course) {
            if (! in_array($course->id, $publishedIds)) {
                $rows->push(['code' => $course->code, 'title' => $course->title, 'section' => null, 'schedules' => []]);
            }
        }

        return [
            'year' => $year,
            'semester' => $semester,
            'rows' => $rows,
            'has_schedules' => $rows->contains(fn ($row) => count($row['schedules']) > 0),
            'load' => $load,
        ];
    }
}
