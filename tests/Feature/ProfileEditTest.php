<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\{Course, SchoolYear, TeacherLoad};
use App\Support\SchoolTerm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileEditTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $username): User
    {
        return User::where('username', $username)->firstOrFail();
    }

    public function test_profile_page_renders_as_panel_for_every_role(): void
    {
        foreach (['dean', 'coordinator', 'faculty', 'secretary'] as $username) {
            $this->actingAs($this->user($username))
                ->get(route('profile.edit'))
                ->assertOk()
                ->assertSee('data-profile-panel', false)
                ->assertSee('id="change-password"', false)
                ->assertSee('Account information')
                ->assertDontSee('route-profile-edit #mobileMenuToggle', false);
        }
    }

    public function test_only_dean_gets_an_employee_number_input(): void
    {
        $this->actingAs($this->user('dean'))->get(route('profile.edit'))
            ->assertSee('name="employee_no"', false);

        $this->actingAs($this->user('faculty'))->get(route('profile.edit'))
            ->assertDontSee('name="employee_no"', false)
            ->assertSee('Employee number');
    }

    public function test_faculty_save_keeps_employee_number_and_name(): void
    {
        $faculty = $this->user('faculty');
        $faculty->employee->update(['employee_no' => 'FAC001', 'full_name' => 'Robert Johnson']);

        $this->actingAs($faculty)
            ->post(route('profile.update'), [
                'email' => 'robert@example.com',
                'employee_no' => '999',
                'full_name' => 'Someone Else',
            ])
            ->assertSessionHasNoErrors();

        $employee = $faculty->employee->fresh();
        $this->assertSame('FAC001', $employee->employee_no);
        $this->assertSame('Robert Johnson', $employee->full_name);
        $this->assertSame('robert@example.com', $faculty->fresh()->email);
    }

    public function test_coordinator_save_does_not_blank_employee_number(): void
    {
        $coordinator = $this->user('coordinator');
        $coordinator->employee->update(['employee_no' => 'SITE-BSIT-COOR001']);

        $this->actingAs($coordinator)
            ->post(route('profile.update'), ['full_name' => 'Jane Smith', 'email' => ''])
            ->assertSessionHasNoErrors();

        $this->assertSame('SITE-BSIT-COOR001', $coordinator->employee->fresh()->employee_no);
        $this->assertSame('Jane Smith', $coordinator->employee->fresh()->full_name);
    }

    public function test_dean_can_save_lettered_employee_numbers_but_not_symbols(): void
    {
        $dean = $this->user('dean');

        $this->actingAs($dean)
            ->post(route('profile.update'), ['full_name' => 'Dr. John Dean', 'employee_no' => 'DEAN-001'])
            ->assertSessionHasNoErrors();
        $this->assertSame('DEAN-001', $dean->employee->fresh()->employee_no);

        $this->post(route('profile.update'), ['full_name' => 'Dr. John Dean', 'employee_no' => 'DEAN 001!'])
            ->assertSessionHasErrors('employee_no');
    }

    public function test_password_change_still_works_and_errors_reopen_password_tab(): void
    {
        $faculty = $this->user('faculty');
        $faculty->forceFill(['password' => Hash::make('old-password-1')])->save();

        $this->actingAs($faculty)
            ->from(route('profile.edit'))
            ->post(route('profile.change-password'), [
                'current_password' => 'wrong-password',
                'new_password' => 'new-password-1',
                'new_password_confirmation' => 'new-password-1',
            ])
            ->assertSessionHasErrors('current_password');

        $this->get(route('profile.edit'))->assertSee('data-initial-tab="password"', false);

        $this->post(route('profile.change-password'), [
            'current_password' => 'old-password-1',
            'new_password' => 'new-password-1',
            'new_password_confirmation' => 'new-password-1',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('new-password-1', $faculty->fresh()->password));
    }

    public function test_existing_assignments_show_in_profile_and_loads_without_creating_a_schedule(): void
    {
        $faculty = $this->user('faculty');
        $courses = Course::active()->where('program', $faculty->employee->program)->take(2)->get();
        foreach ($courses as $course) {
            $course->update(['semester' => SchoolTerm::current()]);
        }
        $faculty->assignedCourses()->sync([$courses[0]->id]);
        $loadCount = TeacherLoad::count();

        $this->actingAs($faculty)->get(route('profile.edit'))->assertOk()
            ->assertSee('Current teaching assignments')->assertSee($courses[0]->title)
            ->assertSee('Schedules will appear')->assertDontSee('teaching-summary__schedule', false);
        $this->get(route('teacher-loads.index'))->assertOk()->assertSee($courses[0]->title);
        $this->assertSame($loadCount, TeacherLoad::count());

        $faculty->assignedCourses()->sync([$courses[1]->id]);
        $this->get(route('profile.edit'))->assertOk()->assertSee($courses[1]->title)->assertDontSee($courses[0]->title);
        $this->post(route('profile.update'), ['email' => '', 'course_ids' => [$courses[0]->id]])->assertSessionHasNoErrors();
        $this->assertSame([$courses[1]->id], $faculty->assignedCourses()->pluck('courses.id')->all());
    }

    public function test_only_own_current_finalized_schedule_appears_on_profile(): void
    {
        $faculty = $this->user('faculty');
        $course = Course::active()->where('program', $faculty->employee->program)->firstOrFail();
        $course->update(['semester' => SchoolTerm::current()]);
        $faculty->assignedCourses()->sync([$course->id]);
        $load = TeacherLoad::create([
            'employee_id' => $faculty->employee->employee_id, 'school_year_id' => SchoolYear::activeId(),
            'semester' => SchoolTerm::current(), 'program' => $faculty->employee->program,
            'faculty_name' => $faculty->employee->full_name, 'department' => 'SITE',
            'academic_year' => 'Current', 'employment_status' => 'Full-Time Faculty', 'status' => 'draft',
        ]);
        $load->items()->create([
            'kind' => 'course', 'course_id' => $course->id, 'course_code' => $course->code, 'title' => $course->title,
            'section' => 'IT 4A', 'position' => 0,
            'schedules' => [['days' => ['Mon', 'Thu'], 'start' => '13:30', 'end' => '14:30', 'mode' => 'Lec', 'room' => 'Published Room 103']],
        ]);
        $this->actingAs($faculty)->get(route('profile.edit'))->assertOk()->assertDontSee('Published Room 103');
        $load->update(['status' => 'finalized', 'finalized_at' => now()]);
        $this->get(route('profile.edit'))->assertOk()->assertSee('Published Room 103')->assertSee('1:30 PM')->assertSee('Section IT 4A')
            ->assertViewHas('teachingAssignments', fn ($summary) => $summary['rows']->count() === 1);

        $load->update(['semester' => SchoolTerm::current() === SchoolTerm::FIRST ? SchoolTerm::SECOND : SchoolTerm::FIRST]);
        $this->get(route('profile.edit'))->assertOk()->assertDontSee('Published Room 103');
        $load->update(['semester' => SchoolTerm::current(), 'employee_id' => $this->user('coordinator')->employee->employee_id]);
        $this->get(route('profile.edit'))->assertOk()->assertDontSee('Published Room 103');
        $this->get(route('teacher-loads.index', ['employee_id' => $load->employee_id]))->assertOk()->assertDontSee('Published Room 103');
    }
}
