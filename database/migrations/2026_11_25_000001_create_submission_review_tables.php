<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Comment-based review for Teaching Guides and Exam Questionnaires.
 * Submissions go live immediately; reviewers record views and leave revision notes.
 * Pending submissions become live ("Submitted"). Approved/rejected rows keep their history.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('submission_comments')) {
            Schema::create('submission_comments', function (Blueprint $table) {
                $table->id();
                $table->string('submission_type', 30);
                $table->unsignedBigInteger('submission_id');
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedInteger('version')->default(1);
                $table->string('location', 150)->nullable();
                $table->text('body');
                $table->timestamp('addressed_at')->nullable();
                $table->foreignId('addressed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['submission_type', 'submission_id']);
            });
        }

        if (!Schema::hasTable('submission_views')) {
            Schema::create('submission_views', function (Blueprint $table) {
                $table->id();
                $table->string('submission_type', 30);
                $table->unsignedBigInteger('submission_id');
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role_name', 50)->nullable();
                $table->unsignedInteger('version')->default(1);
                $table->timestamp('viewed_at');
                $table->timestamps();
                $table->unique(['submission_type', 'submission_id', 'user_id', 'version'], 'submission_views_unique');
            });
        }

        // Former "pending" submissions become live. They are not marked as approved by anyone.
        foreach (['teaching_guides', 'exam_questionnaires'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->where('status', 'pending')->update(['status' => 'approved', 'reviewed_by' => null, 'reviewed_at' => null]);
            }
        }

        // Make the converted submissions visible in Documents (mirror rows), best effort.
        try {
            foreach (\App\Models\TeachingGuide::whereNull('document_id')->where('status', 'approved')->whereNotNull('folder_id')->get() as $guide) {
                try {
                    $owner = \App\Models\User::find($guide->user_id);
                    if ($owner) {
                        app(\App\Services\TeachingGuideSyncService::class)->syncDocumentFromGuide($guide, $owner, $guide->recipients()->pluck('users.id')->all());
                    }
                } catch (\Throwable $e) {
                    Log::warning('Teaching guide '.$guide->id.' could not be mirrored: '.$e->getMessage());
                }
            }
            foreach (\App\Models\ExamQuestionnaire::whereNull('document_id')->where('status', 'approved')->get() as $questionnaire) {
                try {
                    app(\App\Services\ExamQuestionnaireSyncService::class)->syncToDocument($questionnaire);
                } catch (\Throwable $e) {
                    Log::warning('Exam questionnaire '.$questionnaire->id.' could not be mirrored: '.$e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Submission mirror backfill skipped: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        // Status conversion is intentionally not reversed (records stay live).
        Schema::dropIfExists('submission_views');
        Schema::dropIfExists('submission_comments');
    }
};
