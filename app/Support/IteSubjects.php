<?php

namespace App\Support;

use App\Models\User;

class IteSubjects
{
    public static function courses(): array
    {
        return config('ite_subjects', []);
    }

    /** @return list<string> */
    public static function labels(): array
    {
        $labels = [];
        foreach (self::courses() as $code => $title) {
            $labels[] = self::formatLabel($code, $title);
        }

        return $labels;
    }

    public static function formatLabel(string $code, string $title): string
    {
        return $code . ' – ' . $title;
    }

    public static function isValidLabel(string $label): bool
    {
        return in_array($label, self::labels(), true);
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

    public static function shouldUseSubjectPicker(?User $user, bool $shareableCategoryTab): bool
    {
        if (!$shareableCategoryTab || !$user) {
            return false;
        }

        return $user->isDeanOrSecretary()
            || $user->isProgramCoordinator()
            || self::userIsInformationTechnology($user);
    }
}
