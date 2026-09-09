@php
    /** @var \App\Models\TeachingGuide|\App\Models\ExamQuestionnaire $submission */
    $popoverKey = ($popoverPrefix ?? 'sub') . '-' . $submission->id;
    $approveFormId = 'approve-form-' . $popoverKey;
    $submissionLabel = trim((string) ($submission->title ?? '')) !== ''
        ? $submission->title
        : ($submission->subject ?? 'Untitled submission');
    $isPending = $submission->isPending();
@endphp
<div class="submission-review-actions archive-row-actions" role="group" aria-label="Submission actions">
    <a href="{{ $viewUrl }}"
       class="btn btn-sm btn-success border-0 archive-row-actions__btn"
       target="_blank"
       rel="noopener noreferrer"
       title="View"
       aria-label="View {{ $submissionLabel }}">
        <i class="fas fa-eye" aria-hidden="true"></i>
    </a>
    <a href="{{ $downloadUrl }}"
       class="btn btn-sm btn-success border-0 archive-row-actions__btn"
       title="Download"
       aria-label="Download {{ $submissionLabel }}">
        <i class="fas fa-download" aria-hidden="true"></i>
    </a>

    @if($isPending)
        <form id="{{ $approveFormId }}" action="{{ $approveUrl }}" method="POST" class="submission-review-actions__form">
            @csrf
            <button type="button"
                    class="btn btn-sm btn-success border-0 archive-row-actions__btn submission-approve-btn"
                    title="Approve"
                    aria-label="Approve {{ $submissionLabel }}"
                    data-approve-form="{{ $approveFormId }}"
                    data-submission-title="{{ e($submissionLabel) }}">
                <i class="fas fa-check" aria-hidden="true"></i>
            </button>
        </form>
        <button type="button"
                class="btn btn-sm btn-danger border-0 archive-row-actions__btn"
                title="Reject"
                aria-label="Reject {{ $submissionLabel }}"
                onclick="{{ $rejectOnClick }}({{ $submission->id }})">
            <i class="fas fa-times" aria-hidden="true"></i>
        </button>
    @endif
</div>
