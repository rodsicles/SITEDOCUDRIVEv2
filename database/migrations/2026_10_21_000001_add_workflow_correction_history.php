<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('teacher_loads', 'revision_number')) Schema::table('teacher_loads', function (Blueprint $table) {
            $table->unsignedInteger('revision_number')->default(1);
            $table->json('revision_draft')->nullable();
            $table->text('revision_reason')->nullable();
            $table->text('void_reason')->nullable();
        });
        if (!Schema::hasTable('teacher_load_revisions')) Schema::create('teacher_load_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_load_id');
            $table->unsignedInteger('revision_number');
            $table->json('snapshot');
            $table->foreignId('changed_by')->nullable();
            $table->text('reason');
            $table->timestamps();
            $table->unique(['teacher_load_id', 'revision_number']);
        });
        if (!Schema::hasColumn('document_requests', 'lock_version')) Schema::table('document_requests', fn (Blueprint $table) => $table->unsignedInteger('lock_version')->default(1));
        if (!Schema::hasColumn('document_request_recipients', 'previous_status')) Schema::table('document_request_recipients', function (Blueprint $table) {
            $table->string('previous_status', 30)->nullable();
            $table->text('exemption_reason')->nullable();
        });
        if (!Schema::hasTable('document_request_changes')) Schema::create('document_request_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_request_id');
            $table->foreignId('actor_id')->nullable();
            $table->string('action', 40);
            $table->text('reason');
            $table->json('before');
            $table->json('after');
            $table->timestamps();
        });
        if (!Schema::hasTable('course_changes')) Schema::create('course_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id');
            $table->foreignId('actor_id')->nullable();
            $table->json('before');
            $table->json('after');
            $table->timestamps();
        });
        if (!Schema::hasColumn('courses', 'lock_version')) Schema::table('courses', function (Blueprint $table) {
            $table->boolean('units_manually_set')->default(false);
            $table->unsignedInteger('lock_version')->default(1);
        });

        // MySQL DDL is not transactional. Resume foreign keys/indexes after a
        // failed attempt without dropping tables or existing workflow records.
        foreach ([
            ['teacher_load_revisions', 'teacher_load_id', 'teacher_loads', 'cascade'],
            ['teacher_load_revisions', 'changed_by', 'users', 'set null'],
            ['document_request_changes', 'document_request_id', 'document_requests', 'cascade'],
            ['document_request_changes', 'actor_id', 'users', 'set null'],
            ['course_changes', 'course_id', 'courses', 'cascade'],
            ['course_changes', 'actor_id', 'users', 'set null'],
        ] as [$tableName, $column, $parent, $onDelete]) {
            $exists = collect(Schema::getForeignKeys($tableName))->contains(fn ($key) => $key['columns'] === [$column]);
            if (!$exists) Schema::table($tableName, fn (Blueprint $table) => $table->foreign($column)->references('id')->on($parent)->onDelete($onDelete));
        }
        if (!Schema::hasIndex('teacher_load_revisions', 'teacher_load_revisions_teacher_load_id_revision_number_unique')) {
            Schema::table('teacher_load_revisions', fn (Blueprint $table) => $table->unique(['teacher_load_id', 'revision_number']));
        }
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
