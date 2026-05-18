<?php

namespace App\Support;

use Carbon\Carbon;

class AcademicYear
{
    /** @return list<int> */
    public static function availableStartYears(): array
    {
        return config('academic.school_years', [2023, 2024, 2025, 2026, 2027]);
    }

    public static function label(int $startYear): string
    {
        return 'AY ' . $startYear . '-' . ($startYear + 1);
    }

    public static function rangeString(int $startYear): string
    {
        return $startYear . '-' . ($startYear + 1);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];
        foreach (self::availableStartYears() as $year) {
            $options[(string) $year] = self::label($year);
        }

        return $options;
    }

    public static function currentStartYear(?Carbon $date = null): int
    {
        $date ??= now();
        $year = (int) $date->year;

        return (int) $date->month >= 8 ? $year : $year - 1;
    }

    public static function currentRange(?Carbon $date = null): string
    {
        return self::rangeString(self::currentStartYear($date));
    }

    public static function currentSemester(?Carbon $date = null): string
    {
        $date ??= now();
        $month = (int) $date->month;

        return ($month >= 8 || $month <= 1) ? '1st' : '2nd';
    }

    public static function semesterLabel(string $semester, int $startYear): string
    {
        $endYear = $startYear + 1;
        $ay = self::label($startYear);

        return match ($semester) {
            '2nd' => "2nd Semester {$ay} (Feb {$endYear} - Jun {$endYear})",
            default => "1st Semester {$ay} (Aug {$startYear} - Jan {$endYear})",
        };
    }

    public static function parseRange(?string $range): ?array
    {
        if (!$range || !preg_match('/^(\d{4})-(\d{4})$/', $range, $m)) {
            return null;
        }

        return ['start' => (int) $m[1], 'end' => (int) $m[2]];
    }

    public static function startYearFromQuery(?string $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (ctype_digit((string) $value)) {
            return (int) $value;
        }
        $parsed = self::parseRange($value);

        return $parsed['start'] ?? null;
    }

    public static function isArchived(int $startYear): bool
    {
        return $startYear < self::currentStartYear();
    }
}
