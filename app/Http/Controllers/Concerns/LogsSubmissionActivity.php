<?php

namespace App\Http\Controllers\Concerns;

use App\Models\DashboardLog;
use App\Models\User;

trait LogsSubmissionActivity
{
    protected function logSubmissionActivity(User $user, string $activity, string $activityType): void
    {
        DashboardLog::create([
            'user_id' => $user->id,
            'activity' => $activity,
            'activity_type' => $activityType,
            'visibility' => $user->isDeanOrSecretary() ? 'dean' : 'own',
        ]);
    }
}
