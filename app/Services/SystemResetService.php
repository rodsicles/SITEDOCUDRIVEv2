<?php

namespace App\Services;

use App\Models\DashboardLog;
use App\Models\SystemResetEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemResetService
{
    public function latest(string $scope): ?SystemResetEvent
    {
        if (!Schema::hasTable('system_reset_events')) return null;
        return SystemResetEvent::where('scope', $scope)->latest('reset_at')->latest('id')->first();
    }

    public function monthMetrics(?\DateTimeInterface $until = null, ?SystemResetEvent $reset = null): array
    {
        $until ??= now();
        $start = \Illuminate\Support\Carbon::parse($until)->startOfMonth();
        $afterReset = $reset && $reset->reset_at->greaterThan($start);
        if ($afterReset) $start = $reset->reset_at;
        $metrics = [];
        foreach (['documents' => 'Documents uploaded', 'teaching_guides' => 'Teaching guides',
            'exam_questionnaires' => 'Exam questionnaires', 'tasks' => 'Tasks created',
            'dashboard_logs' => 'Recorded actions'] as $table => $label) {
            $column = $table === 'dashboard_logs' ? 'log_date' : 'created_at';
            $metrics[$label] = DB::table($table)
                ->where($column, $afterReset ? '>' : '>=', $start)
                ->where($column, '<=', $until)
                ->count();
        }
        return $metrics;
    }

    public function resetThisMonth(User $actor): SystemResetEvent
    {
        abort_unless($actor->isDean(), 403);
        abort_unless(Schema::hasTable('system_reset_events'), 503, 'The analytics reset migration is pending.');
        $time = now();
        return DB::transaction(fn () => SystemResetEvent::create([
            'scope' => 'analytics_month', 'reset_at' => $time,
            'closed_metrics' => $this->monthMetrics($time, $this->latest('analytics_month')),
            'reset_by' => $actor->id,
        ]));
    }

    public function resetActivityLog(User $actor): SystemResetEvent
    {
        abort_unless($actor->isDean(), 403);
        abort_unless(Schema::hasTable('system_reset_events'), 503, 'The activity log reset migration is pending.');
        return DB::transaction(function () use ($actor) {
            $reset = SystemResetEvent::create([
                'scope' => 'activity_log',
                'reset_at' => now(),
                'closed_metrics' => ['cutoff_log_id' => DashboardLog::max('log_id') ?? 0],
                'reset_by' => $actor->id,
            ]);

            DashboardLog::create([
                'user_id' => $actor->id,
                'activity' => 'Reset Activity Log view',
                'activity_type' => 'Activity Log reset',
            ]);

            return $reset;
        });
    }
}
