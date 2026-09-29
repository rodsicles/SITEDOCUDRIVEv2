<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Program;
use App\Models\TeacherLoad;
use App\Models\User;
use App\Support\CourseUnits;
use App\Support\SchoolTerm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CourseUnitsAndUiTest extends TestCase
{
    use RefreshDatabase;

    private function course(string $program, string $code): Course
    {
        return Course::where('program', $program)->where('code', $code)->firstOrFail();
    }

    public function test_migrations_seed_units_for_every_program(): void
    {
        $this->assertSame(3.0, $this->course('BLIS', 'LIS101')->lecture_units);
        $this->assertSame(0.0, $this->course('BLIS', 'LIS101')->lab_units);
        $this->assertSame(2.0, $this->course('BLIS', 'ICT101Bis')->lecture_units);
        $this->assertSame(1.0, $this->course('BSCpE', 'CHEM1')->lab_units);
        $this->assertSame(9.0, $this->course('BLIS', 'BLIS401')->lecture_units);
        $this->assertSame(3.0, $this->course('BSIT', 'ITE101')->lab_units);
        $this->assertSame(3.0, $this->course('BSIT', 'ITE105')->lecture_units);
        $this->assertSame('Summer', $this->course('BSIT', 'ITE114')->semester);
        $this->assertNull($this->course('BSIT', 'ITE132')->lecture_units);
        $this->assertSame(48, Course::active()->where('program', 'BSEnSE')->count());
        $this->assertSame(4.0, $this->course('BSEnSE', 'CE102')->lecture_units);
        $this->assertSame(9.0, $this->course('BSEnSE', 'ENSE119')->lab_units);
        $this->assertSame(5, $this->course('BSEnSE', 'ENGG120')->year_level);
        $this->assertSame('Computer Fundamentals and Programming', $this->course('BSEnSE', 'ENGG102')->title);
        $this->assertSame('Engineering Economy', $this->course('BSCpE', 'ENGG102')->title);
    }

    public function test_import_matches_codes_ignoring_spacing_and_is_repeatable(): void
    {
        $this->assertSame(CourseUnits::normalize('ITE101'), CourseUnits::normalize('ITE 101'));
        $this->assertSame(CourseUnits::normalize('ITE101'), CourseUnits::normalize('ite-101'));

        $chem = $this->course('BSCpE', 'CHEM1');
        $chem->forceFill(['code' => 'CHEM 1', 'lecture_units' => null, 'lab_units' => null])->save();

        $first = CourseUnits::apply();
        $this->assertSame(3.0, $chem->fresh()->lecture_units);
        $this->assertSame(1.0, $chem->fresh()->lab_units);
        $this->assertGreaterThanOrEqual(1, $first['updated']);

        $second = CourseUnits::apply();
        $this->assertSame(0, $second['updated']);
        $this->artisan('courses:sync-units')->assertSuccessful();
    }

    public function test_bsense_uses_the_official_program_name(): void
    {
        $name = 'Bachelor of Science in Environmental and Sanitary Engineering';
        $this->assertSame($name, Program::OPTIONS['BSEnSE']);
        $this->assertSame($name, DB::table('programs')->where('code', 'BSEnSE')->value('name'));
    }

    public function test_teacher_load_options_include_catalog_units_and_saved_loads_keep_overrides(): void
    {
        $dean = User::where('username', 'dean')->firstOrFail();
        $faculty = User::where('username', 'faculty')->firstOrFail();
        $faculty->employee->update(['program' => 'BSCpE']);
        $chem = $this->course('BSCpE', 'CHEM1');
        $chem->update(['semester' => SchoolTerm::FIRST]);
        $faculty->assignedCourses()->sync([$chem->id]);

        $options = $this->actingAs($dean)->getJson(route('teacher-loads.options', [
            'employee_id' => $faculty->employee->employee_id,
            'semester' => SchoolTerm::FIRST,
        ]))->assertOk()->json('courses');

        $this->assertEquals(3, $options[0]['lecture_units']);
        $this->assertEquals(1, $options[0]['lab_units']);

        $created = $this->postJson(route('teacher-loads.store'), [
            'employee_id' => $faculty->employee->employee_id,
            'school_year_id' => \App\Models\SchoolYear::activeId(),
            'semester' => SchoolTerm::FIRST,
            'employment_status' => 'Full-Time Faculty',
            'items' => [[
                'kind' => 'course', 'course_id' => $chem->id, 'title' => null, 'section' => 'CpE 1A',
                'lecture_units' => 2, 'lab_units' => 2, 'load_equivalent' => 0, 'class_size' => 35,
                'schedules' => [['days' => ['Tue'], 'start' => '08:00', 'end' => '09:30', 'mode' => 'Lec', 'room' => 'EN 201']],
            ]],
        ])->assertCreated()->json();

        $chem->update(['lecture_units' => 4, 'lab_units' => 0]);
        $item = TeacherLoad::findOrFail($created['id'])->items()->firstOrFail();
        $this->assertEquals(2, $item->lecture_units);
        $this->assertEquals(2, $item->lab_units);
        $this->assertEquals(0, $item->load_equivalent);
    }

    public function test_teacher_load_dialog_has_step_navigation_and_units_edit_action(): void
    {
        $this->actingAs(User::where('username', 'dean')->firstOrFail())
            ->get(route('teacher-loads.index'))
            ->assertOk()
            ->assertSee('data-go-step="1"', false)
            ->assertSee('data-go-step="2"', false)
            ->assertSee('data-units-edit', false)
            ->assertSee('Edit units')
            ->assertSee('id="tl-context-confirm"', false);
    }

    public function test_picker_shows_fifth_year_only_when_program_has_it(): void
    {
        Course::create(['code' => 'ENSE501', 'title' => 'Fifth Year Design', 'program' => 'BSEnSE', 'year_level' => 5, 'semester' => '1st', 'is_active' => true]);
        $coordinator = User::where('username', 'coordinator')->firstOrFail();
        $coordinator->employee->update(['program' => 'BSEnSE']);

        $this->actingAs($coordinator->fresh())
            ->get(route('coordinator.create-faculty'))
            ->assertOk()
            ->assertSee('ENSE501')
            ->assertSee('data-guide-year="5" aria-pressed="false">5Y', false);

        $coordinator->employee->update(['program' => 'BSIT']);
        $this->actingAs($coordinator->fresh())
            ->get(route('coordinator.create-faculty'))
            ->assertOk()
            ->assertSee('data-guide-year="5" aria-pressed="false" hidden>5Y', false);
    }

    public function test_archive_page_uses_shared_dialogs(): void
    {
        $this->actingAs(User::where('username', 'dean')->firstOrFail())
            ->get(route('dean.archives.index'))
            ->assertOk()
            ->assertSee('<dialog id="archiveModal" class="ui-dialog ui-dialog--danger"', false)
            ->assertSee('data-ack-for="archiveSubmitBtn"', false)
            ->assertDontSee('bg-opacity-50', false);
    }

    public function test_login_branding_is_swapped(): void
    {
        $html = $this->get(route('login'))->assertOk()->getContent();

        $header = substr($html, strpos($html, 'login-portal-header'), 1200);
        $this->assertStringContainsString('Employee portal', $header);
        $this->assertStringNotContainsString('School of Information Technology and Engineering', $header);

        $panel = substr($html, strpos($html, 'login-auth-identity'), 1500);
        $this->assertStringContainsString('School of Information Technology and Engineering', $panel);
        $this->assertStringContainsString('St. Paul University Philippines', $panel);
        $this->assertStringContainsString('SITE DocuDrive', $panel);
    }
}
