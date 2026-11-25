<?php

namespace App\Services;

use App\Models\DashboardLog;
use App\Models\ExamQuestionnaire;
use App\Models\Role;
use App\Models\SubmissionComment;
use App\Models\SubmissionView;
use App\Models\TeachingGuide;
use App\Models\User;
use App\Support\UploadStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Comment-based review for Teaching Guides and Exam Questionnaires.
 * Submissions are live on upload; reviewers (Dean office and handling Coordinators) record
 * views and leave revision notes; owners upload revised versions and mark notes addressed.
 */
class SubmissionReviewService
{
    public function __construct(protected NotificationService $notifications) {}

    public static function available(): bool
    {
        static $ready;

        return $ready ??= Schema::hasTable('submission_comments') && Schema::hasTable('submission_views');
    }

    /** @return TeachingGuide|ExamQuestionnaire */
    public function find(string $type, int $id, User $viewer)
    {
        $model = match ($type) {
            'teaching-guide', TeachingGuide::REVIEW_TYPE => TeachingGuide::class,
            'exam-questionnaire', ExamQuestionnaire::REVIEW_TYPE => ExamQuestionnaire::class,
            default => abort(404),
        };

        return $model::query()->with('document')->visibleTo($viewer)->findOrFail($id);
    }

    public function typeOf($submission): string
    {
        return $submission instanceof TeachingGuide ? TeachingGuide::REVIEW_TYPE : ExamQuestionnaire::REVIEW_TYPE;
    }

    public function slugOf($submission): string
    {
        return $submission instanceof TeachingGuide ? 'teaching-guide' : 'exam-questionnaire';
    }

    public function ownerId($submission): int
    {
        return (int) ($submission instanceof TeachingGuide ? $submission->user_id : $submission->submitted_by);
    }

    public function version($submission): int
    {
        return max(1, (int) ($submission->document?->version ?? 1));
    }

    public function isOwner($submission, User $user): bool
    {
        return $this->ownerId($submission) === (int) $user->id;
    }

    /** Dean office (Dean/Secretary/Admin) and Coordinators who can see the submission review it; never the owner. */
    public function canReview($submission, User $user): bool
    {
        return ($user->hasOversightAccess() || $user->isProgramCoordinator())
            && !$this->isOwner($submission, $user)
            && $submission->isVisibleTo($user)
            && !$submission->isRejected();
    }

    public function reviewerLabel(User $user): string
    {
        return match (true) {
            $user->isDean() => 'Dean',
            $user->isProgramCoordinator() => 'Coordinator',
            $user->isAdmin() => 'Admin',
            $user->isSecretary() => 'Secretary',
            default => $user->role?->role_name ?? 'Reviewer',
        };
    }

    public function recordView($submission, User $user): void
    {
        if (!self::available() || !$this->canReview($submission, $user)) {
            return;
        }

        SubmissionView::firstOrCreate(
            ['submission_type' => $this->typeOf($submission), 'submission_id' => $submission->id, 'user_id' => $user->id, 'version' => $this->version($submission)],
            ['role_name' => $this->reviewerLabel($user), 'viewed_at' => now()],
        );
    }

    public function addComment($submission, User $user, string $body, ?string $location): SubmissionComment
    {
        abort_unless(self::available(), 503, 'Revision notes are awaiting their database migration.');
        abort_unless($this->canReview($submission, $user), 403, 'You cannot leave revision notes on this submission.');
        $this->recordView($submission, $user);

        $comment = SubmissionComment::create([
            'submission_type' => $this->typeOf($submission),
            'submission_id' => $submission->id,
            'user_id' => $user->id,
            'version' => $this->version($submission),
            'location' => ($location = trim((string) $location)) === '' ? null : Str::limit($location, 150, ''),
            'body' => trim($body),
        ]);

        $where = $comment->location ? " ({$comment->location})" : '';
        $this->notifications->notify(
            $this->ownerId($submission),
            $this->reviewerLabel($user).' left a revision note on "'.$submission->title.'"'.$where.': '.Str::limit($comment->body, 120),
            \App\Models\Notification::TONE_DANGER,
            $this->viewUrlFor($submission, User::find($this->ownerId($submission))),
        );
        $this->log($user, 'Left a revision note on '.$submission->title.$where, 'submission_comment_added', $this->ownerId($submission));

        return $comment;
    }

    public function markAddressed(SubmissionComment $comment, User $user): void
    {
        $submission = $comment->submission();
        abort_unless($submission && $this->isOwner($submission, $user), 403, 'Only the submitter can mark notes as addressed.');
        if ($comment->isOpen()) {
            $comment->update(['addressed_at' => now(), 'addressed_by' => $user->id]);
            $this->notifications->notify($comment->user_id, ($user->employee->full_name ?? $user->username).' addressed your note on "'.$submission->title.'".', \App\Models\Notification::TONE_SUCCESS, $this->viewUrlFor($submission, User::find($comment->user_id)));
            $this->log($user, 'Marked a revision note addressed on '.$submission->title, 'submission_comment_addressed');
        }
    }

    /** Owner uploads a revised file. The previous file stays in version history. */
    public function revise($submission, User $user, UploadedFile $file, ?string $note = null): void
    {
        abort_unless($this->isOwner($submission, $user), 403, 'Only the submitter can upload a revised version.');
        abort_if($submission->isRejected(), 422, 'Rejected submissions cannot be revised. Upload a new submission instead.');
        $document = $submission->document;
        abort_unless($document, 422, 'This submission is not available in Documents yet. Contact the Dean office.');

        DB::transaction(function () use ($submission, $user, $file, $note, $document) {
            $updated = app(DocumentService::class)->uploadNewVersion($document, $file, $user, $note ?: 'Revised after review');
            $submission->forceFill([
                'file_path' => $updated->file_path,
                'original_filename' => $updated->original_filename,
                'file_type' => $updated->document_type === 'word' ? 'word' : 'pdf',
            ])->save();
        });

        $submission->refresh()->load('document');
        $version = $this->version($submission);
        $reviewers = SubmissionComment::where('submission_type', $this->typeOf($submission))->where('submission_id', $submission->id)->pluck('user_id')
            ->merge($this->reviewerIds($submission))->unique()->reject(fn ($id) => (int) $id === (int) $user->id)->values()->all();
        $this->notifications->notifyMany($reviewers, '"'.$submission->title.'" was revised (v'.$version.') by '.($user->employee->full_name ?? $user->username).'.', null, null,
            fn (User $recipient) => $this->viewUrlFor($submission, $recipient));
        $this->log($user, 'Uploaded revised version v'.$version.' of '.$submission->title, 'submission_revised');
    }

    /** Dean office plus Coordinators who handle the owner's program. */
    public function reviewerIds($submission): array
    {
        $owner = User::with('employee')->find($this->ownerId($submission));
        $program = $owner?->employee?->program;

        return User::where('status', 'Active')
            ->where(function ($q) use ($program) {
                $q->whereHas('role', fn ($r) => $r->whereIn('role_name', [Role::DEAN, Role::SECRETARY, Role::ADMIN]));
                if ($program) {
                    $q->orWhere(fn ($c) => $c->whereHas('role', fn ($r) => $r->where('role_name', Role::COORDINATOR))
                        ->whereHas('employee', fn ($e) => $e->where('program', $program)->orWhereHas('extraPrograms', fn ($p) => $p->where('program', $program))));
                }
            })
            ->whereKeyNot($this->ownerId($submission))
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function notifySubmitted($submission, User $uploader): void
    {
        $kind = $submission instanceof TeachingGuide ? 'teaching guide' : 'exam questionnaire';
        $this->notifications->notifyMany($this->reviewerIds($submission),
            'New '.$kind.' submitted by '.($uploader->employee->full_name ?? $uploader->username).': "'.$submission->title.'". Open it to review or leave a revision note.',
            null, null, fn (User $recipient) => $this->viewUrlFor($submission, $recipient));
    }

    public function viewUrlFor($submission, ?User $user): ?string
    {
        if (!$user) {
            return null;
        }
        $prefix = $user->isDeanOrSecretary() ? 'dean' : ($user->isProgramCoordinator() ? 'coordinator' : 'faculty');
        $name = $prefix.'.'.($submission instanceof TeachingGuide ? 'teaching-guides' : 'exam-questionnaires').'.view';

        return \Illuminate\Support\Facades\Route::has($name) ? route($name, $submission->id) : null;
    }

    /**
     * Review status shown on lists and the preview page.
     * @return array{key: string, label: string, tone: string, detail: ?string, open: int}
     */
    public function status($submission, ?Collection $comments = null, ?Collection $views = null): array
    {
        if ($submission->isRejected()) {
            return ['key' => 'rejected', 'label' => 'Rejected (before review change)', 'tone' => 'danger', 'detail' => $submission->remarks, 'open' => 0];
        }
        $comments ??= self::available() ? $submission->reviewComments : collect();
        $views ??= self::available() ? $submission->reviewViews : collect();
        $version = $this->version($submission);
        $open = $comments->whereNull('addressed_at')->count();

        if ($open > 0) {
            return ['key' => 'needs_revision', 'label' => 'Needs revision', 'tone' => 'warning', 'detail' => $open.' open '.Str::plural('note', $open), 'open' => $open];
        }
        $currentViews = $views->where('version', $version)->sortBy('viewed_at');
        if ($version > 1) {
            $viewed = $currentViews->first();
            return ['key' => 'revised', 'label' => 'Revised (v'.$version.')', 'tone' => 'info', 'detail' => $viewed ? 'Viewed by '.$viewed->role_name.' · '.$viewed->viewed_at->format('M d, Y') : 'Awaiting review', 'open' => 0];
        }
        if ($viewed = $currentViews->first()) {
            $roles = $currentViews->pluck('role_name')->unique()->implode(' & ');
            return ['key' => 'viewed', 'label' => 'Viewed by '.$roles, 'tone' => 'success', 'detail' => $viewed->viewed_at->format('M d, Y g:i A'), 'open' => 0];
        }
        if ($submission->reviewed_by) {
            return ['key' => 'approved', 'label' => 'Approved (before review change)', 'tone' => 'success', 'detail' => $submission->reviewed_at?->format('M d, Y'), 'open' => 0];
        }

        return ['key' => 'submitted', 'label' => 'Submitted', 'tone' => 'neutral', 'detail' => 'Not yet viewed', 'open' => 0];
    }

    /** Attach review_status to each item using two queries for the whole page. */
    public function decorate(iterable $items): void
    {
        $items = collect($items instanceof \Illuminate\Contracts\Pagination\Paginator ? $items->items() : $items);
        if ($items->isEmpty()) {
            return;
        }
        $type = $this->typeOf($items->first());
        $ids = $items->pluck('id')->all();
        $comments = self::available() ? SubmissionComment::where('submission_type', $type)->whereIn('submission_id', $ids)->get()->groupBy('submission_id') : collect();
        $views = self::available() ? SubmissionView::where('submission_type', $type)->whereIn('submission_id', $ids)->get()->groupBy('submission_id') : collect();
        $items->each(function ($item) use ($comments, $views) {
            $item->loadMissing('document');
            $item->setAttribute('review_status', $this->status($item, $comments->get($item->id, collect()), $views->get($item->id, collect())));
        });
    }

    /** Panel data for the preview page. */
    public function panel($submission, User $viewer): array
    {
        $comments = self::available() ? $submission->reviewComments()->with('author.employee', 'author.role')->latest()->get() : collect();
        $views = self::available() ? $submission->reviewViews()->with('viewer.employee')->orderBy('viewed_at')->get() : collect();
        $slug = $this->slugOf($submission);

        return [
            'status' => $this->status($submission, $comments, $views),
            'version' => $this->version($submission),
            'comments' => $comments,
            'views' => $views,
            'canComment' => self::available() && $this->canReview($submission, $viewer),
            'isOwner' => $this->isOwner($submission, $viewer),
            'canRevise' => $this->isOwner($submission, $viewer) && $submission->document && !$submission->isRejected(),
            'commentUrl' => route('submission-reviews.comments.store', [$slug, $submission->id]),
            'reviseUrl' => route('submission-reviews.revise', [$slug, $submission->id]),
            'acceptsWord' => $submission instanceof TeachingGuide || $submission->file_type === 'word',
        ];
    }

    /** "New submissions" for a reviewer: live, in scope, active year, not yet viewed by them. */
    public function newCountFor(User $user, string $modelClass): int
    {
        if (!self::available() || !($user->hasOversightAccess() || $user->isProgramCoordinator())) {
            return 0;
        }

        return $this->newQuery($user, $modelClass)->count();
    }

    public function newQuery(User $user, string $modelClass)
    {
        $activeId = \App\Models\SchoolYear::activeId();
        $owner = $modelClass === TeachingGuide::class ? 'user_id' : 'submitted_by';

        return $modelClass::query()->visibleTo($user)
            ->where('status', 'approved')
            ->whereNull('reviewed_by') // legacy approvals were already reviewed
            ->where($owner, '!=', $user->id)
            ->where(fn ($q) => $q->where('school_year_id', $activeId)->orWhereNull('school_year_id'))
            ->whereDoesntHave('reviewViews', fn ($v) => $v->where('user_id', $user->id));
    }

    private function log(User $user, string $activity, string $type, ?int $target = null): void
    {
        DashboardLog::create(['user_id' => $user->id, 'target_user_id' => $target, 'activity' => Str::limit($activity, 250), 'activity_type' => $type, 'visibility' => 'own']);
    }
}
