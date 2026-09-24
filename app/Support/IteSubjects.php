<?php

namespace App\Support;

use App\Models\Course;
use App\Models\User;

// Compatibility facade used by existing academic-folder and upload workflows.
class IteSubjects
{
    public static function courses(): array { return self::coursesForDepartment('BSIT'); }
    public static function engineeringCourses(): array { return []; }
    public static function coursesForDepartment(string $program): array
    {
        return Course::active()->where('program', $program)->ordered()->pluck('title', 'code')->all();
    }
    public static function labelsFromConfig(?string $program = null): array
    {
        return Course::active()->when($program, fn ($q) => $q->where('program', $program))->ordered()->get()->map(fn ($course) => $course->label())->all();
    }
    public static function labels(): array { return CourseCatalog::allLabels(); }
    public static function labelsForUser(?User $user): array { return CourseCatalog::labelsForUser($user); }
    public static function pickerMetaForUser(?User $user): array
    {
        return ['label' => 'Subject (Course)', 'hint' => 'Select an available course. Empty programs have no courses yet.', 'placeholder' => 'Search by course code or title', 'validateMessage' => 'Select an available course.', 'ariaLabel' => 'Courses'];
    }
    public static function codeFromLabel(string $label): ?string
    {
        if (preg_match('/^(.+?)\s+[–—-]\s+/u', trim($label), $match)) return trim($match[1]);
        return preg_match('/^([A-Z]{2,4}\d{2,4})/i', trim($label), $match) ? strtoupper($match[1]) : null;
    }
    public static function documentTitleFromLabel(string $label): string { return mb_substr(self::codeFromLabel($label) ?? trim($label), 0, 13); }
    public static function formatLabel(string $code, string $title): string { return $code.' – '.$title; }
    public static function isValidLabel(string $label, ?User $user = null): bool { return CourseCatalog::isValidLabel($label, $user); }
    public static function userIsInformationTechnology(?User $user): bool { return $user?->employee?->program === 'BSIT'; }
    public static function userIsEngineering(?User $user): bool { return $user?->employee?->program === 'BSCpE'; }
    public static function shouldUseSubjectPicker(?User $user, bool $shareableCategoryTab): bool { return $shareableCategoryTab && $user !== null; }
}
