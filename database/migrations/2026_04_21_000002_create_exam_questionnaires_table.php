<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_questionnaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submitted_by')->constrained('users')->onDelete('cascade');
            $table->string('title', 150);
            $table->string('file_path');
            $table->enum('file_type', ['pdf', 'word']);
            $table->string('subject', 100);
            $table->enum('exam_type', ['Quiz', 'Prelim', 'Midterm', 'Pre-Final', 'Final']);
            $table->enum('semester', ['1st', '2nd', 'Summer']);
            $table->string('academic_year', 20);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_questionnaires');
    }
};
