<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\SchoolYear;
use App\Models\SubmissionComment;
use App\Models\SubmissionView;
use App\Models\TeachingGuide;
use App\Models\User;
use App\Services\TeachingGuideSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmissionReviewTest extends TestCase
{
    use RefreshDatabase;

    private function liveGuide(User $faculty): TeachingGuide
    {
        Storage::fake('local'); Queue::fake(); config(['filesystems.upload_disk' => 'local']);
        Storage::disk('local')->put('teaching-guides/original.pdf', '%PDF-1.4 original');
        $guide = TeachingGuide::create([
            'user_id' => $faculty->id, 'title' => 'IT101 Syllabus', 'file_path' => 'teaching-guides/original.pdf',
            'original_filename' => 'original.pdf', 'file_type' => 'pdf', 'subject' => 'IT101',
            'school_year_id' => SchoolYear::activeId(), 'semester' => '1st', 'academic_year' => '2026-2027', 'status' => 'approved',
        ]);
        app(TeachingGuideSyncService::class)->syncDocumentFromGuide($guide, $faculty, []);

        return $guide->fresh('document');
    }

    public function test_full_review_cycle_view_note_revise_and_address(): void
    {
        $dean = User::where('username', 'dean')->firstOrFail();
        $faculty = User::where('username', 'faculty')->firstOrFail();
        $guide = $this->liveGuide($faculty);

        // New submission is live and appears in the Dean's "New for you" list.
        $this->actingAs($dean)->get(route('dean.teaching-guides.index', ['review' => 'new']))->assertOk()->assertSee('IT101 Syllabus');
        $this->assertSame(1, \App\Support\SubmissionPendingCounts::teachingGuidesFor($dean));

        // Opening it records "Viewed by Dean" for v1.
        $this->get(route('dean.teaching-guides.view', $guide->id))->assertOk()->assertSee('Leave a revision note');
        $this->assertTrue(SubmissionView::where('submission_id', $guide->id)->where('user_id', $dean->id)->where('version', 1)->exists());
        $this->assertSame(0, \App\Support\SubmissionPendingCounts::teachingGuidesFor($dean));

        // Dean leaves a note pointing at a part; faculty is notified; status = Needs revision.
        $this->post(route('submission-reviews.comments.store', ['teaching-guide', $guide->id]), [
            'location' => 'Part II, item 5', 'body' => 'Wrong answer key.',
        ])->assertRedirect()->assertSessionHas('success');
        $comment = SubmissionComment::firstOrFail();
        $this->assertSame('Part II, item 5', $comment->location);
        $this->assertTrue(Notification::where('user_id', $faculty->id)->where('message', 'like', '%revision note%')->exists());
        $this->actingAs($faculty)->get(route('faculty.teaching-guides.view', $guide->id))->assertOk()
            ->assertSee('Needs revision')->assertSee('Wrong answer key.')->assertSee('Upload revised version');

        // Faculty cannot comment on their own file; Dean cannot mark addressed.
        $this->post(route('submission-reviews.comments.store', ['teaching-guide', $guide->id]), ['body' => 'Self note'])->assertForbidden();
        $this->actingAs($dean)->post(route('submission-reviews.comments.addressed', $comment))->assertForbidden();

        // Faculty uploads v2 (old file kept in version history) and marks the note addressed.
        $this->actingAs($faculty)->post(route('submission-reviews.revise', ['teaching-guide', $guide->id]), [
            'file' => UploadedFile::fake()->create('fixed.pdf', 10, 'application/pdf'), 'note' => 'Fixed item 5',
        ])->assertRedirect()->assertSessionHas('success');
        $guide->refresh()->load('document');
        $this->assertSame(2, (int) $guide->document->version);
        $this->assertSame($guide->document->file_path, $guide->file_path);
        $this->assertSame(1, $guide->document->versions()->count());
        $this->assertTrue(Notification::where('user_id', $dean->id)->where('message', 'like', '%revised (v2)%')->exists());

        $this->post(route('submission-reviews.comments.addressed', $comment))->assertRedirect();
        $this->assertNotNull($comment->fresh()->addressed_at);
        $status = app(\App\Services\SubmissionReviewService::class)->status($guide->fresh('document'));
        $this->assertSame('revised', $status['key']);
        $this->assertSame('Revised (v2)', $status['label']);
    }

    public function test_approve_and_reject_routes_are_gone_and_coordinator_scope_applies(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('dean.teaching-guides.approve'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('coordinator.exam-questionnaires.reject'));

        $faculty = User::where('username', 'faculty')->firstOrFail();
        $guide = $this->liveGuide($faculty);
        $coordinator = User::where('username', 'coordinator')->firstOrFail();
        $secretary = User::where('username', 'secretary')->firstOrFail();

        $this->actingAs($coordinator)->get(route('coordinator.teaching-guides.view', $guide->id))->assertOk();
        $this->assertSame('Coordinator', SubmissionView::where('user_id', $coordinator->id)->value('role_name'));

        $this->actingAs($secretary)->post(route('submission-reviews.comments.store', ['teaching-guide', $guide->id]), ['body' => ''])
            ->assertSessionHasErrors('body');
        $this->assertSame(0, SubmissionComment::count());
    }
}
