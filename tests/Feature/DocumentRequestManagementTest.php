<?php

namespace Tests\Feature;

use App\Models\{Document, DocumentRequest, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Queue, Storage};
use Tests\TestCase;

class DocumentRequestManagementTest extends TestCase
{
    use RefreshDatabase;

    private function requestRecord(): array
    {
        $dean = User::where('username', 'dean')->firstOrFail();
        $faculty = User::where('username', 'faculty')->firstOrFail();
        $this->actingAs($dean)->post(route('document-requests.store'), ['title' => 'Signed report', 'request_category' => 'general', 'document_type' => 'pdf', 'recipient_ids' => [$faculty->id]])->assertRedirect();
        return [$dean, $faculty, DocumentRequest::latest('id')->firstOrFail()];
    }

    private function change(DocumentRequest $item, string $action, array $extra = [])
    {
        return $this->patch(route('document-requests.change', $item), array_replace(['action' => $action, 'reason' => 'Updated office requirements', 'lock_version' => $item->fresh()->lock_version], $extra));
    }

    public function test_successful_upload_and_retry_return_to_list_without_duplicate(): void
    {
        [$dean, $faculty, $item] = $this->requestRecord();
        Storage::fake('local'); Queue::fake(); config(['filesystems.upload_disk' => 'local']);
        $recipient = $item->recipients()->firstOrFail();
        $this->actingAs($faculty)->post(route('document-requests.submit', $recipient), [
            'file' => UploadedFile::fake()->create('report.pdf', 10, 'application/pdf'),
        ])->assertRedirect(route('document-requests.index', ['tab' => 'received']))->assertSessionHas('success', 'Document submitted successfully.');
        $documentId = $recipient->fresh()->submitted_document_id;
        Storage::disk('local')->assertExists(Document::findOrFail($documentId)->file_path);
        $count = Document::count();
        $version = $item->fresh()->lock_version;
        $this->post(route('document-requests.submit', $recipient), [
            'file' => UploadedFile::fake()->create('duplicate.pdf', 10, 'application/pdf'),
        ])->assertRedirect(route('document-requests.index', ['tab' => 'received']))->assertSessionHas('success');
        $this->assertSame($count, Document::count());
        $this->assertSame($documentId, $recipient->fresh()->submitted_document_id);
        $this->assertSame($version, $item->fresh()->lock_version);
        $this->assertCount(1, Storage::disk('local')->allFiles('documents/requests'));
        $this->actingAs($dean)->post(route('document-requests.submit', $recipient))->assertForbidden();
    }

    public function test_expired_browser_submission_redirects_with_actionable_message(): void
    {
        [$dean, $faculty, $item] = $this->requestRecord();
        $item->update(['due_at' => now()->subDay(), 'allow_late_submission' => false]);
        $recipient = $item->recipients()->firstOrFail();
        $this->actingAs($faculty)->post(route('document-requests.submit', $recipient))
            ->assertRedirect(route('document-requests.index', ['tab' => 'received']))->assertSessionHasErrors('submission');
        $this->postJson(route('document-requests.submit', $recipient))->assertUnprocessable()->assertJsonValidationErrors('submission');
        $this->assertSame('pending', $recipient->fresh()->status);
        $this->assertNull($recipient->fresh()->submitted_document_id);
    }

    public function test_workflow_migration_can_resume_without_removing_existing_records(): void
    {
        [$dean, $faculty, $item] = $this->requestRecord();
        \Illuminate\Support\Facades\Schema::table('teacher_load_revisions', fn ($table) => $table->dropForeign(['changed_by']));
        $migration = require database_path('migrations/2026_10_21_000001_add_workflow_correction_history.php');
        $migration->up();
        $this->assertDatabaseHas('document_requests', ['id' => $item->id, 'title' => 'Signed report']);
        $this->assertSame(1, $item->recipients()->count());
        $this->assertTrue(collect(\Illuminate\Support\Facades\Schema::getForeignKeys('teacher_load_revisions'))->contains(fn ($key) => $key['columns'] === ['changed_by']));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasIndex('teacher_load_revisions', 'teacher_load_revisions_teacher_load_id_revision_number_unique'));
    }

    public function test_author_can_edit_deadline_add_recipients_cancel_and_reopen_with_history(): void
    {
        [$dean, $faculty, $item] = $this->requestRecord();
        $coordinator = User::where('username', 'coordinator')->firstOrFail();
        $this->get(route('document-requests.manage', $item))->assertOk()->assertSee('Change history');
        $this->change($item, 'update', ['title' => 'Corrected report', 'due_at' => now()->addDays(4)->toDateTimeString(), 'allow_late_submission' => false])->assertRedirect();
        $this->change($item, 'recipients', ['recipient_ids' => [$coordinator->id]])->assertRedirect();
        $this->assertSame(2, $item->recipients()->count());
        $this->change($item, 'cancel')->assertRedirect();
        $this->assertSame('cancelled', $item->fresh()->status);
        $this->actingAs($faculty)->get(route('document-requests.index'))->assertOk()->assertDontSee('class="btn btn-primary request-submit-trigger"', false);
        Storage::fake('local'); Queue::fake(); config(['filesystems.upload_disk' => 'local']);
        $recipient = $item->recipients()->where('user_id', $faculty->id)->firstOrFail();
        $this->postJson(route('document-requests.submit', $recipient), ['file' => UploadedFile::fake()->create('report.pdf', 10, 'application/pdf')])->assertUnprocessable();
        $this->actingAs($dean);
        $this->change($item, 'reopen')->assertRedirect();
        $this->assertSame('open', $item->fresh()->status);
        $this->assertSame(4, $item->changes()->count());
        $this->assertSame('Signed report', $item->changes()->where('action', 'update')->first()->before['title']);
        $this->assertDatabaseHas('notifications', ['user_id' => $faculty->id]);
    }

    public function test_exemption_and_reinstatement_preserve_the_submitted_file_and_review_state(): void
    {
        [$dean, $faculty, $item] = $this->requestRecord();
        Storage::fake('local'); Queue::fake(); config(['filesystems.upload_disk' => 'local']);
        $recipient = $item->recipients()->firstOrFail();
        $this->actingAs($faculty)->post(route('document-requests.submit', $recipient), ['file' => UploadedFile::fake()->create('report.pdf', 10, 'application/pdf')])->assertRedirect();
        $recipient->refresh();
        $document = Document::findOrFail($recipient->submitted_document_id);
        $this->actingAs($dean);
        $this->change($item, 'exempt', ['recipient_id' => $recipient->id])->assertRedirect();
        $this->assertSame('exempt', $recipient->fresh()->status);
        $this->assertSame('submitted', $recipient->fresh()->previous_status);
        Storage::disk('local')->assertExists($document->file_path);
        $this->assertSame($document->document_id, $recipient->fresh()->submitted_document_id);
        $this->change($item, 'reopen')->assertRedirect();
        $this->change($item, 'recipients', ['recipient_ids' => [$faculty->id]])->assertRedirect();
        $this->assertSame('submitted', $recipient->fresh()->status);
        $this->post(route('document-requests.review', $recipient), ['decision' => 'approved'])->assertRedirect();
        $this->change($item, 'reopen')->assertRedirect();
        $this->change($item, 'exempt', ['recipient_id' => $recipient->id])->assertStatus(422);
        $this->assertSame('approved', $recipient->fresh()->status);
    }

    public function test_management_denies_nonowners_stale_forms_and_out_of_scope_recipients(): void
    {
        [$dean, $faculty, $item] = $this->requestRecord();
        $this->actingAs($faculty)->get(route('document-requests.manage', $item))->assertForbidden();
        $this->change($item, 'cancel')->assertForbidden();
        $this->actingAs($dean);
        $this->change($item, 'update', ['title' => 'Changed'])->assertRedirect();
        $this->change($item, 'cancel', ['lock_version' => 1])->assertStatus(409);
        $coordinator = User::where('username', 'coordinator')->firstOrFail();
        $item->update(['requested_by' => $coordinator->id]);
        $coordinator->employee->update(['program' => $faculty->employee->program === 'BLIS' ? 'BSCpE' : 'BLIS']);
        $coordinator->employee->syncExtraPrograms([]);
        $this->actingAs($coordinator);
        $this->change($item, 'recipients', ['recipient_ids' => [$faculty->id]])->assertStatus(422);
    }
}
