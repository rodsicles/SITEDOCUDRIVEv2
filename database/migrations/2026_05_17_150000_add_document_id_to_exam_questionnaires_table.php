<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_questionnaires', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_questionnaires', 'document_id')) {
                $table->unsignedBigInteger('document_id')->nullable()->after('submitted_by');
                $table->foreign('document_id')
                    ->references('document_id')
                    ->on('documents')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('exam_questionnaires', function (Blueprint $table) {
            if (Schema::hasColumn('exam_questionnaires', 'document_id')) {
                $table->dropForeign(['document_id']);
                $table->dropColumn('document_id');
            }
        });
    }
};
