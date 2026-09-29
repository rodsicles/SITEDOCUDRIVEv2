<?php

namespace App\Support;

use App\Models\Course;
use App\Models\Program;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Single source of truth for which subjects can be assigned to an account
 * and how those assignments are validated (Dean and Coordinator forms).
 */
class CourseAssignment
{
    public const FIELDS = ['id', 'code', 'title', 'year_level', 'semester'];

    public static function validProgram(?string $program): ?string
    {
        return in_array($program, Program::codes(), true) ? $program : null;
    }

    public static function coursesFor(?string $program): Collection
    {
        $program = self::validProgram($program);
        if ($program === null) {
            return collect();
        }

        return Course::active()->forDepartment($program)->ordered()->get(self::FIELDS);
    }

    public static function programHasCourses(?string $program): bool
    {
        $program = self::validProgram($program);

        return $program !== null && Course::active()->forDepartment($program)->exists();
    }

    /**
     * @param  bool  $requireWhenAvailable  Require at least one subject when the program has a curriculum.
     */
    public static function rules(?string $program, bool $requireWhenAvailable = false): array
    {
        $program = self::validProgram($program) ?? '';
        $mustAssign = $requireWhenAvailable && self::programHasCourses($program);

        return [
            'course_ids' => $mustAssign ? ['required', 'array', 'min:1'] : ['nullable', 'array'],
            'course_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('courses', 'id')->where('program', $program)->where('is_active', true),
            ],
        ];
    }

    public static function messages(): array
    {
        return [
            'course_ids.required' => 'Assign at least one subject from the selected program.',
            'course_ids.min' => 'Assign at least one subject from the selected program.',
            'course_ids.*.exists' => 'One or more selected subjects do not belong to the selected program.',
        ];
    }

    /**
     * Short "CODE1, CODE2 (+N more)" summary for audit log lines.
     */
    public static function summary(array $courseIds, int $limit = 6): string
    {
        if (empty($courseIds)) {
            return 'none';
        }

        $codes = Course::whereIn('id', $courseIds)->orderBy('code')->pluck('code');
        $shown = $codes->take($limit)->implode(', ');
        $extra = $codes->count() - $limit;

        return $extra > 0 ? "{$shown} (+{$extra} more)" : $shown;
    }
}
