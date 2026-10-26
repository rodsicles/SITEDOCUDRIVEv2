<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Course;
use App\Models\ExamQuestionnaire;
use App\Models\Folder;
use App\Models\SchoolYear;
use App\Models\TeachingGuide;
use App\Models\User;
use App\Services\AcademicHierarchyService;
use App\Support\IteSubjects;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArchiveContentTest extends TestCase
{
    use RefreshDatabase;

    private function years(): array
    {
        $archived = SchoolYear::active();
        $archived->update(['is_active' => false, 'archived_at' => now()->subDay()]);
        $active = SchoolYear::create([
            'name' => 'Next year',
            'start_year' => $archived->start_year + 1,
            'end_year' => $archived->end_year + 1,
            'is_active' => true,
        ]);
        return [$archived, $active];
    }

    public function test_archive_folder_upload_and_rename_keep_the_archived_year(): void
    {
        [$archived, $active] = $this->years();
        $faculty = User::where('username', 'faculty')->firstOrFail();
        $this->actingAs($faculty);
        Storage::fake('local');
        config(['filesystems.upload_disk' => 'local']);
        Queue::fake();

        $this->post(route('archive-contents.folders.store', $archived), ['folder_name' => 'Late Work'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $folder = Folder::where('school_year_id', $archived->id)->where('folder_name', 'Late Work')->firstOrFail();
        $this->getJson(route('archive-contents.destinations', [$archived, 'folder' => $folder->folder_id]))
            ->assertOk()->assertJsonPath('uploadable', true);
        $this->postJson(route('archive-contents.upload', $archived), [
            'guided_upload' => 1,
            'folder_id' => $folder->folder_id,
            'document_type' => 'pdf',
            'documents' => [UploadedFile::fake()->create('late.pdf', 10, 'application/pdf')],
        ])->assertOk()->assertJsonPath('success', true);

        $document = Document::where('folder_id', $folder->folder_id)->firstOrFail();
        $this->assertSame($archived->id, $document->school_year_id);
        $this->assertNotSame($active->id, $document->school_year_id);
        Storage::disk('local')->assertExists($document->file_path);
        $this->get(route('faculty.archives.show', $archived))->assertOk()->assertSee('late');
        $this->get(route('faculty.download-document', $document->document_id))->assertOk()->assertDownload('late.pdf');

        $this->patch(route('archive-contents.documents.rename', [$archived, $document]), ['document_title' => 'Late revised'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->patch(route('archive-contents.folders.rename', [$archived, $folder]), ['folder_name' => 'Late Files'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('documents', ['document_id' => $document->document_id, 'document_title' => 'Late revised', 'school_year_id' => $archived->id]);
        $this->assertDatabaseHas('folders', ['folder_id' => $folder->folder_id, 'folder_name' => 'Late Files', 'school_year_id' => $archived->id]);
    }

    public function test_secretary_can_open_the_archive_with_archive_actions(): void
    {
        [$archived] = $this->years();
        $this->actingAs(User::where('username', 'secretary')->firstOrFail());
        $this->get(route('dean.archives.list'))->assertOk()->assertSee('Archives');
        $this->get(route('dean.archives.show', $archived))->assertOk()->assertSee('Add files');
    }

    public function test_archive_actions_reject_another_year_and_another_users_folder(): void
    {
        [$archived, $active] = $this->years();
        $faculty = User::where('username', 'faculty')->firstOrFail();
        $other = User::where('username', 'dean')->firstOrFail();
        $folder = Folder::create(['folder_name' => 'Current', 'user_id' => $faculty->id, 'school_year_id' => $active->id, 'is_system' => false]);
        $private = Folder::create(['folder_name' => 'Private', 'user_id' => $faculty->id, 'school_year_id' => $archived->id, 'is_system' => false, 'is_private' => true, 'privacy_owner_id' => $faculty->id]);
        $document = Document::create(['uploaded_by' => $faculty->id, 'folder_id' => $private->folder_id, 'school_year_id' => $archived->id, 'document_title' => 'Older file', 'category' => 'Other', 'document_type' => 'pdf', 'file_path' => 'older.pdf', 'file_size' => 1]);
        $this->actingAs($faculty)->getJson(route('archive-contents.destinations', [$archived, 'folder' => $folder->folder_id]))->assertNotFound();
        $this->postJson(route('archive-contents.upload', $archived), ['folder_id' => $folder->folder_id])->assertNotFound();
        $this->post(route('archive-contents.folders.store', $archived), ['folder_name' => 'Wrong', 'parent_id' => $folder->folder_id])->assertNotFound();
        $this->postJson(route('faculty.upload-document'), ['folder_id' => $private->folder_id])->assertUnprocessable();
        $this->postJson(route('faculty.folders.store'), ['folder_name' => 'Wrong', 'parent_id' => $private->folder_id])->assertUnprocessable();
        $this->patch(route('archive-contents.documents.rename', [$active, $document]), ['document_title' => 'Wrong'])->assertNotFound();
        $this->actingAs($other)->getJson(route('archive-contents.destinations', [$archived, 'folder' => $private->folder_id]))->assertNotFound();
        $this->post(route('archive-contents.folders.store', $archived), ['folder_name' => 'Wrong', 'parent_id' => $private->folder_id])->assertNotFound();
        $this->patch(route('archive-contents.folders.rename', [$archived, $private]), ['folder_name' => 'Wrong'])->assertNotFound();
        $this->patch(route('archive-contents.documents.rename', [$archived, $document]), ['document_title' => 'Wrong'])->assertForbidden();
    }

    public function test_late_teaching_guide_stays_in_review_until_approved(): void
    {
        [$archived] = $this->years();
        $faculty = User::where('username', 'faculty')->firstOrFail();
        $hierarchy = app(AcademicHierarchyService::class);
        $hierarchy->ensureSchoolYearStructure('tg', $archived->start_year);
        $semester = Folder::where('school_year_id', $archived->id)->get()->first(fn ($folder) => $hierarchy->isTgSemesterFolder($folder));
        $this->assertNotNull($semester);
        $this->actingAs($faculty);
        $subject = IteSubjects::labelsForUser($faculty)[0];
        $subjectId = $this->postJson(route('archive-contents.subjects', $archived), ['folder' => $semester->folder_id, 'subject' => $subject])
            ->assertOk()->json('id');
        $leaf = Folder::where('parent_id', $subjectId)->where('folder_name', 'TG')->firstOrFail();
        Storage::fake('local');
        config(['filesystems.upload_disk' => 'local']);
        Queue::fake();
        $this->postJson(route('archive-contents.upload', $archived), [
            'guided_upload' => 1,
            'folder_id' => $leaf->folder_id,
            'document_type' => 'pdf',
            'documents' => [UploadedFile::fake()->create('guide.pdf', 10, 'application/pdf')],
        ])->assertOk()->assertJsonPath('success', true);
        $guide = TeachingGuide::where('folder_id', $leaf->folder_id)->firstOrFail();
        $this->assertSame($archived->id, $guide->school_year_id);
        $this->assertSame('pending', $guide->status);
        $this->get(route('faculty.archives.show', $archived))->assertOk()->assertSee('No approved teaching guides in this archive.');

        $this->actingAs(User::where('username', 'dean')->firstOrFail())
            ->post(route('dean.teaching-guides.approve', $guide->id))
            ->assertRedirect();
        $guide->refresh();
        $this->assertSame('approved', $guide->status);
        $this->assertSame($archived->id, $guide->school_year_id);
        $this->assertSame($archived->id, $guide->document?->school_year_id);
        $this->get(route('dean.archives.show', $archived))->assertOk()->assertSee('guide');
        $this->assertSame('guide.pdf', $guide->document?->original_filename);
        $this->get(route('dean.teaching-guides.download', $guide->id))->assertOk()->assertDownload('guide.pdf');

        $otherCourse = Course::active()->where('program', $faculty->employee->program)
            ->where('code', '!=', IteSubjects::codeFromLabel($subject))->firstOrFail();
        $faculty->assignedCourses()->sync([$otherCourse->id]);
        $this->actingAs($faculty)->getJson(route('archive-contents.destinations', [$archived, 'folder' => $leaf->folder_id]))->assertNotFound();
    }

    public function test_late_exam_questionnaire_uses_the_archived_year_after_approval(): void
    {
        [$archived] = $this->years();
        $faculty = User::where('username', 'faculty')->firstOrFail();
        $hierarchy = app(AcademicHierarchyService::class);
        $hierarchy->ensureSchoolYearStructure('eq', $archived->start_year);
        $semester = Folder::where('school_year_id', $archived->id)->get()->first(fn ($folder) => $hierarchy->isEqSemesterFolder($folder));
        $this->assertNotNull($semester);
        $this->actingAs($faculty);
        $subjectId = $this->postJson(route('archive-contents.subjects', $archived), [
            'folder' => $semester->folder_id,
            'subject' => IteSubjects::labelsForUser($faculty)[0],
        ])->assertOk()->json('id');
        $leaf = Folder::where('school_year_id', $archived->id)->where('folder_name', 'TOQ')
            ->whereHas('parent.parent', fn ($q) => $q->where('folder_id', $subjectId))->firstOrFail();
        Storage::fake('local');
        config(['filesystems.upload_disk' => 'local']);
        Queue::fake();
        $this->postJson(route('archive-contents.upload', $archived), [
            'guided_upload' => 1,
            'folder_id' => $leaf->folder_id,
            'document_type' => 'pdf',
            'documents' => [UploadedFile::fake()->create('questionnaire.pdf', 10, 'application/pdf')],
        ])->assertOk()->assertJsonPath('success', true);
        $questionnaire = ExamQuestionnaire::where('school_year_id', $archived->id)->latest('id')->firstOrFail();
        $this->assertSame('pending', $questionnaire->status);
        $this->actingAs(User::where('username', 'dean')->firstOrFail())
            ->post(route('dean.exam-questionnaires.approve', $questionnaire->id))
            ->assertRedirect();
        $questionnaire->refresh();
        $this->assertSame('approved', $questionnaire->status);
        $this->assertSame($archived->id, $questionnaire->document?->school_year_id);
        $this->assertSame('questionnaire.pdf', $questionnaire->document?->original_filename);
        $this->get(route('dean.exam-questionnaires.download', $questionnaire->id))->assertOk()->assertDownload('questionnaire.pdf');
    }
}
