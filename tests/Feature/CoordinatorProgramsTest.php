<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use App\Services\TeacherLoadService;
use App\Support\CoordinatorDepartment;
use App\Support\CourseCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoordinatorProgramsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $username): User
    {
        return User::where('username', $username)->firstOrFail();
    }

    /** @param list<string> $extra */
    private function coordinator(string $home = 'BSIT', array $extra = []): User
    {
        $coordinator = $this->user('coordinator');
        $coordinator->employee->update(['program' => $home]);
        $coordinator->employee->syncExtraPrograms($extra);

        return $coordinator->fresh(['employee.extraPrograms']);
    }

    private function facultyIn(string $program): User
    {
        $faculty = $this->user('faculty');
        $faculty->employee->update(['program' => $program]);

        return $faculty->fresh('employee');
    }

    public function test_dean_creates_coordinator_with_extra_programs_and_home_is_ignored(): void
    {
        $this->actingAs($this->user('dean'))
            ->post(route('dean.store-coordinator'), [
                '_form' => 'coordinator',
                'full_name' => 'Multi Program Coordinator',
                'program' => 'BSIT',
                'extra_programs' => ['BSCpE', 'BLIS', 'BSIT'],
                'username' => 'multi-coor',
                'password' => 'Test-only-password-2026',
                'course_ids' => Course::active()->where('program', 'BSIT')->take(1)->pluck('id')->all(),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dean.employees'));

        $coordinator = $this->user('multi-coor');
        $this->assertEqualsCanonicalizing(['BSIT', 'BSCpE', 'BLIS'], CoordinatorDepartment::programs($coordinator));
        $this->assertEqualsCanonicalizing(['BSCpE', 'BLIS'], $coordinator->employee->extraProgramCodes());
    }

    public function test_dean_can_change_and_clear_extra_programs(): void
    {
        $coordinator = $this->coordinator('BSIT', ['BSCpE', 'BLIS']);
        $employee = $coordinator->employee;
        $payload = [
            'full_name' => $employee->full_name,
            'program' => 'BSIT',
            'course_ids' => $coordinator->assignedCourses()->pluck('courses.id')->all(),
        ];

        $this->actingAs($this->user('dean'))
            ->patch(route('dean.update-employee', $employee->employee_id), $payload + ['extra_programs' => ['BLIS']])
            ->assertSessionHasNoErrors();
        $this->assertSame(['BLIS'], $employee->fresh()->extraProgramCodes());

        $this->patch(route('dean.update-employee', $employee->employee_id), $payload)->assertSessionHasNoErrors();
        $this->assertSame([], $employee->fresh()->extraProgramCodes());

        $this->patch(route('dean.update-employee', $employee->employee_id), $payload + ['extra_programs' => ['NOPE']])
            ->assertSessionHasErrors('extra_programs.0');
    }

    public function test_forms_show_also_handles_and_subject_label_is_not_overwritten(): void
    {
        $coordinator = $this->coordinator('BSIT', ['BSCpE']);

        $this->actingAs($this->user('dean'))
            ->get(route('dean.employees', ['tab' => 'createCoordinator']))
            ->assertOk()
            ->assertSee('data-extra-programs', false)
            ->assertSee('Assigned subjects *', false)
            ->assertDontSee('Engineering *', false);

        $edit = $this->get(route('dean.edit-employee', $coordinator->employee->employee_id))
            ->assertOk()
            ->assertSee('Assigned subjects', false);
        $this->assertMatchesRegularExpression('/name="extra_programs\[\]" value="BSCpE"\s+checked/', $edit->getContent());
        $this->assertMatchesRegularExpression('/name="extra_programs\[\]" value="BSIT"\s+disabled/', $edit->getContent());

        $this->get(route('dean.employee-profile', $coordinator->employee->employee_id))
            ->assertOk()
            ->assertSee('Also handles');
    }

    public function test_extra_programs_widen_the_coordinator_scope(): void
    {
        $faculty = $this->facultyIn('BSCpE');
        $cpeCourse = Course::active()->where('program', 'BSCpE')->get()
            ->first(fn (Course $course) => preg_match('/^[A-Za-z]{2,4}\d{2,4}$/', $course->code));

        $homeOnly = $this->coordinator('BSIT');
        $this->assertSame(['BSIT'], CourseCatalog::programsForUser($homeOnly));
        $this->assertFalse(CoordinatorDepartment::handles($homeOnly, 'BSCpE'));
        $this->assertFalse(app(TeacherLoadService::class)->facultyQuery($homeOnly)->whereKey($faculty->employee->employee_id)->exists());
        $this->actingAs($homeOnly)
            ->patch(route('coordinator.courses.update', $cpeCourse), ['code' => $cpeCourse->code, 'title' => 'Renamed'])
            ->assertForbidden();

        $multi = $this->coordinator('BSIT', ['BSCpE', 'BLIS']);
        $this->assertEqualsCanonicalizing(['BSIT', 'BSCpE', 'BLIS'], CourseCatalog::programsForUser($multi));
        $this->assertTrue(app(TeacherLoadService::class)->facultyQuery($multi)->whereKey($faculty->employee->employee_id)->exists());
        $this->assertTrue(CourseCatalog::queryForUser($multi)->whereKey($cpeCourse->id)->exists());

        $this->actingAs($multi)
            ->patch(route('coordinator.courses.update', $cpeCourse), ['code' => $cpeCourse->code, 'title' => 'Renamed'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Renamed', $cpeCourse->fresh()->title);

        $this->get(route('coordinator.courses', ['program' => 'bscpe']))
            ->assertOk()
            ->assertSee($cpeCourse->code)
            ->assertSee('BLIS');

        $this->post(route('coordinator.courses.store'), ['code' => 'CPE901', 'title' => 'New CpE Subject', 'program' => 'BSCpE'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('courses', ['code' => 'CPE901', 'program' => 'BSCpE']);

        $this->post(route('coordinator.courses.store'), ['code' => 'ENS901', 'title' => 'Outside', 'program' => 'BSEnSE'])
            ->assertSessionHasErrors('program');
    }

    public function test_dean_access_is_unchanged(): void
    {
        $dean = $this->user('dean');

        $this->assertNull(CourseCatalog::programsForUser($dean));
        $this->assertSame([], CoordinatorDepartment::programs($dean));
    }
}
