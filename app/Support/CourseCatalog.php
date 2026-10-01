<?php

namespace App\Support;

use App\Models\Course;
use App\Models\User;

class CourseCatalog
{
    public static function departmentForUser(?User $user): ?string
    {
        if (!$user) {
            return null;
        }

        if ($user->isDeanOrSecretary()) {
            return null;
        }

        $dept = trim((string) optional($user->employee)->program);

        return in_array($dept, \App\Models\Program::codes(), true) ? $dept : '__unassigned__';
    }

    /**
     * Programs whose subjects the user works with; null means every program (Dean/Secretary).
     *
     * @return list<string>|null
     */
    public static function programsForUser(?User $user): ?array
    {
        if (!$user || $user->isDeanOrSecretary()) {
            return null;
        }

        if ($user->isProgramCoordinator()) {
            return CoordinatorDepartment::programs($user) ?: ['__unassigned__'];
        }

        return [self::departmentForUser($user)];
    }

    /** @return list<string> */
    public static function labelsForUser(?User $user): array
    {
        return self::queryForUser($user)->get()->map(fn (Course $c) => $c->label())->values()->all();
    }

    /** @return list<string> */
    public static function allLabels(): array
    {
        return Course::active()->ordered()->get()->map(fn (Course $c) => $c->label())->values()->all();
    }

    public static function isValidLabel(string $label, ?User $user = null): bool
    {
        return in_array($label, self::labelsForUser($user), true)
            || ($user && ($user->isDeanOrSecretary()) && in_array($label, self::allLabels(), true));
    }

    public static function codeFromLabel(string $label): ?string
    {
        return IteSubjects::codeFromLabel($label);
    }

    public static function documentTitleFromLabel(string $label): string
    {
        return IteSubjects::documentTitleFromLabel($label);
    }

    public static function queryForUser(?User $user)
    {
        $query = Course::active()->ordered();
        $programs = self::programsForUser($user);
        if ($programs !== null) $query->forDepartment($programs);

        // Faculty with explicitly assigned courses → show ONLY those subjects
        if ($user && $user->isFaculty()) {
            $assignedIds = $user->assignedCourses()->pluck('courses.id');
            if ($assignedIds->isNotEmpty()) {
                return $query->whereIn('id', $assignedIds);
            }
        }

        return $query;
    }
}
