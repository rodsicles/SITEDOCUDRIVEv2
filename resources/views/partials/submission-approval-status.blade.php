@php
    /** @var \App\Models\TeachingGuide|\App\Models\ExamQuestionnaire $submission */
    $reviewStatus = $submission->review_status ?? app(\App\Services\SubmissionReviewService::class)->status($submission);
    $icon = match ($reviewStatus['key']) {
        'needs_revision' => 'fa-pen-to-square',
        'revised' => 'fa-code-branch',
        'viewed' => 'fa-eye',
        'approved' => 'fa-check',
        'rejected' => 'fa-times',
        default => 'fa-paper-plane',
    };
@endphp
<div class="submission-status-cell">
    <span class="submission-status submission-status--{{ $reviewStatus['key'] }}">
        <i class="fas {{ $icon }}" aria-hidden="true"></i>
        {{ $reviewStatus['label'] }}
    </span>
    @if($reviewStatus['detail'])
        <span class="submission-status-meta" title="{{ $reviewStatus['detail'] }}">{{ \Illuminate\Support\Str::limit($reviewStatus['detail'], 40) }}</span>
    @endif
</div>
