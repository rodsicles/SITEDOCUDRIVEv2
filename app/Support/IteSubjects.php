<?php

namespace App\Support;

use App\Models\User;

class IteSubjects
{
    public static function courses(): array
    {
        $fromDb = CourseCatalog::queryForUser(null)
            ->where('department', \App\Models\Course::DEPT_IT)
            ->get()
            ->mapWithKeys(fn ($c) => [$c->code => $c->title])
            ->all();

        if (!empty($fromDb)) {
            uksort($fromDb, fn (string $a, string $b) => strnatcmp($a, $b));

            return $fromDb;
        }

        $courses = config('ite_subjects', []);
        uksort($courses, fn (string $a, string $b) => strnatcmp($a, $b));

        return $courses;
    }

    /** @return list<string> */
    public static function labels(): array
    {
        $labels = CourseCatalog::allLabels();

        if (!empty($labels)) {
            return $labels;
        }

        $labels = [];
        foreach (self::courses() as $code => $title) {
            $labels[] = self::formatLabel($code, $title);
        }

        return $labels;
    }

    /** @return list<string> */
    public static function labelsForUser(?User $user): array
    {
        $labels = CourseCatalog::labelsForUser($user);

        if (!empty($labels)) {
            return $labels;
        }

        if ($user && !CourseCatalog::departmentForUser($user)) {
            return self::labels();
        }

        return [];
    }

    public static function codeFromLabel(string $label): ?string
    {
        if (preg_match('/^([A-Z]{2,4}\d{2,4})/i', trim($label), $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }

    public static function documentTitleFromLabel(string $label): string
    {
        return mb_substr(self::codeFromLabel($label) ?? trim($label), 0, 13);
    }

    public static function formatLabel(string $code, string $title): string
    {
        return $code . ' – ' . $title;
    }

    public static function isValidLabel(string $label, ?User $user = null): bool
    {
        return CourseCatalog::isValidLabel($label, $user)
            || in_array($label, self::labels(), true);
    }

    public static function userIsInformationTechnology(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $dept = strtolower(trim((string) optional($user->employee)->department));

        return str_contains($dept, 'information technology')
            || $dept === 'it'
            || str_contains($dept, 'info tech');
    }

    public static function userIsEngineering(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $dept = strtolower(trim((string) optional($user->employee)->department));

        return str_contains($dept, 'engineering');
    }

    public static function shouldUseSubjectPicker(?User $user, bool $shareableCategoryTab): bool
    {
        if (!$shareableCategoryTab || !$user) {
            return false;
        }

        return $user->isDeanOrSecretary()
            || $user->isProgramCoordinator()
            || self::userIsInformationTechnology($user)
            || self::userIsEngineering($user);
    }
}
