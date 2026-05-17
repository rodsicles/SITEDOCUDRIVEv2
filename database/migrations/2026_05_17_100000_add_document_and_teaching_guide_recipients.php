<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_recipients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->foreign('document_id')
                ->references('document_id')
                ->on('documents')
                ->cascadeOnDelete();
            $table->unique(['document_id', 'user_id']);
        });

        Schema::create('teaching_guide_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teaching_guide_id')->constrained('teaching_guides')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['teaching_guide_id', 'user_id']);
        });

        Schema::table('teaching_guides', function (Blueprint $table) {
            $table->unsignedBigInteger('document_id')->nullable()->after('folder_id');
            $table->foreign('document_id')
                ->references('document_id')
                ->on('documents')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('teaching_guides', function (Blueprint $table) {
            $table->dropForeign(['document_id']);
            $table->dropColumn('document_id');
        });

        Schema::dropIfExists('teaching_guide_recipients');
        Schema::dropIfExists('document_recipients');
    }
};
