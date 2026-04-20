<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teaching_guides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title', 150);
            $table->string('file_path');
            $table->enum('file_type', ['pdf', 'word']);
            $table->string('subject', 100);
            $table->foreignId('folder_id')->nullable()->constrained('folders', 'folder_id')->onDelete('set null');
            $table->enum('semester', ['1st', '2nd', 'Summer']);
            $table->string('academic_year', 20);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_guides');
    }
};
