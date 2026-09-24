<?php

namespace Tests\Feature;

use App\Models\{Course, TeacherLoad, User};
use App\Support\SchoolTerm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherLoadTest extends TestCase
{
    use RefreshDatabase;

    private function setupFaculty(): array
    {
        $dean = User::where('username', 'dean')->firstOrFail();
        $faculty = User::where('username', 'faculty')->firstOrFail();
        $course = Course::active()->where('program', $faculty->employee->program)->firstOrFail();
        $course->update(['semester' => SchoolTerm::FIRST]);
        $faculty->assignedCourses()->sync([$course->id]);

        return [$dean, $faculty, $course];
    }

    private function payload(User $faculty, Course $course): array
    {
        return [
            'employee_id' => $faculty->employee->employee_id,
            'school_year_id' => \App\Models\SchoolYear::activeId(),
            'semester' => SchoolTerm::FIRST,
            'employment_status' => 'Full-Time Faculty',
            'items' => [[
                'kind' => 'course', 'course_id' => $course->id, 'title' => null,
                'section' => 'IT 4A', 'lecture_units' => 2, 'lab_units' => 1,
                'load_equivalent' => 1.66, 'class_size' => 30,
                'schedules' => [['days' => ['Mon', 'Thu'], 'start' => '13:30', 'end' => '14:30', 'mode' => 'Lec', 'room' => 'LR 103']],
            ], [
                'kind' => 'duty', 'course_id' => null, 'title' => 'Program Coordinator — BSIT',
                'section' => null, 'lecture_units' => 0, 'lab_units' => 0,
                'load_equivalent' => 4, 'class_size' => null, 'schedules' => [],
            ]],
        ];
    }

    public function test_faculty_creation_saves_full_time_or_shared_classification(): void
    {
        $dean = User::where('username', 'dean')->firstOrFail();
        $page = $this->actingAs($dean)->get(route('dean.employees', ['tab' => 'createFaculty']))
            ->assertOk()->assertSee('Full-Time Faculty')->assertSee('Shared Faculty');

        $this->post(route('dean.store-faculty'), [
            '_form' => 'faculty', 'full_name' => 'Shared Faculty Test', 'program' => 'BSIT',
            'faculty_type' => 'shared', 'username' => 'shared-faculty-test', 'password' => 'Test-password-2026',
        ])->assertRedirect(route('dean.employees'));

        $faculty = User::where('username', 'shared-faculty-test')->firstOrFail();
        $this->assertSame('shared', $faculty->employee->faculty_type);
        $this->assertSame('Shared Faculty', $faculty->employee->facultyTypeLabel());

        $this->getJson(route('teacher-loads.options', ['employee_id' => $faculty->employee->employee_id, 'semester' => SchoolTerm::FIRST]))
            ->assertOk()->assertJsonPath('employment_status', 'Shared Faculty');
    }

    public function test_manager_can_create_preview_finalize_and_export_teacher_load(): void
    {
        [$dean, $faculty, $course] = $this->setupFaculty();
        $data = $this->payload($faculty, $course);

        $this->actingAs($dean)->get(route('teacher-loads.index'))->assertOk()->assertSee('Create Teacher’s Load');
        $this->postJson(route('teacher-loads.preview'), $data)->assertOk()->assertHeader('content-type', 'application/pdf');
        $created = $this->postJson(route('teacher-loads.store'), $data)->assertCreated()->json();

        $load = TeacherLoad::findOrFail($created['id']);
        $this->assertSame('draft', $load->status);
        $this->assertSame('5.660', $load->total_load);
        $this->assertSame('3.00', $load->total_units);
        $this->assertCount(2, $load->items);

        $this->postJson(route('teacher-loads.finalize', $load), ['lock_version' => $load->lock_version])->assertOk()->assertJsonPath('status', 'finalized');
        $this->get(route('teacher-loads.pdf', $load))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_faculty_only_sees_own_finalized_load_and_cannot_modify_it(): void
    {
        [$dean, $faculty, $course] = $this->setupFaculty();
        $created = $this->actingAs($dean)->postJson(route('teacher-loads.store'), $this->payload($faculty, $course))->assertCreated()->json();
        $load = TeacherLoad::findOrFail($created['id']);

        $this->actingAs($faculty)->get(route('teacher-loads.index'))->assertOk()->assertSee('No finalized teaching loads available yet.');
        $this->get(route('teacher-loads.pdf', $load))->assertNotFound();
        $this->patchJson(route('teacher-loads.update', $load), $this->payload($faculty, $course))->assertForbidden();

        $this->actingAs($dean)->postJson(route('teacher-loads.finalize', $load), ['lock_version' => $load->lock_version])->assertOk();
        $this->actingAs($faculty)->get(route('teacher-loads.index'))->assertOk()->assertSee($load->faculty_name);
        $this->get(route('teacher-loads.pdf', $load))->assertOk();
    }

    public function test_unassigned_course_and_overlapping_schedule_are_rejected(): void
    {
        [$dean, $faculty, $course] = $this->setupFaculty();
        $other = Course::active()->where('program', $faculty->employee->program)->whereKeyNot($course->id)->firstOrFail();
        $data = $this->payload($faculty, $course);
        $data['items'][0]['course_id'] = $other->id;
        $this->actingAs($dean)->postJson(route('teacher-loads.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('items.0.course_id');

        $data = $this->payload($faculty, $course);
        $data['items'][0]['schedules'][] = ['days' => ['Mon'], 'start' => '14:00', 'end' => '15:00', 'mode' => 'Lab', 'room' => 'Lab 1'];
        $this->postJson(route('teacher-loads.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('items.0.schedules.1.start');
    }
}
