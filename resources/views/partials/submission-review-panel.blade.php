{{--
  Review panel for a Teaching Guide / Exam Questionnaire preview.
  $review comes from SubmissionReviewService::panel(): status, version, comments, views, canComment, isOwner, canRevise, commentUrl, reviseUrl, acceptsWord.
--}}
@php
    $status = $review['status'];
    $openComments = $review['comments']->whereNull('addressed_at');
    $reviseFailed = $errors->has('file') || $errors->has('note');
@endphp
<section class="submission-review-panel content-card" aria-labelledby="submissionReviewHeading">
    <div class="submission-review-panel__head">
        <div>
            <h3 id="submissionReviewHeading" class="card-title">Review</h3>
            <div class="submission-review-panel__status">
                @include('partials.submission-approval-status', ['submission' => (object) ['review_status' => $status]])
                <span class="badge badge-info">Current version v{{ $review['version'] }}</span>
            </div>
        </div>
        <div class="submission-review-panel__actions">
            @if($review['canComment'])
                <button type="button" class="btn submission-note-btn submission-note-btn--lg"
                        data-open-note-modal data-note-url="{{ $review['commentUrl'] }}"
                        data-note-title="{{ $title }}" data-note-version="{{ $review['version'] }}">
                    <i class="fas fa-comment-dots" aria-hidden="true"></i> Leave a revision note
                </button>
            @endif
            @if($review['canRevise'])
                <button type="button" class="btn btn-primary" data-open-revise-modal>
                    <i class="fas fa-upload" aria-hidden="true"></i> Upload revised version
                </button>
            @endif
        </div>
    </div>

    @if($review['isOwner'] && $openComments->isNotEmpty())
        <p class="submission-review-panel__alert" role="status">
            <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
            {{ $openComments->count() }} open {{ \Illuminate\Support\Str::plural('note', $openComments->count()) }}. Upload a revised version, then mark each note as addressed.
        </p>
    @endif

    <div class="submission-review-panel__grid">
        <div>
            <h4 class="submission-review-panel__subhead">Revision notes</h4>
            @forelse($review['comments'] as $comment)
                <article class="submission-note {{ $comment->isOpen() ? 'is-open' : 'is-addressed' }}">
                    <header>
                        <strong>{{ $comment->author?->employee?->full_name ?? $comment->author?->username ?? 'Reviewer' }}</strong>
                        <span>{{ app(\App\Services\SubmissionReviewService::class)->reviewerLabel($comment->author ?? auth()->user()) }} · v{{ $comment->version }} · {{ $comment->created_at->format('M d, Y g:i A') }}</span>
                    </header>
                    @if($comment->location)<p class="submission-note__location"><i class="fas fa-location-dot" aria-hidden="true"></i> {{ $comment->location }}</p>@endif
                    <p class="submission-note__body">{{ $comment->body }}</p>
                    <footer>
                        @if($comment->isOpen())
                            <span class="submission-status submission-status--needs_revision">Open</span>
                            @if($review['isOwner'])
                                <form method="POST" action="{{ route('submission-reviews.comments.addressed', $comment) }}" data-request-guard>
                                    @csrf
                                    <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-check" aria-hidden="true"></i> Mark addressed</button>
                                </form>
                            @endif
                        @else
                            <span class="submission-status submission-status--viewed"><i class="fas fa-check" aria-hidden="true"></i> Addressed {{ $comment->addressed_at->format('M d, Y') }}</span>
                        @endif
                    </footer>
                </article>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No revision notes yet.</p>
            @endforelse
        </div>
        <div>
            <h4 class="submission-review-panel__subhead">Viewed by</h4>
            @forelse($review['views'] as $view)
                <p class="submission-review-panel__view">
                    <i class="fas fa-eye" aria-hidden="true"></i>
                    <strong>{{ $view->role_name }}</strong> {{ $view->viewer?->employee?->full_name ?? $view->viewer?->username }}
                    <span>· v{{ $view->version }} · {{ $view->viewed_at->format('M d, Y g:i A') }}</span>
                </p>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">Not yet viewed by the Dean or Coordinator.</p>
            @endforelse
        </div>
    </div>
</section>

@if($review['canComment'])
    @include('partials.submission-note-modal')
@endif

@if($review['canRevise'])
<dialog id="submissionReviseModal" class="ui-dialog" aria-labelledby="submissionReviseTitle">
    <form method="POST" action="{{ $review['reviseUrl'] }}" enctype="multipart/form-data" class="ui-dialog__form" data-request-guard>
        @csrf
        <header class="ui-dialog__head">
            <h2 id="submissionReviseTitle" class="ui-dialog__title"><i class="fas fa-upload" aria-hidden="true"></i> Upload revised version</h2>
            <button type="button" class="ui-dialog__close" data-close-revise-modal aria-label="Close">&times;</button>
        </header>
        <div class="ui-dialog__body">
            <p class="text-sm mb-3">This becomes <strong>v{{ $review['version'] + 1 }}</strong>. The current file stays in Version History, and reviewers are notified.</p>
            @if($reviseFailed)<p class="ui-dialog__error" role="alert">{{ $errors->first('file') ?: $errors->first('note') }}</p>@endif
            <div class="form-group">
                <label class="form-label" for="submissionReviseFile">Revised file <span class="text-red-500">*</span></label>
                <input id="submissionReviseFile" type="file" name="file" class="form-control" required accept="{{ $review['acceptsWord'] ? '.pdf,.doc,.docx' : '.pdf' }}">
                <small class="text-xs text-gray-500">{{ $review['acceptsWord'] ? 'PDF or Word' : 'PDF' }}, up to 10 MB.</small>
            </div>
            <div class="form-group mb-0">
                <label class="form-label" for="submissionReviseNote">What changed? <span class="text-gray-500">(optional)</span></label>
                <input id="submissionReviseNote" name="note" class="form-control" maxlength="255" value="{{ old('note') }}" placeholder="e.g. Fixed answer key for Part II, item 5">
            </div>
        </div>
        <footer class="ui-dialog__foot">
            <button type="button" class="btn btn-secondary" data-close-revise-modal>Cancel</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-upload" aria-hidden="true"></i> Upload revision</button>
        </footer>
    </form>
</dialog>
<script>
(function () {
    var modal = document.getElementById('submissionReviseModal');
    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-open-revise-modal]')) { modal.showModal(); return; }
        if (event.target.closest('[data-close-revise-modal]') || event.target === modal) modal.close();
    });
    @if($reviseFailed) modal.showModal(); @endif
})();
</script>
@endif
