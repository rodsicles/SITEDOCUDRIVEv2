<?php

use App\Support\CourseUnits;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const BSENSE_NAME = 'Bachelor of Science in Environmental and Sanitary Engineering';

    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (! Schema::hasColumn('courses', 'lecture_units')) {
                $table->decimal('lecture_units', 4, 2)->nullable()->after('semester');
            }
            if (! Schema::hasColumn('courses', 'lab_units')) {
                $table->decimal('lab_units', 4, 2)->nullable()->after('lecture_units');
            }
        });

        CourseUnits::apply();

        if (Schema::hasTable('programs')) {
            DB::table('programs')->where('code', 'BSEnSE')->update(['name' => self::BSENSE_NAME]);
        }
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['lecture_units', 'lab_units']);
        });

        if (Schema::hasTable('programs')) {
            DB::table('programs')->where('code', 'BSEnSE')
                ->update(['name' => 'Bachelor of Science in Environmental Science']);
        }
    }
};
