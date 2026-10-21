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
        $query = Course::query()->whereIn('program', \App\Models\Program::codes())->ordered();

        if ($departmentFilter === 'inactive') {
            $query->where('is_active', false);
        } elseif ($departmentFilter && $departmentFilter !== 'all') {
            $department = self::slugToDepartment($departmentFilter);
            if ($department) {
                $query->where('program', $department);
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

    /**
     * List courses for a single department (Program Coordinator scope).
     */
    /** @param string|list<string> $department */
    public function listForDepartment(string|array $department, ?string $filter = 'all', ?string $search = null): Collection
    {
        $query = Course::query()->ordered()->whereIn('program', (array) $department);

        if ($filter === 'inactive') {
            $query->where('is_active', false);
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

    public static function slugToDepartment(?string $slug): ?string
    {
        foreach (\App\Models\Program::codes() as $code) if (strtolower($code) === strtolower((string) $slug)) return $code;
        return null;
    }

    public static function departmentToSlug(?string $department): ?string
    {
        return $department ? strtolower($department) : null;
    }

    public function create(array $data, int $deanUserId): Course
    {
        $code = trim($data['code']);
        $department = $data['program'];
        $title = trim($data['title']);

        $course = Course::create([
            'code' => $code,
            'title' => $title,
            'program' => $department,
            'is_active' => true,
            'sort_order' => (int) (Course::max('sort_order') ?? 0) + 1,
            ...\Illuminate\Support\Arr::only($data, ['year_level', 'semester', 'lecture_units', 'lab_units']),
        ]);

        if (array_key_exists('lecture_units', $data) || array_key_exists('lab_units', $data)) {
            $course->forceFill(['units_manually_set' => true])->save();
        }

        DashboardLog::create([
            'user_id' => $deanUserId,
            'activity' => "Added course {$course->label()} ({$department})",
            'activity_type' => 'course_added',
            'visibility' => 'dean',
        ]);

        return $course;
    }

    public function rename(Course $course, string $newCode, string $newTitle, int $deanUserId): void
    {
        $oldLabel = $course->label();
        $course->update(['code' => $newCode, 'title' => $newTitle]);

        DashboardLog::create([
            'user_id' => $deanUserId,
            'activity' => "Renamed course {$oldLabel} → {$course->label()}",
            'activity_type' => 'course_renamed',
            'visibility' => 'dean',
        ]);
    }

    public function updateDetails(Course $course, array $data, int $actorId): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($course, $data, $actorId) {
            $locked = Course::lockForUpdate()->findOrFail($course->id);
            if (isset($data['lock_version'])) abort_if((int) $data['lock_version'] !== $locked->lock_version, 409, 'This course changed. Reload before saving.');
            if (!empty($data['updated_at'])) {
                abort_unless($locked->updated_at->toISOString() === $data['updated_at'], 409, 'This course changed in another session. Reload before saving.');
            }
            $before = $locked->only(['code', 'title', 'year_level', 'semester', 'lecture_units', 'lab_units']);
            $locked->fill(\Illuminate\Support\Arr::except($data, ['program', 'updated_at', 'lock_version']));
            $locked->code = trim($data['code']);
            $locked->title = trim($data['title']);
            if (array_key_exists('lecture_units', $data) || array_key_exists('lab_units', $data)) $locked->units_manually_set = true;
            $locked->lock_version++;
            $locked->save();
            \App\Models\CourseChange::create(['course_id' => $locked->id, 'actor_id' => $actorId, 'before' => $before, 'after' => $locked->only(array_keys($before))]);
            DashboardLog::create(['user_id' => $actorId, 'activity' => \Illuminate\Support\Str::limit("Updated course {$locked->label()} (term, year level and units recorded in course history)", 250), 'activity_type' => 'course_updated', 'visibility' => 'dean']);
        });
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
        abort_unless(in_array($course->program, \App\Models\Program::codes(), true), 422, 'Legacy courses require an explicit program mapping before activation.');
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
        return \App\Models\Program::labels();
    }
}
