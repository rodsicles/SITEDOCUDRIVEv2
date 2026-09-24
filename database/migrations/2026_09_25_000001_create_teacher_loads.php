<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Imported WAMP databases may use MyISAM. This referenced table must enforce keys.
        if (DB::connection()->getDriverName() === 'mysql') {
            $table = DB::selectOne('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', ['school_years']);
            if (strtolower($table->ENGINE ?? '') !== 'innodb') {
                DB::statement('ALTER TABLE school_years ENGINE=InnoDB');
            }
        }

        Schema::create('teacher_loads', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('employee_id')->constrained('employees', 'employee_id')->restrictOnDelete();
            $table->foreignId('school_year_id')->constrained()->restrictOnDelete();
            $table->string('semester', 10);
            $table->string('program', 12);
            $table->foreign('program')->references('code')->on('programs')->restrictOnDelete();
            $table->string('faculty_name', 100);
            $table->string('employee_number', 30)->nullable();
            $table->string('department', 150);
            $table->string('academic_year', 30);
            $table->string('employment_status', 20);
            $table->string('status', 20)->default('draft');
            $table->decimal('total_units', 10, 2)->default(0);
            $table->decimal('total_load', 10, 3)->default(0);
            $table->unsignedInteger('lock_version')->default(1);
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('created_by_employee_id')->nullable()->constrained('employees', 'employee_id')->nullOnDelete();
            $table->timestamps();
            $table->unique(['employee_id', 'school_year_id', 'semester'], 'teacher_load_faculty_term_unique');
            $table->index(['program', 'status']);
        });

        Schema::create('teacher_load_items', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('teacher_load_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 10);
            $table->string('course_code', 30)->nullable();
            $table->string('title', 150);
            $table->string('section', 40)->nullable();
            $table->decimal('lecture_units', 6, 2)->default(0);
            $table->decimal('lab_units', 6, 2)->default(0);
            $table->decimal('load_equivalent', 8, 3)->default(0);
            $table->unsignedSmallInteger('class_size')->nullable();
            $table->json('schedules')->nullable();
            $table->unsignedSmallInteger('position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_load_items');
        Schema::dropIfExists('teacher_loads');
    }
};
