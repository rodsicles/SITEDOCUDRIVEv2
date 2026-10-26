<?php

namespace Tests\Feature;

use App\Models\{CourseSection, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_sections_and_catalog_tabs(): void
    {
        $this->assertSame(8, CourseSection::count());
        $this->assertDatabaseHas('course_sections', ['name' => 'BSIT 1 A', 'year_level' => 1]);
        $this->assertDatabaseHas('course_sections', ['name' => 'BSIT 4 B', 'year_level' => 4]);
        $this->actingAs(User::where('username', 'dean')->firstOrFail());
        $this->get(route('dean.courses'))->assertOk()->assertSee('Catalog management')->assertSee('Sections');
        $this->get(route('dean.courses', ['view' => 'sections']))->assertOk()->assertSee('BSIT 4 B')->assertSee('Add section');
    }

    public function test_dean_can_add_rename_remove_restore_with_concurrency_and_duplicates(): void
    {
        $this->actingAs(User::where('username', 'dean')->firstOrFail());
        $data = ['program' => 'BSIT', 'name' => ' bsit 2 c ', 'year_level' => 2];
        $this->post(route('course-sections.store'), $data)->assertRedirect();
        $section = CourseSection::where('name', 'BSIT 2 C')->firstOrFail();
        $this->postJson(route('course-sections.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->patch(route('course-sections.update', $section), ['name' => 'BSIT 2 D', 'year_level' => 2, 'lock_version' => 1])->assertRedirect();
        $this->assertSame('BSIT 2 D', $section->fresh()->name);
        $this->patchJson(route('course-sections.update', $section), ['name' => 'Stale', 'year_level' => 2, 'lock_version' => 1])->assertUnprocessable();
        $this->patch(route('course-sections.status', $section), ['is_active' => 0, 'lock_version' => 2])->assertRedirect();
        $this->assertFalse($section->fresh()->is_active);
        $this->patch(route('course-sections.status', $section), ['is_active' => 1, 'lock_version' => 3])->assertRedirect();
        $this->assertTrue($section->fresh()->is_active);
        $this->postJson(route('course-sections.store'), array_replace($data, ['year_level' => 5]))->assertUnprocessable()->assertJsonValidationErrors('year_level');
    }

    public function test_coordinator_only_manages_home_and_extra_programs_and_faculty_is_denied(): void
    {
        $coordinator = User::where('username', 'coordinator')->firstOrFail();
        $coordinator->employee->update(['program' => 'BLIS']);
        $coordinator->employee->syncExtraPrograms([]);
        $section = CourseSection::firstOrFail();
        $this->actingAs($coordinator);
        $this->get(route('coordinator.courses', ['view' => 'sections']))->assertOk()->assertDontSee('BSIT 4 B');
        $this->patchJson(route('course-sections.update', $section), ['name' => 'Wrong', 'year_level' => 1, 'lock_version' => 1])->assertNotFound();
        $this->patchJson(route('course-sections.status', $section), ['is_active' => 0, 'lock_version' => 1])->assertNotFound();
        $this->postJson(route('course-sections.store'), ['program' => 'BSIT', 'name' => 'BSIT 1 C', 'year_level' => 1])->assertUnprocessable();
        $coordinator->employee->syncExtraPrograms(['BSIT']);
        $this->get(route('coordinator.courses', ['view' => 'sections']))->assertOk()->assertSee('BSIT 4 B');
        $this->patch(route('course-sections.update', $section), ['name' => 'BSIT 1 C', 'year_level' => 1, 'lock_version' => 1])->assertRedirect();
        $this->actingAs(User::where('username', 'faculty')->firstOrFail());
        $this->postJson(route('course-sections.store'), ['program' => 'BSIT', 'name' => 'No access', 'year_level' => 1])->assertForbidden();
    }
}
