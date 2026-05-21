<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\DashboardLog;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * WeeklyInsightService
 *
 * Generates a deterministic, data-driven narrative briefing for the Dean.
 * Every figure in the produced paragraph is computed from real database
 * records — no randomness, no AI calls — making the output explainable
 * and reproducible (important for academic defensibility).
 */
class WeeklyInsightService
{
    /**
     * Cache key TTL: 1 hour. Briefing recomputes hourly so the Dean
     * always sees fresh figures without hammering the DB on every load.
     */
    protected const CACHE_TTL_MINUTES = 60;

    /**
     * Generate the full briefing payload (raw metrics + narrative HTML).
     *
     * @return array{
     *   period_label: string,
     *   generated_at: \Carbon\Carbon,
     *   metrics: array<string, mixed>,
     *   narrative: string,
     *   highlights: array<int, array{label:string, value:string, tone:string}>,
     *   recommendations: array<int, string>
     * }
     */
    public function generateForDean(): array
    {
        return Cache::remember('weekly_insight_dean', now()->addMinutes(self::CACHE_TTL_MINUTES), function () {
            $now      = now();
            $thisWeek = ['start' => $now->copy()->startOfWeek(), 'end' => $now->copy()->endOfWeek()];
            $lastWeek = ['start' => $now->copy()->subWeek()->startOfWeek(), 'end' => $now->copy()->subWeek()->endOfWeek()];

            $metrics = $this->collectMetrics($thisWeek, $lastWeek);

            return [
                'period_label'    => $thisWeek['start']->format('M d') . ' – ' . $thisWeek['end']->format('M d, Y'),
                'generated_at'    => $now,
                'metrics'         => $metrics,
                'narrative'       => $this->composeNarrative($metrics),
                'highlights'      => $this->buildHighlights($metrics),
                'recommendations' => $this->buildRecommendations($metrics),
            ];
        });
    }

    /**
     * Collect every metric used by the narrative composer.
     */
    protected function collectMetrics(array $thisWeek, array $lastWeek): array
    {
        // Activity totals
        $activityThis = DashboardLog::whereBetween('log_date', [$thisWeek['start'], $thisWeek['end']])->count();
        $activityLast = DashboardLog::whereBetween('log_date', [$lastWeek['start'], $lastWeek['end']])->count();

        // Top performer (most completed tasks this week)
        $topPerformer = Task::with('assignedTo.employee')
            ->where('status', 'Completed')
            ->whereBetween('updated_at', [$thisWeek['start'], $thisWeek['end']])
            ->select('assigned_to', DB::raw('COUNT(*) as completed_count'))
            ->groupBy('assigned_to')
            ->orderByDesc('completed_count')
            ->first();

        // Inactive faculty (no logged activity in the last 5 days)
        $cutoff = now()->subDays(5);
        $activeFacultyIds = DashboardLog::where('log_date', '>=', $cutoff)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        $inactiveFaculty = User::with('employee')
            ->where('role_id', 3)
            ->where('status', 'Active')
            ->whereNotIn('id', $activeFacultyIds)
            ->get()
            ->map(fn($u) => $u->employee->full_name ?? $u->username)
            ->values();

        // Most-engaged announcement (highest read rate this week)
        $totalFaculty = max(1, User::where('role_id', 3)->where('status', 'Active')->count());

        $announcementsThisWeek = Announcement::with('reads')
            ->whereBetween('created_at', [$thisWeek['start'], $thisWeek['end']])
            ->get();

        $topAnnouncement = $announcementsThisWeek
            ->map(fn($a) => [
                'title'     => $a->title,
                'reads'     => $a->reads->count(),
                'read_rate' => round(($a->reads->count() / $totalFaculty) * 100),
            ])
            ->sortByDesc('read_rate')
            ->first();

        // Task overdue rate this week vs last week
        $overdueRateThis = $this->overdueRate($thisWeek);
        $overdueRateLast = $this->overdueRate($lastWeek);

        return [
            'this_week_label'   => $thisWeek['start']->format('M d') . '–' . $thisWeek['end']->format('M d'),
            'activity_this'     => $activityThis,
            'activity_last'     => $activityLast,
            'activity_delta_pc' => $this->deltaPercent($activityThis, $activityLast),
            'top_performer'     => $topPerformer ? [
                'name'  => optional($topPerformer->assignedTo->employee)->full_name
                    ?? optional($topPerformer->assignedTo)->username
                    ?? 'A faculty member',
                'count' => (int) $topPerformer->completed_count,
            ] : null,
            'inactive_count'    => $inactiveFaculty->count(),
            'inactive_names'    => $inactiveFaculty->take(3)->all(),
            'top_announcement'  => $topAnnouncement,
            'overdue_rate_this' => $overdueRateThis,
            'overdue_rate_last' => $overdueRateLast,
            'overdue_jumped'    => $overdueRateThis - $overdueRateLast >= 10,
        ];
    }

    /**
     * Compose the narrative paragraph from raw metrics.
     * Uses inline <strong> tags so figures stand out in the rendered card.
     */
    protected function composeNarrative(array $m): string
    {
        $parts = [];

        // Greeting (time-aware)
        $greeting = match (true) {
            now()->hour < 12 => 'Good morning',
            now()->hour < 18 => 'Good afternoon',
            default          => 'Good evening',
        };
        $parts[] = $greeting . ', Dean.';

        // Activity sentence with directional language
        if ($m['activity_last'] === 0 && $m['activity_this'] === 0) {
            $parts[] = 'No system activity has been recorded this week yet.';
        } elseif ($m['activity_last'] === 0) {
            $parts[] = 'This week, your department logged <strong>' . $m['activity_this'] . ' activities</strong> — a fresh start versus a quiet prior week.';
        } else {
            $direction = $m['activity_delta_pc'] > 0 ? 'increase' : ($m['activity_delta_pc'] < 0 ? 'decrease' : 'no change');
            $parts[] = 'This week, your department logged <strong>' . number_format($m['activity_this']) . ' activities</strong> — a <strong>'
                . abs($m['activity_delta_pc']) . '% ' . $direction . '</strong> compared to last week.';
        }

        // Top performer
        if ($m['top_performer']) {
            $parts[] = '<strong>' . e($m['top_performer']['name']) . '</strong> had the highest task completion rate ('
                . $m['top_performer']['count'] . ' completed).';
        }

        // Inactive faculty
        if ($m['inactive_count'] > 0) {
            $names = collect($m['inactive_names'])->map(fn($n) => '<strong>' . e($n) . '</strong>')->implode(', ');
            $extra = $m['inactive_count'] > count($m['inactive_names'])
                ? ' (and ' . ($m['inactive_count'] - count($m['inactive_names'])) . ' more)'
                : '';
            $parts[] = '<strong>' . $m['inactive_count'] . ' faculty</strong> had no system activity for 5+ days: ' . $names . $extra . '.';
        } else {
            $parts[] = 'All faculty have been active in the system within the last 5 days.';
        }

        // Most-engaged announcement
        if ($m['top_announcement'] && $m['top_announcement']['read_rate'] > 0) {
            $parts[] = "The most-engaged announcement was &ldquo;<strong>" . e($m['top_announcement']['title']) . '</strong>&rdquo; (read by '
                . $m['top_announcement']['read_rate'] . '% of faculty).';
        }

        // Anomaly: overdue task rate jumped
        if ($m['overdue_jumped']) {
            $parts[] = '<span class="text-red-600 dark:text-red-400">One anomaly detected:</span> task overdue rate jumped from <strong>'
                . $m['overdue_rate_last'] . '%</strong> to <strong>' . $m['overdue_rate_this'] . '%</strong> — recommend reviewing workload distribution.';
        }

        return implode(' ', $parts);
    }

    /**
     * Build short highlight chips for a glance-worthy summary row.
     */
    protected function buildHighlights(array $m): array
    {
        return [
            [
                'label' => 'Total Activities',
                'value' => number_format($m['activity_this']),
                'tone'  => $m['activity_delta_pc'] >= 0 ? 'positive' : 'negative',
            ],
            [
                'label' => 'Inactive Faculty (5+ days)',
                'value' => (string) $m['inactive_count'],
                'tone'  => $m['inactive_count'] === 0 ? 'positive' : 'warning',
            ],
            [
                'label' => 'Overdue Rate',
                'value' => $m['overdue_rate_this'] . '%',
                'tone'  => $m['overdue_jumped'] ? 'negative' : 'neutral',
            ],
        ];
    }

    /**
     * Heuristic recommendations derived from the same metrics.
     */
    protected function buildRecommendations(array $m): array
    {
        $recs = [];

        if ($m['inactive_count'] > 0) {
            $recs[] = 'Reach out to inactive faculty — they may need access support or have unaddressed concerns.';
        }
        if ($m['overdue_jumped']) {
            $recs[] = 'Open the Tasks view filtered by Overdue — current rate (' . $m['overdue_rate_this'] . '%) is significantly higher than last week.';
        }
        if ($m['activity_delta_pc'] < -25) {
            $recs[] = 'System engagement dropped sharply — consider posting an announcement or scheduling a faculty check-in.';
        }
        if (empty($recs)) {
            $recs[] = 'No immediate action required. Department metrics are within healthy ranges.';
        }

        return $recs;
    }

    /**
     * Compute the % of tasks (due in window) that ended up overdue.
     */
    protected function overdueRate(array $window): int
    {
        $totalDue = Task::whereBetween('due_date', [$window['start']->toDateString(), $window['end']->toDateString()])->count();
        if ($totalDue === 0) {
            return 0;
        }
        $overdue = Task::whereBetween('due_date', [$window['start']->toDateString(), $window['end']->toDateString()])
            ->where('status', '!=', 'Completed')
            ->where('due_date', '<', now()->toDateString())
            ->count();

        return (int) round(($overdue / $totalDue) * 100);
    }

    /**
     * Safe percent change a→b.
     */
    protected function deltaPercent(int $current, int $previous): int
    {
        if ($previous === 0) {
            return $current === 0 ? 0 : 100;
        }
        return (int) round((($current - $previous) / $previous) * 100);
    }
}
