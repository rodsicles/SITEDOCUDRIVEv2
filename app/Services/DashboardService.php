<?php

namespace App\Services;

use App\Models\User;
use App\Models\Employee;
use App\Models\Task;
use App\Models\Document;
use App\Models\LeaveRequest;
use App\Models\DashboardLog;
use App\Models\PerformanceReport;
use App\Models\Announcement;
use App\Models\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * Get leave request count for a user by period.
     */
    public function getLeaveCount(int $userId, string $period = 'year'): int
    {
        $query = LeaveRequest::where('user_id', $userId)
            ->whereYear('start_date', date('Y'));

        if ($period === 'month') {
            $query->whereMonth('start_date', date('m'));
        }

        return $query->count();
    }

    /**
     * Get Dean dashboard statistics.
     */
    public function getDeanStats(int $userId): array
    {
        return Cache::remember("dean_stats_{$userId}", now()->addMinutes(5), function () use ($userId) {
            return [
                'totalEmployees' => Employee::count(),
                'totalDocuments' => Document::count(),
                'leaveThisMonth' => $this->getLeaveCount($userId, 'month'),
                'leaveThisYear' => $this->getLeaveCount($userId, 'year'),
                'totalTasks' => Task::count(),
            ];
        });
    }

    /**
     * Get Coordinator dashboard statistics.
     */
    public function getCoordinatorStats(int $userId): array
    {
        return Cache::remember("coordinator_stats_{$userId}", now()->addMinutes(5), function () use ($userId) {
            return [
                'totalFaculty' => User::where('role_id', 3)->count(),
                'totalDocuments' => Document::where('uploaded_by', $userId)->count(),
                'leaveThisMonth' => $this->getLeaveCount($userId, 'month'),
                'leaveThisYear' => $this->getLeaveCount($userId, 'year'),
                'totalTasks' => Task::where('assigned_by', $userId)->count(),
            ];
        });
    }

    /**
     * Get Faculty dashboard statistics.
     */
    public function getFacultyStats(int $userId): array
    {
        return Cache::remember("faculty_stats_{$userId}", now()->addMinutes(5), function () use ($userId) {
            return [
                'totalDocuments' => Document::where('uploaded_by', $userId)->count(),
                'leaveThisMonth' => $this->getLeaveCount($userId, 'month'),
                'leaveThisYear' => $this->getLeaveCount($userId, 'year'),
                'completedTasks' => Task::where('assigned_to', $userId)
                    ->where('status', 'Completed')
                    ->count(),
            ];
        });
    }

    /**
     * Get monthly system usage data for a given year.
     */
    public function getMonthlyUsage(int $year): array
    {
        $systemUsageData = Cache::remember("monthly_usage_{$year}", now()->addMinutes(15), function () use ($year) {
            return DashboardLog::select(
                    DB::raw('MONTH(log_date) as month'),
                    DB::raw('COUNT(*) as activity_count')
                )
                ->whereYear('log_date', $year)
                ->groupBy('month')
                ->orderBy('month')
                ->get();
        });

        $monthlyUsage = array_fill(1, 12, 0);
        foreach ($systemUsageData as $data) {
            $monthlyUsage[$data->month] = $data->activity_count;
        }

        return $monthlyUsage;
    }

    /**
     * Get month name labels.
     */
    public function getMonthNames(): array
    {
        return ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    }

    /**
     * Get recent activities for a user.
     */
    public function getRecentActivities(User $user, int $limit = 10): Collection
    {
        return DashboardLog::getFilteredLogs($user, $limit);
    }

    /**
     * Get performance data for the current year.
     */
    public function getPerformanceData(): Collection
    {
        return PerformanceReport::select(
                DB::raw('AVG(rating) as avg_rating'),
                DB::raw('MONTH(report_date) as month')
            )
            ->whereYear('report_date', date('Y'))
            ->groupBy('month')
            ->get();
    }

    /**
     * Get top performers.
     */
    public function getTopPerformers(int $limit = 5): Collection
    {
        return PerformanceReport::with('employee')
            ->select('employee_id', DB::raw('AVG(rating) as avg_rating'))
            ->groupBy('employee_id')
            ->orderByDesc('avg_rating')
            ->take($limit)
            ->get();
    }

    /**
     * Get active announcements visible to a user.
     */
    public function getAnnouncements(User $user, int $limit = 5): Collection
    {
        return Announcement::with(['author.employee', 'reads'])
            ->active()
            ->visibleTo($user)
            ->ordered()
            ->take($limit)
            ->get();
    }

    /**
     * Get analytics data: task statuses, departments, monthly performance.
     */
    public function getAnalyticsData(): array
    {
        return Cache::remember('analytics_data', now()->addMinutes(10), function () {
            $taskStatusData = Task::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get();

            $departmentData = Employee::select('department', DB::raw('count(*) as count'))
                ->whereNotNull('department')
                ->groupBy('department')
                ->get();

            $monthlyPerformance = PerformanceReport::select(
                    DB::raw('DATE_FORMAT(report_date, "%Y-%m") as month'),
                    DB::raw('AVG(rating) as avg_rating'),
                    DB::raw('COUNT(*) as total_reports')
                )
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->take(12)
                ->get();

            return compact('taskStatusData', 'departmentData', 'monthlyPerformance');
        });
    }

    /**
     * Get unread notification count for a user.
     */
    public function getUnreadNotificationCount(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Get recent notifications for a user.
     */
    public function getRecentNotifications(int $userId, int $limit = 5): Collection
    {
        return Notification::where('user_id', $userId)
            ->latest()
            ->take($limit)
            ->get();
    }
}
