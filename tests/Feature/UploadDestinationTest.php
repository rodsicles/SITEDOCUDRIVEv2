<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Folder;
use App\Models\User;
use App\Services\AcademicHierarchyService;
use App\Support\IteSubjects;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UploadDestinationTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_three_roles_can_browse_and_have_one_upload_entry(): void
    {
        foreach (['faculty', 'coordinator', 'dean'] as $role) {
            $this->actingAs(User::where('username', $role)->firstOrFail());
            $this->getJson(route('upload-destinations.index'))->assertOk()->assertJsonPath('uploadable', false);
            $page = $this->get(route($role.'.documents'))->assertOk();
            $page->assertSee('Upload Document')->assertDontSee('Upload to this Folder');
        }
    }

    public function test_assigned_course_can_be_opened_and_only_its_final_folder_selected(): void
    {
        $faculty = User::where('username', 'faculty')->firstOrFail();
        $course = Course::active()->where('department', Course::DEPT_IT)->firstOrFail();
        $faculty->assignedCourses()->sync([$course->id]);
        $hierarchy = app(AcademicHierarchyService::class);
        $hierarchy->ensureActiveSchoolYearStructures();
        $semester = Folder::where('school_year_id', \App\Models\SchoolYear::activeId())->get()->first(fn ($folder) => $hierarchy->isTgSemesterFolder($folder));
        $this->assertNotNull($semester);
        $this->actingAs($faculty)->getJson(route('upload-destinations.index', ['folder' => $semester->folder_id]))
            ->assertOk()->assertJsonPath('uploadable', false);
        $response = $this->postJson(route('upload-destinations.subject'), [
            'folder' => $semester->folder_id, 'subject' => IteSubjects::labelsForUser($faculty)[0],
        ])->assertOk();
        $subject = Folder::findOrFail($response->json('id'));
        $leaf = $subject->children()->where('folder_name', 'TG')->firstOrFail();
        $this->getJson(route('upload-destinations.index', ['folder' => $leaf->folder_id]))
            ->assertOk()->assertJsonPath('uploadable', true)->assertJsonPath('academic', true);
        $faculty->assignedCourses()->sync([Course::active()->where('department', Course::DEPT_IT)->where('id', '!=', $course->id)->firstOrFail()->id]);
        $this->getJson(route('upload-destinations.index', ['folder' => $leaf->folder_id]))->assertNotFound();
        $this->postJson(route('faculty.upload-document'), ['guided_upload' => 1, 'folder_id' => $leaf->folder_id])->assertNotFound();
    }

    public function test_category_cannot_be_used_as_upload_destination(): void
    {
        $dean = User::where('username', 'dean')->firstOrFail();
        $category = Folder::system()->topLevel()->firstOrFail();
        $this->actingAs($dean)->postJson(route('dean.upload-document'), [
            'guided_upload' => 1, 'folder_id' => $category->folder_id,
        ])->assertUnprocessable()->assertJsonValidationErrors('folder_id');
    }

    public function test_guests_cannot_browse_destinations(): void
    {
        $this->getJson(route('upload-destinations.index'))->assertUnauthorized();
    }
}
