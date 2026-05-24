<?php

namespace App\Support;

use App\Models\Course;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Support\Collection;

class CoordinatorDepartment
{
    public static function name(?User $user): ?string
    {
        if (!$user?->isProgramCoordinator()) {
            return null;
        }

        $dept = trim((string) optional($user->employee)->department);

        return $dept !== '' ? $dept : null;
    }

    public static function require(User $user): string
    {
        $dept = self::name($user);
        if (!$dept) {
            abort(403, 'Your account has no department assigned. Contact the Dean to update your profile.');
        }

        return $dept;
    }

    /** @return list<string> */
    public static function courseCodes(?string $department): array
    {
        if (!$department) {
            return [];
        }

        return Course::query()
            ->where('department', $department)
            ->pluck('code')
            ->map(fn (string $code) => strtolower($code))
            ->all();
    }

    public static function folderMatchesDepartment(Folder $folder, ?string $department): bool
    {
        if (!$department) {
            return false;
        }

        $codes = self::courseCodes($department);
        if ($codes === []) {
            return false;
        }

        $slug = strtolower((string) ($folder->slug ?? ''));
        if (preg_match('/-(?:subject|course)-([a-z0-9]+)/', $slug, $matches)) {
            return in_array($matches[1], $codes, true);
        }

        $code = IteSubjects::codeFromLabel((string) $folder->folder_name);

        return $code && in_array(strtolower($code), $codes, true);
    }

    public static function filterSubjectFolders(Collection $folders, ?User $viewer): Collection
    {
        $department = self::name($viewer);
        if (!$department) {
            return collect();
        }

        return $folders
            ->filter(fn (Folder $folder) => self::folderMatchesDepartment($folder, $department))
            ->values();
    }
}
