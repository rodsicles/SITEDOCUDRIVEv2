<?php

namespace App\Support;

use App\Models\ExamQuestionnaire;
use App\Models\TeachingGuide;
use App\Models\User;
use App\Services\SubmissionReviewService;

/**
 * Sidebar/dashboard badges for reviewers. There is no approval queue any more:
 * the count is "new submissions" — live faculty uploads this reviewer has not opened yet.
 */
class SubmissionPendingCounts
{
    public static function teachingGuidesFor(User $user): int
    {
        return app(SubmissionReviewService::class)->newCountFor($user, TeachingGuide::class);
    }

    public static function examQuestionnairesFor(User $user): int
    {
        return app(SubmissionReviewService::class)->newCountFor($user, ExamQuestionnaire::class);
    }

    /** Faculty badge: open revision notes on the faculty member's own submissions. */
    public static function openNotesFor(User $user, string $modelClass): int
    {
        if (!SubmissionReviewService::available()) {
            return 0;
        }
        $owner = $modelClass === TeachingGuide::class ? 'user_id' : 'submitted_by';

        return $modelClass::query()->where($owner, $user->id)
            ->whereHas('reviewComments', fn ($c) => $c->whereNull('addressed_at'))
            ->count();
    }
}
