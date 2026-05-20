<?php

namespace App\Support;

use App\Models\Course;
use App\Models\User;

class IteSubjects
{
    public static function courses(): array
    {
        return self::coursesForDepartment(Course::DEPT_IT);
    }

    public static function engineeringCourses(): array
    {
        return self::coursesForDepartment(Course::DEPT_ENGINEERING);
    }

    public static function coursesForDepartment(string $department): array
    {
        $fromDb = CourseCatalog::queryForUser(null)
            ->where('department', $department)
            ->get()
            ->mapWithKeys(fn ($c) => [$c->code => $c->title])
            ->all();

        if (!empty($fromDb)) {
            uksort($fromDb, fn (string $a, string $b) => strnatcmp($a, $b));

            return $fromDb;
        }

        $configKey = $department === Course::DEPT_ENGINEERING
            ? 'engineering_subjects'
            : 'ite_subjects';

        $courses = config($configKey, []);
        uksort($courses, fn (string $a, string $b) => strnatcmp($a, $b));

        return $courses;
    }

    /** @return list<string> */
    public static function labelsFromConfig(?string $department = null): array
    {
        $labels = [];

        if ($department === null || $department === Course::DEPT_IT) {
            foreach (self::coursesForDepartment(Course::DEPT_IT) as $code => $title) {
                $labels[] = self::formatLabel($code, $title);
            }
        }

        if ($department === null || $department === Course::DEPT_ENGINEERING) {
            foreach (self::coursesForDepartment(Course::DEPT_ENGINEERING) as $code => $title) {
                $labels[] = self::formatLabel($code, $title);
            }
        }

        return $labels;
    }

    /** @return list<string> */
    public static function labels(): array
    {
        $labels = CourseCatalog::allLabels();

        if (!empty($labels)) {
            return $labels;
        }

        return self::labelsFromConfig();
    }

    /** @return list<string> */
    public static function labelsForUser(?User $user): array
    {
        $labels = CourseCatalog::labelsForUser($user);

        if (!empty($labels)) {
            return $labels;
        }

        $dept = CourseCatalog::departmentForUser($user);

        if ($dept === Course::DEPT_ENGINEERING) {
            return self::labelsFromConfig(Course::DEPT_ENGINEERING);
        }

        if ($dept === Course::DEPT_IT) {
            return self::labelsFromConfig(Course::DEPT_IT);
        }

        if ($user && ($user->isDeanOrSecretary() || $user->isProgramCoordinator())) {
            return self::labelsFromConfig();
        }

        return [];
    }

    /** @return array{label: string, hint: string, placeholder: string, validateMessage: string, ariaLabel: string} */
    public static function pickerMetaForUser(?User $user): array
    {
        if (self::userIsEngineering($user)) {
            return [
                'label' => 'Subject (Engineering Course)',
                'hint' => 'Engineering courses only. Search by code or title.',
                'placeholder' => 'Search e.g. CE101, ENGG104...',
                'validateMessage' => 'Please select a valid Engineering subject from the list.',
                'ariaLabel' => 'Engineering courses',
            ];
        }

        if (self::userIsInformationTechnology($user)) {
            return [
                'label' => 'Subject (ITE Course)',
                'hint' => 'Information Technology courses only. Search by code or title.',
                'placeholder' => 'Search e.g. ITE108, Web Systems...',
                'validateMessage' => 'Please select a valid ITE subject from the list.',
                'ariaLabel' => 'ITE courses',
            ];
        }

        return [
            'label' => 'Subject (Course)',
            'hint' => 'Search by course code or title.',
            'placeholder' => 'Search e.g. ITE108, CE101...',
            'validateMessage' => 'Please select a valid subject from the list.',
            'ariaLabel' => 'Courses',
        ];
    }

    public static function codeFromLabel(string $label): ?string
    {
        $label = trim($label);

        if (preg_match('/^(.+?)\s+[–\-]\s+/u', $label, $matches)) {
            return trim($matches[1]);
        }

        if (preg_match('/^([A-Z]{2,4}\d{2,4})/i', $label, $matches)) {
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
            || in_array($label, self::labelsForUser($user), true)
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
