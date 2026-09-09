<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Track which version number a Document is currently at
        if (!Schema::hasColumn('documents', 'version')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->unsignedInteger('version')->default(1)->after('tags');
            });
        }

        // Immutable snapshot of each past version
        if (Schema::hasTable('document_versions')) {
            return; // already created by a previous (failed) run
        }

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->unsignedInteger('version_number')->default(1);
            $table->string('document_title', 150)->nullable();
            $table->string('file_path', 500);
            $table->bigInteger('file_size')->nullable();
            $table->string('document_type', 20)->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('document_id')
                ->references('document_id')->on('documents')
                ->onDelete('cascade');

            $table->foreign('uploaded_by')
                ->references('id')->on('users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_versions');
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('version');
        });
    }
};
