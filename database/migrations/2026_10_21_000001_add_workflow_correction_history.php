<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_loads', function (Blueprint $table) {
            $table->unsignedInteger('revision_number')->default(1);
            $table->json('revision_draft')->nullable();
            $table->text('revision_reason')->nullable();
            $table->text('void_reason')->nullable();
        });
        Schema::create('teacher_load_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_load_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('revision_number');
            $table->json('snapshot');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason');
            $table->timestamps();
            $table->unique(['teacher_load_id', 'revision_number']);
        });
        Schema::table('document_requests', fn (Blueprint $table) => $table->unsignedInteger('lock_version')->default(1));
        Schema::table('document_request_recipients', function (Blueprint $table) {
            $table->string('previous_status', 30)->nullable();
            $table->text('exemption_reason')->nullable();
        });
        Schema::create('document_request_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);
            $table->text('reason');
            $table->json('before');
            $table->json('after');
            $table->timestamps();
        });
        Schema::create('course_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('before');
            $table->json('after');
            $table->timestamps();
        });
        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('units_manually_set')->default(false);
            $table->unsignedInteger('lock_version')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('courses', fn (Blueprint $table) => $table->dropColumn(['units_manually_set', 'lock_version']));
        Schema::dropIfExists('course_changes');
        Schema::dropIfExists('document_request_changes');
        Schema::table('document_request_recipients', fn (Blueprint $table) => $table->dropColumn(['previous_status', 'exemption_reason']));
        Schema::table('document_requests', fn (Blueprint $table) => $table->dropColumn('lock_version'));
        Schema::dropIfExists('teacher_load_revisions');
        Schema::table('teacher_loads', fn (Blueprint $table) => $table->dropColumn(['revision_number', 'revision_draft', 'revision_reason', 'void_reason']));
    }
};
