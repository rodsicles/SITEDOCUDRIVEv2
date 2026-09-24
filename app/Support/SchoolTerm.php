<?php

namespace App\Support;

/**
 * SITE academic term windows (see personal store site-school-calendar.md).
 *
 * 1st Sem:  August → January 1st week
 * 2nd Sem:  January 3rd week → May last week
 * Summer:   June → July
 *
 * January week 2 is a transition buffer; treated as still 1st Sem.
 */
class SchoolTerm
{
    public const FIRST = '1st';

    public const SECOND = '2nd';

    public const SUMMER = 'Summer';

    /** @return list<string> */
    public static function options(): array
    {
        return [self::FIRST, self::SECOND, self::SUMMER];
    }

    public static function labels(): array
    {
        return [
            self::FIRST => '1st Semester',
            self::SECOND => '2nd Semester',
            self::SUMMER => 'Summer',
        ];
    }

    /**
     * Resolve the current default term from today's date.
     */
    public static function current(?\DateTimeInterface $now = null): string
    {
        $now = $now ? \Carbon\Carbon::instance($now) : now();
        $month = (int) $now->month;
        $day = (int) $now->day;

        // Summer: June–July
        if ($month === 6 || $month === 7) {
            return self::SUMMER;
        }

        // 2nd Sem: Jan 3rd week (day >= 15) through May
        if ($month >= 2 && $month <= 5) {
            return self::SECOND;
        }
        if ($month === 1 && $day >= 15) {
            return self::SECOND;
        }

        // 1st Sem: August–December, and January through 1st week (and week-2 buffer)
        return self::FIRST;
    }

    public static function label(?string $term): string
    {
        return self::labels()[$term] ?? (string) $term;
    }
}
