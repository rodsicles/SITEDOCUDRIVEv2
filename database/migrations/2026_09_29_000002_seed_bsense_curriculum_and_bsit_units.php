<?php

use App\Models\Course;
use App\Models\Program;
use App\Support\CourseUnits;
use App\Support\ProgramCurricula;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const PROGRAMS = ['BSIT', 'BSEnSE'];

    public function up(): void
    {
        $sort = (int) (Course::max('sort_order') ?? 0);

        foreach (self::PROGRAMS as $program) {
            $existing = Course::where('program', $program)->get()
                ->keyBy(fn (Course $course) => CourseUnits::normalize($course->code));

            foreach (ProgramCurricula::forProgram($program) as $row) {
                $course = $existing->get(CourseUnits::normalize($row['code']));

                if ($course) {
                    // Keep catalog titles; only realign the year/term placement.
                    $course->forceFill(['year_level' => $row['year_level'], 'semester' => $row['semester']]);
                    if ($course->isDirty()) {
                        $course->save();
                    }
                    continue;
                }

                Course::create([
                    'code' => $row['code'],
                    'title' => $row['title'],
                    'program' => $program,
                    'department' => Program::DEPARTMENT_NAME,
                    'year_level' => $row['year_level'],
                    'semester' => $row['semester'],
                    'is_active' => true,
                    'sort_order' => ++$sort,
                ]);
            }
        }

        CourseUnits::apply();
    }

    public function down(): void
    {
        Course::where('program', 'BSEnSE')->delete();
        Course::where('program', 'BSIT')->where('code', 'ITE132')->delete();
        Course::where('program', 'BSIT')->where('code', 'ITE114')->update(['semester' => '1st']);
        Course::where('program', 'BSIT')->update(['lecture_units' => null, 'lab_units' => null]);
    }
};
