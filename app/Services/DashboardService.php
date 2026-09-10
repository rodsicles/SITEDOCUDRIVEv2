<?php

namespace App\Services;

use App\Models\User;
use App\Models\Employee;
use App\Models\Task;
use App\Models\Document;
use App\Models\Folder;
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
     * Get Dean dashboard statistics.
     */
    public function getDeanStats(int $userId): array
    {
        return Cache::remember("dean_stats_{$userId}", now()->addMinutes(5), function () use ($userId) {
            return [
                'totalEmployees' => Employee::count(),
                'totalDocuments' => Document::count(),
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
                'totalTasks' => Task::where('assigned_to', $userId)->count(),
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
                'completedTasks' => Task::where('assigned_to', $userId)
                    ->where('status', 'Completed')
                    ->count(),
            ];
        });
    }

    /**
     * Payload for the Faculty ops command-center dashboard.
     */
    public function getFacultyCommandCenter(User $user): array
    {
        $userId = (int) $user->id;

        return Cache::remember("faculty_command_center_{$userId}", now()->addMinutes(5), function () use ($user, $userId) {
            $stats = $this->getFacultyStats($userId);

            $openTasksCount = Task::where('assigned_to', $userId)
                ->whereIn('status', ['Pending', 'In Progress'])
                ->count();

            $overdueCount = Task::where('assigned_to', $userId)
                ->whereIn('status', ['Pending', 'In Progress'])
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', today())
                ->count();

            $unreadCount = $this->getUnreadNotificationCount($userId);

            $pendingTeachingGuidesCount = \App\Models\TeachingGuide::query()
                ->where('user_id', $userId)
                ->where('status', 'pending')
                ->count();

            $pendingExamQuestionnairesCount = \App\Models\ExamQuestionnaire::query()
                ->where('submitted_by', $userId)
                ->where('status', 'pending')
                ->count();

            $awaitingApproval = $pendingTeachingGuidesCount + $pendingExamQuestionnairesCount;
            $attentionCount = $overdueCount + $unreadCount + $awaitingApproval;

            if ($overdueCount > 0) {
                $bannerUrl = route('faculty.tasks', ['filter' => 'overdue']);
                $bannerCta = 'Review overdue tasks';
            } elseif ($awaitingApproval > 0) {
                $bannerUrl = $pendingTeachingGuidesCount >= $pendingExamQuestionnairesCount
                    ? route('faculty.teaching-guides.index')
                    : route('faculty.exam-questionnaires.index');
                $bannerCta = 'Check submissions awaiting approval';
            } elseif ($unreadCount > 0) {
                $bannerUrl = route('faculty.notifications');
                $bannerCta = 'Open notifications';
            } elseif ($openTasksCount > 0) {
                $bannerUrl = route('faculty.tasks', ['filter' => 'pending']);
                $bannerCta = 'Continue open tasks';
            } else {
                $bannerUrl = route('faculty.documents');
                $bannerCta = 'Open documents';
            }

            $recentTasks = Task::with(['assignedBy.employee'])
                ->where('assigned_to', $userId)
                ->latest()
                ->take(5)
                ->get();

            $upcomingDeadlines = Task::with(['assignedBy.employee'])
                ->where('assigned_to', $userId)
                ->whereIn('status', ['Pending', 'In Progress'])
                ->whereNotNull('due_date')
                ->where('due_date', '<=', now()->addDays(7))
                ->orderBy('due_date')
                ->take(5)
                ->get();

            $overdueTasks = Task::with(['assignedBy.employee'])
                ->where('assigned_to', $userId)
                ->whereIn('status', ['Pending', 'In Progress'])
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', today())
                ->orderBy('due_date')
                ->take(5)
                ->get();

            $unreadNotifications = Notification::where('user_id', $userId)
                ->where('is_read', false)
                ->latest()
                ->take(5)
                ->get();

            $recentDocuments = Document::with(['uploader.employee'])
                ->where('uploaded_by', $userId)
                ->latest()
                ->take(5)
                ->get();

            $announcements = $this->getAnnouncements($user, 3);
            $activityPulse = $this->getRecentActivities($user, 4);

            $rangeStart = now()->subDays(6)->startOfDay();

            $loginsByDay = DashboardLog::where('user_id', $userId)
                ->where('activity_type', 'login')
                ->where('log_date', '>=', $rangeStart)
                ->select(DB::raw('DATE(log_date) as day'), DB::raw('COUNT(*) as aggregate'))
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('aggregate', 'day');

            $usageByDay = DashboardLog::where('user_id', $userId)
                ->where('log_date', '>=', $rangeStart)
                ->whereNotIn('activity_type', ['login_failed', 'login_throttled'])
                ->select(DB::raw('DATE(log_date) as day'), DB::raw('COUNT(*) as aggregate'))
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('aggregate', 'day');

            $loginBars = [];
            $usageBars = [];
            $loginMax = 1;
            $usageMax = 1;
            $loginTotal = 0;
            $usageTotal = 0;
            $activeDays = 0;

            for ($i = 6; $i >= 0; $i--) {
                $day = now()->subDays($i)->toDateString();
                $loginCount = (int) ($loginsByDay[$day] ?? 0);
                $usageCount = (int) ($usageByDay[$day] ?? 0);
                $label = now()->subDays($i)->format('D');

                $loginBars[] = ['label' => $label, 'count' => $loginCount];
                $usageBars[] = ['label' => $label, 'count' => $usageCount];
                $loginMax = max($loginMax, $loginCount);
                $usageMax = max($usageMax, $usageCount);
                $loginTotal += $loginCount;
                $usageTotal += $usageCount;
                if ($usageCount > 0 || $loginCount > 0) {
                    $activeDays++;
                }
            }

            return array_merge($stats, [
                'openTasksCount' => $openTasksCount,
                'overdueCount' => $overdueCount,
                'unreadCount' => $unreadCount,
                'pendingTeachingGuidesCount' => $pendingTeachingGuidesCount,
                'pendingExamQuestionnairesCount' => $pendingExamQuestionnairesCount,
                'awaitingApproval' => $awaitingApproval,
                'attentionCount' => $attentionCount,
                'bannerUrl' => $bannerUrl,
                'bannerCta' => $bannerCta,
                'recentTasks' => $recentTasks,
                'upcomingDeadlines' => $upcomingDeadlines,
                'overdueTasks' => $overdueTasks,
                'unreadNotifications' => $unreadNotifications,
                'recentDocuments' => $recentDocuments,
                'announcements' => $announcements,
                'activityPulse' => $activityPulse,
                'loginBars' => $loginBars,
                'loginMax' => $loginMax,
                'loginTotal' => $loginTotal,
                'usageBars' => $usageBars,
                'usageMax' => $usageMax,
                'usageTotal' => $usageTotal,
                'activeDays' => $activeDays,
            ]);
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
     * Get analytics data: task statuses.
     */
    public function getAnalyticsData(): array
    {
        return Cache::remember('analytics_data', now()->addMinutes(10), function () {
            $taskStatusData = Task::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get();

            return compact('taskStatusData');
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
     * Payload for the Dean ops command-center dashboard.
     */
    public function getDeanCommandCenter(User $user): array
    {
        $userId = (int) $user->id;

        return Cache::remember("dean_command_center_{$userId}", now()->addMinutes(5), function () use ($user, $userId) {
            $stats = $this->getDeanStats($userId);
            $activeId = \App\Models\SchoolYear::activeId();

            $pendingScope = fn ($q) => $q->where('status', 'pending')
                ->where(function ($q2) use ($activeId) {
                    $q2->where('school_year_id', $activeId)->orWhereNull('school_year_id');
                });

            $pendingTeachingGuidesCount = \App\Models\TeachingGuide::query()->where($pendingScope)->count();
            $pendingExamQuestionnairesCount = \App\Models\ExamQuestionnaire::query()->where($pendingScope)->count();
            $pendingApprovals = $pendingTeachingGuidesCount + $pendingExamQuestionnairesCount;

            $passwordResetCount = \App\Models\PasswordResetRequest::pending()
                ->notExpired()
                ->count();

            $docsThisSchoolYear = Document::where(function ($q) use ($activeId) {
                $q->where('school_year_id', $activeId)->orWhereNull('school_year_id');
            })->count();

            $tasksInProgress = Task::where('status', 'In Progress')->count();

            $attentionCount = $pendingApprovals + $passwordResetCount;

            if ($pendingApprovals > 0) {
                $bannerUrl = $pendingTeachingGuidesCount >= $pendingExamQuestionnairesCount
                    ? route('dean.teaching-guides.index', ['status' => 'pending'])
                    : route('dean.exam-questionnaires.index', ['status' => 'pending']);
                $bannerCta = 'Review pending files';
            } elseif ($passwordResetCount > 0) {
                $bannerUrl = route('password-reset-requests.index', ['tab' => 'pending']);
                $bannerCta = 'Review password resets';
            } else {
                $bannerUrl = route('dean.notifications');
                $bannerCta = 'Open notifications';
            }

            $recentTasks = Task::with(['assignedTo.employee'])
                ->latest()
                ->take(5)
                ->get();

            $overdueTasks = Task::with(['assignedTo.employee'])
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString())
                ->where('status', '!=', 'Completed')
                ->orderBy('due_date')
                ->take(5)
                ->get();

            $unreadNotifications = Notification::where('user_id', $userId)
                ->where('is_read', false)
                ->latest()
                ->take(5)
                ->get();

            $pendingPasswordResets = \App\Models\PasswordResetRequest::pending()
                ->notExpired()
                ->with(['user.employee'])
                ->latest()
                ->take(5)
                ->get();

            $recentDocuments = Document::with(['uploader.employee'])
                ->latest()
                ->take(5)
                ->get();

            $announcements = $this->getAnnouncements($user, 3);
            $activityPulse = DashboardLog::with(['user.employee'])
                ->latest('log_date')
                ->limit(4)
                ->get();
            $analytics = $this->getAnalyticsData();

            $uploadsLast7Days = Document::where('created_at', '>=', now()->subDays(6)->startOfDay())
                ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as aggregate'))
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('aggregate', 'day');

            $uploadBars = [];
            $uploadMax = 1;
            for ($i = 6; $i >= 0; $i--) {
                $day = now()->subDays($i)->toDateString();
                $count = (int) ($uploadsLast7Days[$day] ?? 0);
                $uploadBars[] = [
                    'label' => now()->subDays($i)->format('D'),
                    'count' => $count,
                ];
                $uploadMax = max($uploadMax, $count);
            }

            $taskStatusBars = [];
            $taskMax = 1;
            foreach ($analytics['taskStatusData'] as $row) {
                $count = (int) $row->count;
                $taskStatusBars[] = [
                    'label' => (string) $row->status,
                    'count' => $count,
                ];
                $taskMax = max($taskMax, $count);
            }

            return array_merge($stats, [
                'recentTasks' => $recentTasks,
                'pendingTeachingGuidesCount' => $pendingTeachingGuidesCount,
                'pendingExamQuestionnairesCount' => $pendingExamQuestionnairesCount,
                'pendingApprovals' => $pendingApprovals,
                'docsThisSchoolYear' => $docsThisSchoolYear,
                'tasksInProgress' => $tasksInProgress,
                'passwordResetCount' => $passwordResetCount,
                'attentionCount' => $attentionCount,
                'bannerUrl' => $bannerUrl,
                'bannerCta' => $bannerCta,
                'overdueTasks' => $overdueTasks,
                'unreadNotifications' => $unreadNotifications,
                'pendingPasswordResets' => $pendingPasswordResets,
                'recentDocuments' => $recentDocuments,
                'announcements' => $announcements,
                'activityPulse' => $activityPulse,
                'uploadBars' => $uploadBars,
                'uploadMax' => $uploadMax,
                'taskStatusBars' => $taskStatusBars,
                'taskMax' => $taskMax,
            ]);
        });
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
