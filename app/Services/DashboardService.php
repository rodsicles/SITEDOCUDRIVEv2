<?php

namespace App\Services;

use App\Models\User;
use App\Models\Employee;
use App\Models\Task;
use App\Models\Document;
use App\Models\Folder;
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
        $user = User::with('employee')->find($userId);
        $dept = optional($user->employee)->department;

        return Cache::remember("coordinator_stats_{$userId}_{$dept}", now()->addMinutes(5), function () use ($userId, $dept) {
            $facultyQuery = User::where('role_id', 3);
            if ($dept) {
                $facultyQuery->whereHas('employee', function ($q) use ($dept) {
                    $q->where('department', $dept);
                });
            }

            return [
                'totalFaculty' => $facultyQuery->count(),
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

    /**
     * Get document analytics for Dean dashboard (system-wide).
     */
    public function getDeanDocumentAnalytics(): array
    {
        return Cache::remember('dean_document_analytics', now()->addMinutes(5), function () {
            $docsThisMonth = Document::whereMonth('created_at', date('m'))
                ->whereYear('created_at', date('Y'))
                ->count();

            $topType = Document::select('document_type', DB::raw('count(*) as count'))
                ->whereNotNull('document_type')
                ->where('document_type', '!=', '')
                ->groupBy('document_type')
                ->orderByDesc('count')
                ->first();

            $mostUsedFolder = Folder::withCount('documents')
                ->having('documents_count', '>', 0)
                ->orderByDesc('documents_count')
                ->first();

            $topUploader = Document::select('uploaded_by', DB::raw('count(*) as count'))
                ->whereHas('uploader', function ($q) {
                    $q->where('role_id', 3);
                })
                ->groupBy('uploaded_by')
                ->orderByDesc('count')
                ->with('uploader.employee')
                ->first();

            $totalFolders = Folder::count();
            $totalDocs = Document::count();

            return [
                'docAnalytics' => [
                    'docsThisMonth' => $docsThisMonth,
                    'topDocType' => $topType ? ucfirst($topType->document_type) : 'N/A',
                    'topDocTypeCount' => $topType ? $topType->count : 0,
                    'mostUsedFolder' => $mostUsedFolder ? $mostUsedFolder->folder_name : 'N/A',
                    'mostUsedFolderCount' => $mostUsedFolder ? $mostUsedFolder->documents_count : 0,
                    'topUploaderName' => $topUploader ? ($topUploader->uploader->employee->full_name ?? 'Unknown') : 'N/A',
                    'topUploaderCount' => $topUploader ? $topUploader->count : 0,
                    'totalFolders' => $totalFolders,
                    'totalDocs' => $totalDocs,
                ],
            ];
        });
    }

    /**
     * Get document analytics for Coordinator dashboard (dept-scoped).
     */
    public function getCoordinatorDocumentAnalytics(int $userId): array
    {
        return Cache::remember("coordinator_document_analytics_{$userId}", now()->addMinutes(5), function () use ($userId) {
            $user = User::with('employee')->find($userId);
            $coordinatorDept = optional($user->employee)->department;

            // Build scoped query: own docs + faculty docs from same department
            $scopedIds = Document::where(function ($q) use ($userId, $coordinatorDept) {
                $q->where('uploaded_by', $userId);
                if ($coordinatorDept) {
                    $q->orWhereHas('uploader', function ($sq) use ($coordinatorDept) {
                        $sq->where('role_id', 3)
                           ->whereHas('employee', function ($eq) use ($coordinatorDept) {
                               $eq->where('department', $coordinatorDept);
                           });
                    });
                }
            })->pluck('document_id');

            $deptTotal = $scopedIds->count();

            $docsThisMonth = Document::whereIn('document_id', $scopedIds)
                ->whereMonth('created_at', date('m'))
                ->whereYear('created_at', date('Y'))
                ->count();

            $topType = Document::whereIn('document_id', $scopedIds)
                ->select('document_type', DB::raw('count(*) as count'))
                ->whereNotNull('document_type')
                ->where('document_type', '!=', '')
                ->groupBy('document_type')
                ->orderByDesc('count')
                ->first();

            $mostUsedFolder = Folder::where('user_id', $userId)
                ->withCount('documents')
                ->having('documents_count', '>', 0)
                ->orderByDesc('documents_count')
                ->first();

            $topUploader = Document::whereIn('document_id', $scopedIds)
                ->select('uploaded_by', DB::raw('count(*) as count'))
                ->whereHas('uploader', function ($q) {
                    $q->where('role_id', 3);
                })
                ->groupBy('uploaded_by')
                ->orderByDesc('count')
                ->with('uploader.employee')
                ->first();

            $myDocs = Document::where('uploaded_by', $userId)->count();
            $myFolders = Folder::where('user_id', $userId)->count();

            return [
                'docAnalytics' => [
                    'deptTotal' => $deptTotal,
                    'myDocs' => $myDocs,
                    'docsThisMonth' => $docsThisMonth,
                    'topDocType' => $topType ? ucfirst($topType->document_type) : 'N/A',
                    'topDocTypeCount' => $topType ? $topType->count : 0,
                    'mostUsedFolder' => $mostUsedFolder ? $mostUsedFolder->folder_name : 'N/A',
                    'mostUsedFolderCount' => $mostUsedFolder ? $mostUsedFolder->documents_count : 0,
                    'topUploaderName' => $topUploader ? ($topUploader->uploader->employee->full_name ?? 'Unknown') : 'N/A',
                    'topUploaderCount' => $topUploader ? $topUploader->count : 0,
                    'myFolders' => $myFolders,
                ],
            ];
        });
    }
}
