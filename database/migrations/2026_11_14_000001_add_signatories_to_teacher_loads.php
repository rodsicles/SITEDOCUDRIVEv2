<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Printed "Endorsed by" / "Approved by" names and titles for each Teacher's Load PDF.
return new class extends Migration {
    private const COLUMNS = ['endorsed_by_name', 'endorsed_by_title', 'approved_by_name', 'approved_by_title'];

    public function up(): void
    {
        Schema::table('teacher_loads', function (Blueprint $table) {
            foreach (self::COLUMNS as $column) {
                if (!Schema::hasColumn('teacher_loads', $column)) {
                    $table->string($column, 100)->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('teacher_loads', function (Blueprint $table) {
            $table->dropColumn(array_values(array_filter(self::COLUMNS, fn ($c) => Schema::hasColumn('teacher_loads', $c))));
        });
    }
};
