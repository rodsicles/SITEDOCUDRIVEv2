@php
    /** @var \App\Models\TeachingGuide|\App\Models\ExamQuestionnaire $submission */
    $popoverKey = ($popoverPrefix ?? 'sub') . '-' . $submission->id;
    $approveFormId = 'approve-form-' . $popoverKey;
@endphp
<div class="submission-action-wrap">
    <button type="button"
            class="doc-actions-btn submission-actions-btn"
            data-popover-key="{{ $popoverKey }}"
            aria-label="Actions for {{ $submission->title }}"
            aria-expanded="false"
            aria-haspopup="true"
            aria-controls="submission-popover-{{ $popoverKey }}">
        <i class="fas fa-ellipsis-v" aria-hidden="true"></i>
    </button>
    <div id="submission-popover-{{ $popoverKey }}"
         class="doc-list-popover submission-review-popover"
         data-popover-key="{{ $popoverKey }}"
         role="menu"
         hidden>
        <a href="{{ $viewUrl }}" target="_blank" class="doc-list-popover-item" role="menuitem">
            <i class="fas fa-eye text-xs" aria-hidden="true"></i> View
        </a>
        <a href="{{ $downloadUrl }}" class="doc-list-popover-item" role="menuitem">
            <i class="fas fa-download text-xs" aria-hidden="true"></i> Download
        </a>
        @if($submission->isPending())
            <form id="{{ $approveFormId }}" action="{{ $approveUrl }}" method="POST">
                @csrf
                <button type="button"
                        class="doc-list-popover-item submission-approve-btn"
                        role="menuitem"
                        data-approve-form="{{ $approveFormId }}">
                    <i class="fas fa-check text-xs" aria-hidden="true"></i> Approve
                </button>
            </form>
            <button type="button"
                    class="doc-list-popover-item doc-list-popover-item--danger"
                    role="menuitem"
                    onclick="closeSubmissionPopovers(); {{ $rejectOnClick }}({{ $submission->id }})">
                <i class="fas fa-times text-xs" aria-hidden="true"></i> Reject
            </button>
        @endif
    </div>
</div>
