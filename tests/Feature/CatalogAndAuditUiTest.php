<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogAndAuditUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_course_is_a_dialog_that_reopens_only_after_its_own_failed_submit(): void
    {
        $dean = User::where('username', 'dean')->firstOrFail();
        $this->actingAs($dean)->get(route('dean.courses'))->assertOk()
            ->assertSee('id="addCourseModal"', false)->assertSee('id="openAddCourse"', false)
            ->assertDontSee('Course not added');

        $this->from(route('dean.courses'))->post(route('dean.courses.store'), ['_add_course' => 1, 'code' => '', 'title' => 'Draft title'])
            ->assertRedirect(route('dean.courses'))->assertSessionHasErrors('code');
        $this->get(route('dean.courses'))->assertOk()->assertSee('Course not added')->assertSee('value="Draft title"', false);
    }

    public function test_activity_log_filters_use_compact_grouped_layout(): void
    {
        $this->actingAs(User::where('username', 'dean')->firstOrFail())->get(route('dean.audit-trail'))->assertOk()
            ->assertSee('Search activity')->assertSee('Date Range')->assertSee('All Roles')->assertSee('Apply Filters');
    }

    public function test_only_search_shows_until_advanced_filters_are_opened_or_active(): void
    {
        $this->actingAs(User::where('username', 'dean')->firstOrFail());

        $this->get(route('dean.audit-trail'))->assertOk()
            ->assertSee('Advanced filters')
            ->assertSee('id="auditAdvancedFilters" class="audit-filters__advanced"  hidden', false);
        $this->get(route('dean.audit-trail', ['actor_role' => 'Dean']))->assertOk()
            ->assertDontSee('id="auditAdvancedFilters" class="audit-filters__advanced"  hidden', false)
            ->assertSee('aria-expanded="true" aria-controls="auditAdvancedFilters"', false);

        $this->get(route('dean.documents'))->assertOk()
            ->assertSee('Advanced filters')
            ->assertSee('class="doc-advanced-filters is-collapsed"', false);
    }
}
