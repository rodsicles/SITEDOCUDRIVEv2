<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $schoolYears = DB::table('school_years')->get();

        foreach ($schoolYears as $sy) {
            $startYear = $sy->start_year;
            $endYear   = $startYear + 1;

            foreach (['tg', 'eq'] as $prefix) {
                $semSlugs = [
                    "{$prefix}-1st-{$startYear}-{$endYear}",
                    "{$prefix}-2nd-{$startYear}-{$endYear}",
                ];

                foreach ($semSlugs as $semSlug) {
                    $semFolder = DB::table('folders')->where('slug', $semSlug)->first();
                    if (!$semFolder) {
                        continue;
                    }

                    DB::table('folders')
                        ->where('folder_id', $semFolder->folder_id)
                        ->update(['school_year_id' => $sy->id]);

                    DB::table('folders')
                        ->where('parent_id', $semFolder->folder_id)
                        ->update(['school_year_id' => $sy->id]);
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('folders')
            ->whereNotNull('school_year_id')
            ->update(['school_year_id' => null]);
    }
};
