<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('trashed_folder_id')
                ->nullable()
                ->after('folder_id')
                ->constrained('folders', 'folder_id')
                ->nullOnDelete();

            $table->foreignId('deleted_by')
                ->nullable()
                ->after('deleted_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['trashed_folder_id']);
            $table->dropForeign(['deleted_by']);
            $table->dropColumn(['trashed_folder_id', 'deleted_by']);
        });
    }
};
