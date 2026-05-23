@php
    /** @var \App\Models\TeachingGuide|\App\Models\ExamQuestionnaire $submission */
@endphp
<div class="submission-status-cell">
    @if($submission->isPending())
        <span class="submission-status submission-status--pending" title="Awaiting Dean approval">
            <i class="fas fa-clock" aria-hidden="true"></i>
            Pending
        </span>
    @elseif($submission->isApproved())
        <span class="submission-status submission-status--approved">
            <i class="fas fa-check" aria-hidden="true"></i>
            Approved
        </span>
        @if($submission->reviewer)
            <span class="submission-status-meta">by {{ $submission->reviewer->employee->full_name ?? $submission->reviewer->username }}</span>
        @endif
    @else
        <span class="submission-status submission-status--rejected">
            <i class="fas fa-times" aria-hidden="true"></i>
            Rejected
        </span>
        @if($submission->remarks)
            <span class="submission-status-meta" title="{{ $submission->remarks }}">{{ \Illuminate\Support\Str::limit($submission->remarks, 40) }}</span>
        @endif
    @endif
</div>
