@php
    /** @var \App\Models\TeachingGuide|\App\Models\ExamQuestionnaire $submission */
    $reviewService = app(\App\Services\SubmissionReviewService::class);
    $submissionLabel = trim((string) ($submission->title ?? '')) !== ''
        ? $submission->title
        : ($submission->subject ?? 'Untitled submission');
    $canLeaveNote = \App\Services\SubmissionReviewService::available() && $reviewService->canReview($submission, auth()->user());
@endphp
<div class="submission-review-actions archive-row-actions" role="group" aria-label="Submission actions">
    <a href="{{ $viewUrl }}"
       class="btn btn-sm btn-success border-0 archive-row-actions__btn"
       title="Open and review"
       aria-label="Open {{ $submissionLabel }}">
        <i class="fas fa-eye" aria-hidden="true"></i>
    </a>
    <a href="{{ $downloadUrl }}"
       class="btn btn-sm btn-success border-0 archive-row-actions__btn"
       title="Download"
       aria-label="Download {{ $submissionLabel }}">
        <i class="fas fa-download" aria-hidden="true"></i>
    </a>
    @if($canLeaveNote)
        <button type="button"
                class="btn btn-sm submission-note-btn"
                data-open-note-modal
                data-note-url="{{ route('submission-reviews.comments.store', [$reviewService->slugOf($submission), $submission->id]) }}"
                data-note-title="{{ $submissionLabel }}"
                data-note-version="{{ $reviewService->version($submission) }}"
                aria-label="Leave a revision note on {{ $submissionLabel }}">
            <i class="fas fa-comment-dots" aria-hidden="true"></i> Leave note
        </button>
    @endif
</div>
