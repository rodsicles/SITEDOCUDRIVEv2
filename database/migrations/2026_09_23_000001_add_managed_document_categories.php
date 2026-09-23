<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_categories', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('scope_key', 40)->nullable();
            $table->string('name_key', 100)->nullable();
            $table->unique(['scope_key', 'name_key'], 'document_category_scope_name_unique');
        });
        Schema::table('folders', function (Blueprint $table) {
            $table->unsignedBigInteger('document_category_id')->nullable();
            $table->foreign('document_category_id')->references('category_id')->on('document_categories')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        // Category identities and privacy must survive rollback; reverse only on an empty installation.
        if (\Illuminate\Support\Facades\DB::table('document_categories')->whereNotNull('created_by')->exists()) {
            throw new \RuntimeException('Managed categories contain user data. Restore the pre-deployment backup to roll back safely.');
        }
        Schema::table('folders', function (Blueprint $table) {
            $table->dropForeign(['document_category_id']);
            $table->dropColumn('document_category_id');
        });
        Schema::table('document_categories', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['owner_id']);
            $table->dropUnique('document_category_scope_name_unique');
            $table->dropColumn(['created_by', 'owner_id', 'description', 'is_active', 'scope_key', 'name_key']);
        });
    }
};
