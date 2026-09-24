<?php

use App\Models\Course;
use App\Models\Program;
use App\Support\ProgramCurricula;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (! Schema::hasColumn('courses', 'year_level')) {
                $table->unsignedTinyInteger('year_level')->nullable()->after('program');
            }
            if (! Schema::hasColumn('courses', 'semester')) {
                $table->string('semester', 12)->nullable()->after('year_level');
            }
        });

        // Index may already exist on re-run in some environments; ignore duplicate.
        try {
            Schema::table('courses', function (Blueprint $table) {
                $table->index(['program', 'semester', 'year_level'], 'courses_program_term_idx');
            });
        } catch (\Throwable) {
            // Index already present.
        }

        $sortBase = (int) (Course::max('sort_order') ?? 0);

        foreach (ProgramCurricula::all() as $program => $courses) {
            $sort = $sortBase;
            foreach ($courses as $row) {
                $sort++;
                Course::updateOrCreate(
                    [
                        'code' => $row['code'],
                        'program' => $program,
                    ],
                    [
                        'title' => $row['title'],
                        'department' => Program::DEPARTMENT_NAME,
                        'year_level' => $row['year_level'],
                        'semester' => $row['semester'],
                        'is_active' => true,
                        'sort_order' => $sort,
                    ]
                );
            }
            $sortBase = $sort;
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Curriculum seed preserves course IDs. Restore from backup instead of rolling back.');
    }
};
