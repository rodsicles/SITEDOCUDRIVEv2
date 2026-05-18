<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('school_year_id')->nullable()->after('category_id')
                ->constrained('school_years')->nullOnDelete();
        });

        Schema::table('teaching_guides', function (Blueprint $table) {
            $table->foreignId('school_year_id')->nullable()->after('folder_id')
                ->constrained('school_years')->nullOnDelete();
        });

        Schema::table('exam_questionnaires', function (Blueprint $table) {
            $table->foreignId('school_year_id')->nullable()->after('document_id')
                ->constrained('school_years')->nullOnDelete();
        });

        Schema::table('folders', function (Blueprint $table) {
            $table->foreignId('school_year_id')->nullable()->after('is_system')
                ->constrained('school_years')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_year_id');
        });

        Schema::table('teaching_guides', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_year_id');
        });

        Schema::table('exam_questionnaires', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_year_id');
        });

        Schema::table('folders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_year_id');
        });
    }
};
