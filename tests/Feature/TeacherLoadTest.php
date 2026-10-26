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
                'section' => 'BSIT 4 A', 'lecture_units' => 2, 'lab_units' => 1,
                'load_equivalent' => 1.66, 'class_size' => 30,
                'schedules' => [['days' => ['Mon', 'Thu'], 'start' => '13:30', 'end' => '14:30', 'mode' => 'Lec', 'room' => 'LR 103']],
            ], [
                'kind' => 'duty', 'course_id' => null, 'title' => 'Program Coordinator — BSIT',
                'section' => null, 'lecture_units' => 0, 'lab_units' => 0,
                'load_equivalent' => 4, 'class_size' => null, 'schedules' => [],
            ]],
        ];
    }

    public function test_section_choices_and_validation_preserve_old_snapshots(): void
    {
        [$dean, $faculty, $course] = $this->setupFaculty();
        $this->actingAs($dean)->getJson(route('teacher-loads.options', ['employee_id' => $faculty->employee->employee_id, 'semester' => SchoolTerm::FIRST]))
            ->assertOk()->assertJsonFragment(['name' => 'BSIT 4 A']);
        $data = $this->payload($faculty, $course);
        $data['items'][0]['section'] = 'Not a catalog section';
        $this->postJson(route('teacher-loads.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('items.0.section');
        $data = $this->payload($faculty, $course);
        $id = $this->postJson(route('teacher-loads.store'), $data)->assertCreated()->json('id');
        $load = TeacherLoad::findOrFail($id);
        \App\Models\CourseSection::where('name', 'BSIT 4 A')->update(['name' => 'BSIT 4 RENAMED', 'is_active' => false]);
        $this->assertSame('BSIT 4 A', $load->items()->where('kind', 'course')->first()->section);
        $data['lock_version'] = $load->lock_version;
        $this->patchJson(route('teacher-loads.update', $id), $data)->assertOk();
        $this->postJson(route('teacher-loads.finalize', $id), ['lock_version' => $load->fresh()->lock_version])->assertOk();
        $this->assertSame('BSIT 4 A', $load->fresh()->items()->where('kind', 'course')->first()->section);
        $new = $data; unset($new['lock_version']);
        $new['semester'] = SchoolTerm::SECOND;
        $this->postJson(route('teacher-loads.store'), $new)->assertUnprocessable();
    }

    public function test_drafts_view_includes_unpublished_loads_and_open_revisions_only(): void
    {
        [$dean, $faculty, $course] = $this->setupFaculty();
        $id = $this->actingAs($dean)->postJson(route('teacher-loads.store'), $this->payload($faculty, $course))->assertCreated()->json('id');
        $load = TeacherLoad::findOrFail($id);
        $this->get(route('teacher-loads.index', ['view' => 'drafts']))->assertOk()->assertViewHas('loads', fn ($loads) => $loads->contains('id', $id));
        $this->postJson(route('teacher-loads.finalize', $id), ['lock_version' => $load->lock_version])->assertOk();
        $this->get(route('teacher-loads.index', ['view' => 'drafts']))->assertOk()->assertViewHas('loads', fn ($loads) => $loads->isEmpty());
        $this->post(route('teacher-loads.change', $id), ['action' => 'revise', 'reason' => 'Update schedule details', 'lock_version' => $load->fresh()->lock_version])->assertRedirect();
        $this->get(route('teacher-loads.index', ['view' => 'drafts']))->assertOk()->assertViewHas('loads', fn ($loads) => $loads->contains('id', $id));
        $this->actingAs($faculty)->get(route('teacher-loads.index', ['view' => 'drafts']))->assertForbidden();
        $coordinator = User::where('username', 'coordinator')->firstOrFail();
        $coordinator->employee->update(['program' => 'BLIS']); $coordinator->employee->syncExtraPrograms([]);
        $this->actingAs($coordinator)->get(route('teacher-loads.index', ['view' => 'drafts']))->assertOk()->assertViewHas('loads', fn ($loads) => $loads->isEmpty());
        $coordinator->employee->syncExtraPrograms(['BSIT']);
        $this->get(route('teacher-loads.index', ['view' => 'drafts']))->assertOk()->assertViewHas('loads', fn ($loads) => $loads->contains('id', $id));
    }

    public function test_new_load_rejects_removed_or_other_program_sections(): void
    {
        [$dean, $faculty, $course] = $this->setupFaculty();
        \App\Models\CourseSection::where('name', 'BSIT 1 A')->update(['is_active' => false]);
        \App\Models\CourseSection::create(['program' => 'BLIS', 'name' => 'BLIS 1 A', 'year_level' => 1, 'is_active' => true, 'lock_version' => 1]);
        $choices = $this->actingAs($dean)->getJson(route('teacher-loads.options', ['employee_id' => $faculty->employee->employee_id, 'semester' => SchoolTerm::FIRST]))->assertOk()->json('sections');
        $this->assertNotContains('BSIT 1 A', array_column($choices, 'name'));
        $this->assertNotContains('BLIS 1 A', array_column($choices, 'name'));
        foreach (['BSIT 1 A', 'BLIS 1 A'] as $section) {
            $data = $this->payload($faculty, $course);
            $data['items'][0]['section'] = $section;
            $this->postJson(route('teacher-loads.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('items.0.section');
        }
    }

    public function test_picker_includes_program_coordinators_with_scope_rules(): void
    {
        $dean = User::where('username', 'dean')->firstOrFail();
        $coordinator = User::where('username', 'coordinator')->firstOrFail();
        $this->assertSame('Program Coordinator', $coordinator->role->role_name);

        $this->actingAs($dean)->get(route('teacher-loads.index'))
            ->assertOk()
            ->assertSee('Faculty / Program Coordinator')
            ->assertSee('Choose faculty or program coordinator')
            ->assertSee($coordinator->employee->full_name)
            ->assertSee('No courses assigned yet', false);

        $this->actingAs($coordinator)->get(route('teacher-loads.index'))
            ->assertOk()
            ->assertSee($coordinator->employee->full_name);

        $otherProgram = $coordinator->employee->program === 'BSIT' ? 'BLIS' : 'BSIT';
        $foreign = \App\Models\Employee::query()
            ->where('program', $otherProgram)
            ->whereHas('user', fn ($q) => $q->where('status', 'Active')->whereHas('role', fn ($r) => $r->whereIn('role_name', ['Faculty Employee', 'Program Coordinator'])))
            ->where('employee_id', '!=', $coordinator->employee->employee_id)
            ->first();

        if ($foreign) {
            $this->actingAs($coordinator)
                ->getJson(route('teacher-loads.options', [
                    'employee_id' => $foreign->employee_id,
                    'semester' => SchoolTerm::FIRST,
                ]))
                ->assertNotFound();
        }

        $this->actingAs($coordinator)
            ->getJson(route('teacher-loads.options', [
                'employee_id' => $coordinator->employee->employee_id,
                'semester' => SchoolTerm::FIRST,
            ]))
            ->assertOk()
            ->assertJsonPath('role_label', 'Program Coordinator')
            ->assertJsonPath('has_assigned_courses', false);
    }

    public function test_faculty_creation_saves_full_time_or_shared_classification(): void
    {
        $dean = User::where('username', 'dean')->firstOrFail();
        $page = $this->actingAs($dean)->get(route('dean.employees', ['tab' => 'createFaculty']))
            ->assertOk()->assertSee('Full-Time Faculty')->assertSee('Shared Faculty');

        $this->post(route('dean.store-faculty'), [
            '_form' => 'faculty', 'full_name' => 'Shared Faculty Test', 'program' => 'BSIT',
            'faculty_type' => 'shared', 'username' => 'shared-faculty-test', 'password' => 'Test-password-2026',
            'course_ids' => [Course::active()->where('program', 'BSIT')->firstOrFail()->id],
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
        $pdf = $this->get(route('teacher-loads.pdf', $load))->assertOk()->assertHeader('content-type', 'application/pdf');
        $disposition = (string) $pdf->headers->get('content-disposition');
        $this->assertStringContainsString(str_replace(' ', '_', $faculty->employee->full_name), $disposition);
        $this->assertStringContainsString('Teachers_Load', $disposition);
    }

    public function test_finalize_returns_the_missing_schedule_field_and_preserves_draft(): void
    {
        [$dean, $faculty, $course] = $this->setupFaculty();
        $data = $this->payload($faculty, $course);
        $data['items'][0]['schedules'] = [];
        $created = $this->actingAs($dean)->postJson(route('teacher-loads.store'), $data)->assertCreated()->json();
        $load = TeacherLoad::findOrFail($created['id']);

        $this->postJson(route('teacher-loads.finalize', $load), ['lock_version' => $load->lock_version])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.schedules')
            ->assertJsonPath('errors', ['items.0.schedules' => ['Add at least one schedule before finalizing.']]);
        $this->assertSame('draft', $load->fresh()->status);
        $this->assertCount(2, $load->fresh()->items);
    }

    public function test_finalize_rejects_empty_load_with_actionable_validation(): void
    {
        [$dean, $faculty, $course] = $this->setupFaculty();
        $data = $this->payload($faculty, $course);
        $data['items'] = [];
        $created = $this->actingAs($dean)->postJson(route('teacher-loads.store'), $data)->assertCreated()->json();
        $load = TeacherLoad::findOrFail($created['id']);

        $this->postJson(route('teacher-loads.finalize', $load), ['lock_version' => $load->lock_version])
            ->assertUnprocessable()->assertJsonValidationErrors('items')
            ->assertJsonPath('errors.items.0', 'Add at least one subject or additional duty before finalizing.');
        $this->assertSame('draft', $load->fresh()->status);
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

    public function test_managers_can_open_current_assignments_and_existing_subject_editor_within_scope(): void
    {
        [$dean, $faculty, $course] = $this->setupFaculty();
        $course->update(['semester' => SchoolTerm::current()]);
        $this->actingAs($dean)->get(route('teacher-loads.index', ['employee_id' => $faculty->employee->employee_id]))
            ->assertOk()->assertSee($course->title)->assertSee('Manage subjects')
            ->assertSee(route('dean.edit-employee', $faculty->employee->employee_id), false);
        $coordinator = User::where('username', 'coordinator')->firstOrFail();
        $coordinator->employee->update(['program' => $faculty->employee->program]);
        $this->actingAs($coordinator)->get(route('teacher-loads.index', ['employee_id' => $faculty->employee->employee_id]))
            ->assertOk()->assertSee($course->title)->assertSee(route('coordinator.edit-faculty', $faculty->employee->employee_id), false);

        $coordinator->employee->update(['program' => $faculty->employee->program === 'BLIS' ? 'BSCpE' : 'BLIS']);
        $coordinator->employee->syncExtraPrograms([]);
        $this->get(route('teacher-loads.index', ['employee_id' => $faculty->employee->employee_id]))->assertNotFound();
    }

    public function test_revision_keeps_published_schedule_until_finalized_and_retains_history(): void
    {
        [$dean, $faculty, $course] = $this->setupFaculty();
        $payload = $this->payload($faculty, $course);
        $id = $this->actingAs($dean)->postJson(route('teacher-loads.store'), $payload)->assertCreated()->json('id');
        $load = TeacherLoad::findOrFail($id);
        $this->postJson(route('teacher-loads.finalize', $id), ['lock_version' => $load->lock_version])->assertOk();
        $load->refresh();
        $this->post(route('teacher-loads.change', $id), ['action' => 'revise', 'lock_version' => $load->lock_version, 'reason' => 'Room changed for next week'])->assertRedirect();
        $load->refresh();
        $this->post(route('teacher-loads.change', $id), ['action' => 'revise', 'lock_version' => $load->lock_version, 'reason' => 'Duplicate revision attempt'])->assertStatus(409);
        $payload['lock_version'] = $load->lock_version;
        $payload['items'][0]['schedules'][0]['room'] = 'New Room 204';
        $this->patchJson(route('teacher-loads.update', $id), $payload)->assertOk()->assertJsonPath('editing_revision', true);
        $load->refresh();
        $this->assertSame('LR 103', $load->items()->first()->schedules[0]['room']);
        $this->actingAs($faculty)->getJson(route('teacher-loads.show', $id))->assertOk()
            ->assertDontSee('New Room 204')->assertJsonMissingPath('revision_draft');
        $this->actingAs($dean)->postJson(route('teacher-loads.finalize', $id), ['lock_version' => $load->lock_version])->assertOk();
        $load->refresh();
        $this->assertSame(2, $load->revision_number);
        $this->assertSame('New Room 204', $load->items()->first()->schedules[0]['room']);
        $revision = $load->revisions()->firstOrFail();
        $this->assertSame('LR 103', $revision->snapshot['items'][0]['schedules'][0]['room']);
        $this->actingAs($faculty)->get(route('teacher-loads.history', $id))->assertOk()->assertSee('Room changed for next week');
        $this->get(route('teacher-loads.revision-pdf', [$id, $revision->id]))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_void_discard_and_delete_actions_are_versioned_and_restricted(): void
    {
        [$dean, $faculty, $course] = $this->setupFaculty();
        $id = $this->actingAs($dean)->postJson(route('teacher-loads.store'), $this->payload($faculty, $course))->assertCreated()->json('id');
        $load = TeacherLoad::findOrFail($id);
        $data = ['action' => 'delete-draft', 'lock_version' => $load->lock_version, 'reason' => 'Created for the wrong employee'];
        $this->actingAs($faculty)->postJson(route('teacher-loads.change', $id), $data)->assertForbidden();
        $this->actingAs($dean)->post(route('teacher-loads.change', $id), $data)->assertRedirect();
        $this->assertDatabaseMissing('teacher_loads', ['id' => $id]);
        $id = $this->postJson(route('teacher-loads.store'), $this->payload($faculty, $course))->assertCreated()->json('id');
        $load = TeacherLoad::findOrFail($id);
        $this->postJson(route('teacher-loads.finalize', $id), ['lock_version' => $load->lock_version])->assertOk();
        $load->refresh();
        $data['lock_version'] = $load->lock_version;
        $this->postJson(route('teacher-loads.change', $id), $data)->assertStatus(409);
        $data['action'] = 'revise';
        $this->post(route('teacher-loads.change', $id), $data)->assertRedirect();
        $this->postJson(route('teacher-loads.change', $id), $data)->assertStatus(409);
        $load->refresh();
        $this->post(route('teacher-loads.change', $id), array_replace($data, ['action' => 'discard', 'lock_version' => $load->lock_version]))->assertRedirect();
        $load->refresh();
        $this->assertNull($load->revision_draft);
        $this->post(route('teacher-loads.change', $id), array_replace($data, ['action' => 'void', 'lock_version' => $load->lock_version]))->assertRedirect();
        $this->assertSame('void', $load->fresh()->status);
        $this->actingAs($faculty)->get(route('teacher-loads.history', $id))->assertOk()->assertSee('Voided:');
        $this->actingAs($dean);
        $load->refresh();
        $this->post(route('teacher-loads.change', $id), array_replace($data, ['action' => 'revise', 'lock_version' => $load->lock_version]))->assertRedirect();
        $load->refresh();
        $this->postJson(route('teacher-loads.finalize', $id), ['lock_version' => $load->lock_version])->assertOk();
        $this->assertSame('finalized', $load->fresh()->status);
        $this->assertSame(2, $load->fresh()->revision_number);
        $this->assertCount(1, $load->revisions);
    }

    public function test_revision_actions_honor_extra_programs_and_archive_restrictions(): void
    {
        [$dean, $faculty, $course] = $this->setupFaculty();
        $id = $this->actingAs($dean)->postJson(route('teacher-loads.store'), $this->payload($faculty, $course))->assertCreated()->json('id');
        $load = TeacherLoad::findOrFail($id);
        $this->postJson(route('teacher-loads.finalize', $id), ['lock_version' => $load->lock_version])->assertOk();
        $coordinator = User::where('username', 'coordinator')->firstOrFail();
        $coordinator->employee->update(['program' => $faculty->employee->program === 'BLIS' ? 'BSIT' : 'BLIS']);
        $coordinator->employee->syncExtraPrograms([]);
        $data = ['action' => 'revise', 'reason' => 'Update the assigned room', 'lock_version' => $load->fresh()->lock_version];
        $this->actingAs($coordinator)->postJson(route('teacher-loads.change', $id), $data)->assertNotFound();
        $coordinator->employee->syncExtraPrograms([$faculty->employee->program]);
        $this->post(route('teacher-loads.change', $id), $data)->assertRedirect();
        $load->schoolYear->update(['archived_at' => now(), 'is_active' => false]);
        $data['lock_version'] = $load->fresh()->lock_version;
        $data['action'] = 'discard';
        $this->postJson(route('teacher-loads.change', $id), $data)->assertUnprocessable();
    }
}
