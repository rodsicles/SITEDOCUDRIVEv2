<?php

namespace Tests\Feature;

use App\Models\{Course, CourseChange, User};
use App\Support\CourseUnits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_edit_accepts_existing_code_formats_and_records_all_metadata(): void
    {
        $dean = User::where('username', 'dean')->firstOrFail();
        $course = Course::where('program', 'BLIS')->where('code', 'ICT101Bis')->firstOrFail();
        $this->actingAs($dean)->get(route('dean.courses'))->assertOk()->assertSee('edit-course-lecture_units', false);
        $this->patch(route('dean.courses.update', $course), ['code' => $course->code, 'title' => 'Updated curriculum title', 'year_level' => 3, 'semester' => 'Summer', 'lecture_units' => 1.5, 'lab_units' => 2, 'updated_at' => $course->updated_at->toISOString()])->assertSessionHasNoErrors()->assertRedirect();
        $course->refresh();
        $this->assertSame('ICT101Bis', $course->code);
        $this->assertSame('Summer', $course->semester);
        $this->assertSame(1.5, $course->lecture_units);
        $this->assertSame(3, $course->year_level);
        $this->assertSame(1, CourseChange::where('course_id', $course->id)->count());
        $this->get(route('courses.history', $course))->assertOk()->assertSee('Updated curriculum title');
        CourseUnits::apply();
        $this->assertSame(1.5, $course->fresh()->lecture_units);
        $this->post(route('dean.courses.store'), ['code' => 'ENGGMAT99', 'title' => 'New engineering elective', 'program' => 'BSCpE', 'year_level' => 4, 'semester' => '2nd', 'lecture_units' => 3, 'lab_units' => 0])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('courses', ['code' => 'ENGGMAT99', 'program' => 'BSCpE', 'semester' => '2nd']);
    }

    public function test_coordinator_metadata_edit_is_scoped_and_invalid_values_are_rejected(): void
    {
        $actor = User::where('username', 'coordinator')->firstOrFail();
        $course = Course::active()->where('program', $actor->employee->program)->firstOrFail();
        $data = ['code' => $course->code, 'title' => $course->title, 'year_level' => 2, 'semester' => '1st', 'lecture_units' => 3, 'lab_units' => 1];
        $this->actingAs($actor)->patch(route('coordinator.courses.update', $course), $data)->assertSessionHasNoErrors();
        $this->assertSame(3.0, $course->fresh()->lecture_units);
        $this->patchJson(route('coordinator.courses.update', $course), array_replace($data, ['lecture_units' => -1]))->assertUnprocessable();
        $this->patchJson(route('coordinator.courses.update', $course), array_replace($data, ['updated_at' => now()->subYear()->toISOString()]))->assertStatus(409);
        $foreign = Course::active()->where('program', '!=', $actor->employee->program)->firstOrFail();
        $this->patchJson(route('coordinator.courses.update', $foreign), $data)->assertForbidden();
        $this->get(route('courses.history', $foreign))->assertForbidden();
        $faculty = User::where('username', 'faculty')->firstOrFail();
        $this->actingAs($faculty)->patchJson(route('dean.courses.update', $course), $data)->assertForbidden();
        $this->get(route('courses.history', $course))->assertForbidden();
    }
}
