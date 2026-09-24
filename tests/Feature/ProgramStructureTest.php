<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Program;
use App\Models\User;
use App\Support\CourseCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_four_programs_share_one_department_and_curricula_are_seeded(): void
    {
        $this->assertEqualsCanonicalizing(Program::codes(), Program::pluck('code')->all());
        $this->assertSame(1, Program::distinct()->count('department'));
        $this->assertSame(0, Course::where('program', 'BSEnSE')->count());
        $this->assertGreaterThan(0, Course::active()->where('program', 'BLIS')->count());
        $this->assertGreaterThan(0, Course::active()->where('program', 'BSCpE')->count());
        $this->assertGreaterThan(0, Course::active()->where('program', 'BSIT')->count());
        $this->assertGreaterThan(0, Course::where('legacy_department', 'Engineering')->whereNull('program')->count());
        $this->assertSame(0, Course::active()->whereNull('program')->count());
        $this->assertTrue(Course::where('program', 'BLIS')->where('code', 'LIS101')->where('semester', '1st')->exists());
        $this->assertTrue(Course::where('program', 'BSCpE')->where('code', 'ELEC101CPE')->exists());
        $this->assertTrue(Course::where('program', 'BSIT')->where('code', 'ITE101')->where('year_level', 1)->exists());
    }

    public function test_role_pages_render_with_current_program_fields(): void
    {
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Dashboard queries require MySQL; run against the isolated MySQL test database.');
        }
        foreach ([
            'dean' => ['dean.dashboard', 'dean.analytics', 'dean.employees', 'dean.courses', 'dean.create-task', 'dean.reports'],
            'coordinator' => ['coordinator.dashboard', 'coordinator.analytics', 'coordinator.faculty', 'coordinator.courses'],
            'faculty' => ['faculty.dashboard', 'faculty.analytics', 'faculty.profile', 'faculty.reports'],
        ] as $username => $routes) {
            $this->actingAs(User::where('username', $username)->firstOrFail());
            foreach ($routes as $route) $this->get(route($route))->assertOk();
        }
    }

    public function test_empty_program_catalog_and_dashboards_work_without_fallback_courses(): void
    {
        $coordinator = User::where('username', 'coordinator')->firstOrFail();
        $coordinator->employee->update(['program' => 'BSEnSE']);
        $coordinator->refresh();
        $this->assertSame([], CourseCatalog::labelsForUser($coordinator));
        $this->actingAs($coordinator)->get(route('coordinator.courses'))->assertOk();
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'mysql') {
            $this->get(route('coordinator.dashboard'))->assertOk();
            $this->get(route('coordinator.analytics'))->assertOk();
        }
    }

    public function test_unassigned_coordinator_cannot_manage_current_catalog(): void
    {
        $coordinator = User::where('username', 'coordinator')->firstOrFail();
        $coordinator->employee->update(['program' => null]);
        $coordinator->refresh();
        $this->assertSame([], CourseCatalog::labelsForUser($coordinator));
        $this->actingAs($coordinator)->get(route('coordinator.courses'))->assertForbidden();
        $this->get(route('dean.employees'))->assertForbidden();
    }

    public function test_empty_program_allows_accounts_but_rejects_cross_program_assignments(): void
    {
        $dean = User::where('username', 'dean')->firstOrFail();
        $data = ['username' => 'blis-test-faculty', 'password' => 'Test-only-password-2026', 'full_name' => 'Program Test Faculty', 'program' => 'BLIS'];
        $this->actingAs($dean)->post(route('dean.store-faculty'), $data)->assertSessionHasNoErrors();
        $faculty = User::where('username', $data['username'])->firstOrFail();
        $this->assertSame('BLIS', $faculty->employee->program);
        $this->assertSame(0, $faculty->assignedCourses()->count());
        $data['username'] = 'cross-program-test';
        $data['course_ids'] = [Course::active()->where('program', 'BSIT')->firstOrFail()->id];
        $this->postJson(route('dean.store-faculty'), $data)->assertUnprocessable()->assertJsonValidationErrors('course_ids.0');
        $this->assertDatabaseMissing('users', ['username' => $data['username']]);
    }

    public function test_catalog_filters_and_course_editing_preserve_program_scope(): void
    {
        $dean = User::where('username', 'dean')->firstOrFail();
        $blis = $this->actingAs($dean)->getJson(route('dean.courses.by-program', ['dept' => 'BLIS']));
        $blis->assertOk();
        $this->assertNotEmpty($blis->json());
        $this->assertArrayHasKey('year_level', $blis->json()[0]);
        $this->assertArrayHasKey('semester', $blis->json()[0]);
        $this->actingAs($dean)->getJson(route('dean.courses.by-program', ['dept' => 'BSEnSE']))->assertExactJson([]);
        $this->post(route('dean.courses.store'), ['code' => 'TEST999', 'title' => 'Test fixture only', 'program' => 'BSIT'])->assertSessionHasNoErrors();
        $course = Course::where('code', 'TEST999')->firstOrFail();
        $this->patch(route('dean.courses.update', $course), ['code' => 'TEST999', 'title' => 'Edited test fixture'])->assertSessionHasNoErrors();
        $this->assertSame('Edited test fixture', $course->fresh()->title);
        $coordinator = User::where('username', 'coordinator')->firstOrFail();
        $coordinator->employee->update(['program' => 'BLIS']);
        $this->actingAs($coordinator->fresh())->patch(route('coordinator.courses.update', $course), ['code' => 'TEST999', 'title' => 'Not allowed'])->assertForbidden();
    }

    public function test_mapped_faculty_can_still_login(): void
    {
        $faculty = User::where('username', 'faculty')->firstOrFail();
        $faculty->update(['password' => \Illuminate\Support\Facades\Hash::make('Test-only-password-2026'), 'must_change_password' => false]);
        $this->post(route('login.post'), ['username' => 'faculty', 'password' => 'Test-only-password-2026'])->assertRedirect();
        $this->assertAuthenticatedAs($faculty);
    }
}
