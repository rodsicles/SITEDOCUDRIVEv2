<?php

namespace App\Support;

use App\Models\ExamQuestionnaire;
use App\Models\SchoolYear;
use App\Models\TeachingGuide;
use App\Models\User;

class SubmissionPendingCounts
{
    public static function pendingScope(): \Closure
    {
        $activeId = SchoolYear::activeId();

        return fn ($q) => $q->where('status', 'pending')
            ->where(function ($q2) use ($activeId) {
                $q2->where('school_year_id', $activeId)->orWhereNull('school_year_id');
            });
    }

    public static function teachingGuidesFor(User $user): int
    {
        $scope = self::pendingScope();

        if ($user->isDeanOrSecretary()) {
            return TeachingGuide::query()->where($scope)->count();
        }

        if ($user->isProgramCoordinator()) {
            return TeachingGuide::query()->visibleTo($user)->where($scope)->count();
        }

        return 0;
    }

    public static function examQuestionnairesFor(User $user): int
    {
        $scope = self::pendingScope();

        if ($user->isDeanOrSecretary()) {
            return ExamQuestionnaire::query()->where($scope)->count();
        }

        if ($user->isProgramCoordinator()) {
            return ExamQuestionnaire::query()->visibleTo($user)->where($scope)->count();
        }

        return 0;
    }
}
