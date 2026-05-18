<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('exam_questionnaires', 'submission_type')) {
            Schema::table('exam_questionnaires', function (Blueprint $table) {
                $table->enum('submission_type', ['tos', 'toq'])
                    ->default('toq')
                    ->after('exam_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('exam_questionnaires', 'submission_type')) {
            Schema::table('exam_questionnaires', function (Blueprint $table) {
                $table->dropColumn('submission_type');
            });
        }
    }
};
