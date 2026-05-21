<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Make the users.email column optional and non-unique.
 *
 * Rationale: this system uses username-based auth. There is no SMTP wired up,
 * no password recovery via email, and no OAuth. The email column has been
 * filled with synthetic "@employees.internal" placeholders to satisfy the
 * unique constraint. Softening keeps existing data and lets email become a
 * real, optional contact field once SMTP is configured later.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop the unique index on email if it exists (name is conventional).
        Schema::table('users', function (Blueprint $table) {
            try {
                $table->dropUnique('users_email_unique');
            } catch (\Throwable $e) {
                // Index may not exist on some environments; ignore.
            }
        });

        // Make the column nullable.
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });

        // Convert auto-generated placeholder addresses to NULL so the column
        // no longer looks "filled" when it really isn't.
        DB::table('users')
            ->where('email', 'like', '%@employees.internal')
            ->update(['email' => null]);
    }

    public function down(): void
    {
        // Best-effort reversal. Existing NULL rows will fail the unique
        // constraint, so we cannot guarantee a clean rollback without
        // re-populating values; we restore the column as NOT NULL UNIQUE
        // only if no NULL rows are present.
        $hasNulls = DB::table('users')->whereNull('email')->exists();

        if (!$hasNulls) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('email')->nullable(false)->change();
                $table->unique('email');
            });
        }
    }
};
