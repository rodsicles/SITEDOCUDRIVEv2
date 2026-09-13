<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('title', 150);
            $table->text('instructions')->nullable();
            $table->string('document_type', 30)->default('any');
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->string('department', 100)->nullable();
            $table->foreignId('school_year_id')->nullable()->constrained('school_years')->nullOnDelete();
            $table->string('semester', 30)->nullable();
            $table->dateTime('due_at')->nullable();
            $table->boolean('allow_late_submission')->default(true);
            $table->string('status', 20)->default('open');
            $table->timestamps();

            $table->index(['requested_by', 'status']);
            $table->index(['department', 'due_at']);
        });

        Schema::create('document_request_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_request_id')->constrained('document_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('submitted_document_id')->nullable()->constrained('documents', 'document_id')->nullOnDelete();
            $table->string('status', 30)->default('pending');
            $table->text('review_note')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['document_request_id', 'user_id'], 'doc_request_recipient_unique');
            $table->index(['user_id', 'status']);
        });

        Schema::create('document_search_indexes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->unique()->constrained('documents', 'document_id')->cascadeOnDelete();
            $table->string('file_hash', 64)->nullable()->index();
            $table->longText('content_text')->nullable();
            $table->string('extraction_method', 30)->nullable();
            $table->string('index_status', 20)->default('pending');
            $table->text('index_error')->nullable();
            $table->dateTime('indexed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('saved_document_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 80);
            $table->json('filters');
            $table->timestamps();
            $table->unique(['user_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_document_searches');
        Schema::dropIfExists('document_search_indexes');
        Schema::dropIfExists('document_request_recipients');
        Schema::dropIfExists('document_requests');
    }
};
