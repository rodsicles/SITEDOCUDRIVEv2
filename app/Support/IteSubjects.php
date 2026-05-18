<?php

namespace App\Support;

use App\Models\User;

class IteSubjects
{
    public static function courses(): array
    {
        $courses = config('ite_subjects', []);
        uksort($courses, fn (string $a, string $b) => strnatcmp($a, $b));

        return $courses;
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

    public static function codeFromLabel(string $label): ?string
    {
        if (preg_match('/^(ITE\d+)/i', trim($label), $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }

    /** Short title for documents.document_title (max 13 characters). */
    public static function documentTitleFromLabel(string $label): string
    {
        return mb_substr(self::codeFromLabel($label) ?? trim($label), 0, 13);
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
