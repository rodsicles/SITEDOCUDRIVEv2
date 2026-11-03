<?php

namespace App\Services;

use App\Models\MonthlyAnalyticsSnapshot;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MonthlyAnalyticsService
{
    public function closePreviousMonth(User $dean): MonthlyAnalyticsSnapshot
    {
        abort_unless($dean->isDean(), 403);
        $start = now()->startOfMonth()->subMonth()->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return DB::transaction(function () use ($dean, $start, $end) {
            abort_if(MonthlyAnalyticsSnapshot::whereDate('period_start', $start->toDateString())->exists(), 422, 'This month is already closed.');
            $metrics = [];
            foreach (['documents' => 'Documents uploaded', 'teaching_guides' => 'Teaching guides',
                'exam_questionnaires' => 'Exam questionnaires', 'tasks' => 'Tasks created',
                'dashboard_logs' => 'Recorded actions'] as $table => $label) {
                $metrics[$label] = DB::table($table)->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])->count();
            }
            return MonthlyAnalyticsSnapshot::create([
                'period_start' => $start->toDateString(), 'period_end' => $end->toDateString(),
                'metrics' => $metrics, 'closed_by' => $dean->id,
            ]);
        });
    }
}
