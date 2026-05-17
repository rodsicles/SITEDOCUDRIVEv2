<?php

namespace App\Support;

use App\Models\Employee;

class EmployeeNumberGenerator
{
    public const PREFIX_COORDINATOR = 'SITECOOR';

    public const PREFIX_FACULTY = 'SITEFAC';

    /**
     * Next sequential ID for new accounts (SITECOOR001, SITEFAC001, …).
     * Only considers existing numbers matching the prefix; legacy numbers are ignored.
     */
    public function next(string $prefix): string
    {
        $max = 0;

        Employee::query()
            ->where('employee_no', 'like', $prefix . '%')
            ->pluck('employee_no')
            ->each(function (string $number) use ($prefix, &$max) {
                if (preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/i', $number, $matches)) {
                    $max = max($max, (int) $matches[1]);
                }
            });

        return $prefix . str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }
}
