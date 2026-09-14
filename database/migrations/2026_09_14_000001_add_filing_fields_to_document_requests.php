<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->string('request_category', 30)->default('general')->after('document_type');
            $table->foreignId('destination_folder_id')
                ->nullable()
                ->after('course_id')
                ->constrained('folders', 'folder_id')
                ->nullOnDelete();
        });

        DB::table('document_requests')->update(['request_category' => 'general']);
    }

    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropForeign(['destination_folder_id']);
            $table->dropColumn(['request_category', 'destination_folder_id']);
        });
    }
};
