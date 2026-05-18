<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $prefixes = ['tg', 'eq'];
        $oldSuffixes = ['prelims', 'midterms', 'finals'];

        foreach ($prefixes as $prefix) {
            foreach ($oldSuffixes as $suffix) {
                DB::table('folders')
                    ->where('is_system', true)
                    ->where('slug', 'like', "{$prefix}-%-%-%-{$suffix}")
                    ->update(['is_system' => false]);
            }
        }
    }

    public function down(): void
    {
        $prefixes = ['tg', 'eq'];
        $oldSuffixes = ['prelims', 'midterms', 'finals'];

        foreach ($prefixes as $prefix) {
            foreach ($oldSuffixes as $suffix) {
                DB::table('folders')
                    ->where('is_system', false)
                    ->where('slug', 'like', "{$prefix}-%-%-%-{$suffix}")
                    ->update(['is_system' => true]);
            }
        }
    }
};
