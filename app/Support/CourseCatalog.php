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

        $dept = trim((string) optional($user->employee)->department);

        if (str_contains(strtolower($dept), 'engineering')) {
            return Course::DEPT_ENGINEERING;
        }

        if (IteSubjects::userIsInformationTechnology($user) || str_contains(strtolower($dept), 'information technology') || $dept === 'IT') {
            return Course::DEPT_IT;
        }

        return $dept !== '' ? $dept : null;
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

        $dept = self::departmentForUser($user);
        if ($dept) {
            $query->forDepartment($dept);
        }

        return $query;
    }
}
