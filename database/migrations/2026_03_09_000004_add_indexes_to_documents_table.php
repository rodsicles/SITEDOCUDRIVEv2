<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->index('folder_id');
            $table->index('category_id');
            $table->index('document_type');
            $table->index(['uploaded_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['folder_id']);
            $table->dropIndex(['category_id']);
            $table->dropIndex(['document_type']);
            $table->dropIndex(['uploaded_by', 'created_at']);
        });
    }
};
