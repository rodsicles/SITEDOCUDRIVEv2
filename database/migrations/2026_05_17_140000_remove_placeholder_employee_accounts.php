<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Only true demo placeholders — keep default role logins (dean/coordinator/faculty/secretary).
     */
    private const PLACEHOLDER_EMPLOYEE_NOS = [
        'CEDR006',
        'KARL005',
    ];

    public function up(): void
    {
        $userIds = DB::table('employees')
            ->whereIn('employee_no', self::PLACEHOLDER_EMPLOYEE_NOS)
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return;
        }

        DB::table('users')->whereIn('id', $userIds)->delete();
    }

    public function down(): void
    {
        // Placeholders are not restored on rollback.
    }
};
