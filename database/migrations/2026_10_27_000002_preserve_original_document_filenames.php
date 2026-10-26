<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['documents', 'document_versions', 'teaching_guides', 'exam_questionnaires'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->string('original_filename')->nullable());
        }
    }

    public function down(): void
    {
        foreach (['documents', 'document_versions', 'teaching_guides', 'exam_questionnaires'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('original_filename'));
        }
    }
};
