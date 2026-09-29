<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\DashboardLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountCreationTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $username): User
    {
        return User::where('username', $username)->firstOrFail();
    }

    private function coordinatorIn(string $program): User
    {
        $coordinator = $this->user('coordinator');
        $coordinator->employee->update(['program' => $program]);

        return $coordinator->fresh();
    }

    private function courseIds(string $program, int $count = 1): array
    {
        return Course::active()->where('program', $program)->orderBy('id')->take($count)->pluck('id')->all();
    }

    private function facultyPayload(string $username, string $program, array $courseIds = []): array
    {
        return array_filter([
            '_form' => 'faculty',
            'full_name' => 'Test ' . $username,
            'program' => $program,
            'faculty_type' => 'full_time',
            'username' => $username,
            'password' => 'Test-only-password-2026',
            'course_ids' => $courseIds,
        ], fn ($value) => $value !== []);
    }

    public function test_dean_create_forms_render_picker_on_initial_load(): void
    {
        $this->actingAs($this->user('dean'))
            ->get(route('dean.employees', ['tab' => 'createFaculty']))
            ->assertOk()
            ->assertSee('data-course-guide="coordinatorCourses"', false)
            ->assertSee('data-course-guide="facultyCourses"', false)
            ->assertSee('data-program-select="facultyDepartment"', false)
            ->assertSee('Select a program to load its subjects.');
    }

    public function test_validation_errors_restore_program_and_selected_subjects(): void
    {
        [$first, $second] = $this->courseIds('BSIT', 2);
        $payload = $this->facultyPayload('restore-test', 'BSIT', [$first, $second]);
        unset($payload['password']);

        $this->actingAs($this->user('dean'))
            ->from(route('dean.employees', ['tab' => 'createFaculty']))
            ->followingRedirects()
            ->post(route('dean.store-faculty'), $payload)
            ->assertOk()
            ->assertSee('<option value="BSIT" selected>', false)
            ->assertSee('value="' . $first . '" checked', false)
            ->assertSee('value="' . $second . '" checked', false)
            ->assertSee(Course::find($first)->code);

        $this->assertDatabaseMissing('users', ['username' => 'restore-test']);
    }

    public function test_dean_creates_faculty_and_coordinator_with_subjects(): void
    {
        $dean = $this->user('dean');
        $bsit = $this->courseIds('BSIT', 2);
        $blis = $this->courseIds('BLIS', 1);

        $this->actingAs($dean)
            ->post(route('dean.store-faculty'), $this->facultyPayload('dean-made-faculty', 'BSIT', $bsit))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dean.employees'));

        $this->post(route('dean.store-coordinator'), [
            '_form' => 'coordinator',
            'full_name' => 'Dean Made Coordinator',
            'program' => 'BLIS',
            'username' => 'dean-made-coor',
            'password' => 'Test-only-password-2026',
            'course_ids' => $blis,
        ])->assertSessionHasNoErrors()->assertRedirect(route('dean.employees'));

        $faculty = $this->user('dean-made-faculty');
        $coordinator = $this->user('dean-made-coor');
        $this->assertEqualsCanonicalizing($bsit, $faculty->assignedCourses()->pluck('courses.id')->all());
        $this->assertEqualsCanonicalizing($blis, $coordinator->assignedCourses()->pluck('courses.id')->all());
        $this->assertSame(2, $coordinator->role_id);

        $log = DashboardLog::where('target_user_id', $faculty->id)->where('activity_type', 'account_created')->firstOrFail();
        $this->assertSame($dean->id, $log->user_id);
        $this->assertStringContainsString('Faculty Employee', $log->activity);
        $this->assertStringContainsString('Program: BSIT', $log->activity);
        $this->assertStringContainsString(Course::find($bsit[0])->code, $log->activity);
    }

    public function test_subjects_are_required_when_program_has_a_curriculum(): void
    {
        $this->actingAs($this->user('dean'))
            ->post(route('dean.store-faculty'), $this->facultyPayload('no-subject-faculty', 'BSIT'))
            ->assertSessionHasErrors('course_ids');

        $this->post(route('dean.store-coordinator'), [
            '_form' => 'coordinator', 'full_name' => 'No Subject Coor', 'program' => 'BSCpE',
            'username' => 'no-subject-coor', 'password' => 'Test-only-password-2026',
        ])->assertSessionHasErrors('course_ids');

        $this->actingAs($this->coordinatorIn('BSIT'))
            ->post(route('coordinator.store-faculty'), $this->facultyPayload('coor-no-subject', 'BLIS'))
            ->assertSessionHasErrors('course_ids');

        $this->assertDatabaseMissing('users', ['username' => 'no-subject-faculty']);
        $this->assertDatabaseMissing('users', ['username' => 'no-subject-coor']);
        $this->assertDatabaseMissing('users', ['username' => 'coor-no-subject']);
    }

    public function test_empty_curriculum_allows_account_and_shows_empty_state(): void
    {
        Course::where('program', 'BSEnSE')->delete();
        $coordinator = $this->coordinatorIn('BSEnSE');

        $this->actingAs($coordinator)
            ->get(route('coordinator.create-faculty'))
            ->assertOk()
            ->assertSee('<option value="BSEnSE" selected>', false)
            ->assertSee('No curriculum has been set up for');

        $this->post(route('coordinator.store-faculty'), $this->facultyPayload('bsense-faculty', 'BSEnSE'))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('coordinator.faculty'));

        $this->assertSame(0, $this->user('bsense-faculty')->assignedCourses()->count());
    }

    public function test_invalid_or_malformed_subject_ids_are_rejected(): void
    {
        $this->actingAs($this->user('dean'))
            ->postJson(route('dean.store-faculty'), $this->facultyPayload('bad-ids', 'BSIT', [999999]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('course_ids.0');

        $this->postJson(route('dean.store-faculty'), $this->facultyPayload('bad-ids', 'BSIT', ['abc']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('course_ids.0');

        $this->postJson(route('dean.store-faculty'), array_merge($this->facultyPayload('bad-ids', 'BSIT'), ['course_ids' => 'not-an-array']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('course_ids');

        $this->assertDatabaseMissing('users', ['username' => 'bad-ids']);
    }

    public function test_coordinator_directory_has_create_action_and_is_department_wide(): void
    {
        $coordinator = $this->coordinatorIn('BLIS');
        $faculty = $this->user('faculty');
        $faculty->employee->update(['program' => 'BSIT']);

        $this->actingAs($coordinator)
            ->get(route('coordinator.faculty'))
            ->assertOk()
            ->assertSee(route('coordinator.create-faculty'), false)
            ->assertSee('Create Faculty')
            ->assertSee($faculty->employee->full_name);

        $this->get(route('coordinator.faculty-profile', $faculty->employee->employee_id))->assertOk();
        $this->get(route('coordinator.edit-faculty', $faculty->employee->employee_id))
            ->assertOk()
            ->assertSee('data-program-select="editFacultyProgram"', false);
    }

    public function test_coordinator_creates_faculty_across_site_programs(): void
    {
        $coordinator = $this->coordinatorIn('BSIT');
        $this->actingAs($coordinator);

        foreach (['BLIS', 'BSIT', 'BSCpE'] as $program) {
            $courseIds = $this->courseIds($program, 2);
            $username = strtolower('coor-' . $program);

            $this->post(route('coordinator.store-faculty'), $this->facultyPayload($username, $program, $courseIds))
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('coordinator.faculty'));

            $created = $this->user($username);
            $this->assertSame($program, $created->employee->program);
            $this->assertSame(3, $created->role_id);
            $this->assertEqualsCanonicalizing($courseIds, $created->assignedCourses()->pluck('courses.id')->all());
            $this->assertTrue(DashboardLog::where('user_id', $coordinator->id)->where('target_user_id', $created->id)->exists());
        }

        $blis = $this->getJson(route('coordinator.courses.by-program', ['dept' => 'BLIS']))->assertOk()->json();
        $this->assertNotEmpty($blis);
        $this->assertSame(
            count($blis),
            Course::active()->where('program', 'BLIS')->whereIn('id', array_column($blis, 'id'))->count()
        );
    }

    public function test_coordinator_can_update_subjects_for_faculty_in_another_program(): void
    {
        $coordinator = $this->coordinatorIn('BLIS');
        $faculty = $this->user('faculty');
        $faculty->employee->update(['program' => 'BSIT']);
        $newCourses = $this->courseIds('BSCpE', 2);

        $this->actingAs($coordinator)
            ->patch(route('coordinator.update-faculty', $faculty->employee->employee_id), [
                'full_name' => $faculty->employee->full_name,
                'program' => 'BSCpE',
                'faculty_type' => 'full_time',
                'course_ids' => $newCourses,
            ])
            ->assertSessionHasNoErrors();

        $faculty->refresh();
        $this->assertSame('BSCpE', $faculty->employee->program);
        $current = $faculty->assignedCourses()->where('courses.program', 'BSCpE')->pluck('courses.id')->all();
        $this->assertEqualsCanonicalizing($newCourses, $current);
    }

    public function test_subjects_outside_selected_program_are_rejected(): void
    {
        $bsitCourse = $this->courseIds('BSIT')[0];

        $this->actingAs($this->coordinatorIn('BSIT'))
            ->postJson(route('coordinator.store-faculty'), $this->facultyPayload('cross-program', 'BLIS', [$bsitCourse]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('course_ids.0');

        $faculty = $this->user('faculty');
        $this->patchJson(route('coordinator.update-faculty', $faculty->employee->employee_id), [
            'full_name' => $faculty->employee->full_name,
            'program' => 'BLIS',
            'faculty_type' => 'full_time',
            'course_ids' => [$bsitCourse],
        ])->assertUnprocessable()->assertJsonValidationErrors('course_ids.0');

        $this->actingAs($this->user('dean'))
            ->postJson(route('dean.store-coordinator'), [
                'full_name' => 'Cross Coor', 'program' => 'BSCpE', 'username' => 'cross-coor',
                'password' => 'Test-only-password-2026', 'course_ids' => [$bsitCourse],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('course_ids.0');

        $this->assertDatabaseMissing('users', ['username' => 'cross-program']);
        $this->assertDatabaseMissing('users', ['username' => 'cross-coor']);
    }

    public function test_unauthorized_roles_are_blocked(): void
    {
        $courseIds = $this->courseIds('BSIT');
        $coordinatorPayload = [
            'full_name' => 'Escalated Coor', 'program' => 'BSIT', 'username' => 'escalated-coor',
            'password' => 'Test-only-password-2026', 'course_ids' => $courseIds,
        ];

        $this->get(route('coordinator.create-faculty'))->assertRedirect(route('login'));

        $this->actingAs($this->user('coordinator'))
            ->post(route('dean.store-coordinator'), $coordinatorPayload)
            ->assertForbidden();
        $this->post(route('dean.store-faculty'), $this->facultyPayload('coor-via-dean', 'BSIT', $courseIds))->assertForbidden();
        $this->getJson(route('dean.courses.by-program', ['dept' => 'BSIT']))->assertForbidden();

        $this->actingAs($this->user('faculty'))
            ->get(route('coordinator.create-faculty'))
            ->assertForbidden();
        $this->post(route('coordinator.store-faculty'), $this->facultyPayload('faculty-made', 'BSIT', $courseIds))->assertForbidden();
        $this->getJson(route('coordinator.courses.by-program', ['dept' => 'BSIT']))->assertForbidden();
        $this->post(route('dean.store-coordinator'), $coordinatorPayload)->assertForbidden();

        foreach (['escalated-coor', 'coor-via-dean', 'faculty-made'] as $username) {
            $this->assertDatabaseMissing('users', ['username' => $username]);
        }
    }
}
