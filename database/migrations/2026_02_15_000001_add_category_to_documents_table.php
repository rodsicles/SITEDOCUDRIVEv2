<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Check if columns don't already exist before adding
            if (!Schema::hasColumn('documents', 'category')) {
                $table->enum('category', ['Policies', 'Forms', 'Reports', 'Memos', 'Research Papers', 'Other'])->default('Other')->after('document_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'category')) {
                $table->dropColumn('category');
            }
            // Don't drop tags if it was there before
        });
    }
};
