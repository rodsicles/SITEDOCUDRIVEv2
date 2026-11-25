<?php

namespace App\Http\Controllers;

use App\Models\SubmissionComment;
use App\Services\SubmissionReviewService;
use Illuminate\Http\Request;

/** Revision notes, "addressed" marks, and revised uploads for Teaching Guides / Exam Questionnaires. */
class SubmissionReviewController extends Controller
{
    public function __construct(private readonly SubmissionReviewService $reviews) {}

    public function storeComment(Request $request, string $type, int $id)
    {
        $submission = $this->reviews->find($type, $id, $request->user());
        $data = $request->validate([
            'location' => 'nullable|string|max:150',
            'body' => 'required|string|min:3|max:2000',
        ], [], ['location' => 'part / page', 'body' => 'revision note']);

        $this->reviews->addComment($submission, $request->user(), $data['body'], $data['location'] ?? null);

        return back()->with('success', 'Revision note sent. The submitter has been notified.');
    }

    public function addressed(Request $request, SubmissionComment $comment)
    {
        $this->reviews->find($comment->submission_type, (int) $comment->submission_id, $request->user());
        $this->reviews->markAddressed($comment, $request->user());

        return back()->with('success', 'Note marked as addressed.');
    }

    public function revise(Request $request, string $type, int $id)
    {
        $submission = $this->reviews->find($type, $id, $request->user());
        $mimes = $submission instanceof \App\Models\TeachingGuide || $submission->file_type === 'word' ? 'pdf,doc,docx' : 'pdf';
        $data = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:'.$mimes],
            'note' => 'nullable|string|max:255',
        ], [], ['file' => 'revised file']);

        $this->reviews->revise($submission, $request->user(), $data['file'], $data['note'] ?? null);

        return back()->with('success', 'Revised version uploaded. Reviewers have been notified.');
    }
}
