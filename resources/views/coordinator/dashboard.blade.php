@extends('layouts.dashboard')

@section('title', 'Program Coordinator Dashboard')

@section('page-title', 'Data Analytics Dashboard')
@section('page-subtitle', 'Manage tasks and faculty performance')

@section('sidebar')
    @include('partials.coordinator-sidebar')
@endsection

@section('content')
    <!-- Minimalist Horizontal Stats -->
    <div class="stats-grid-horizontal">
        <div class="stat-item-horizontal">
            <div class="stat-icon-horizontal">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content-horizontal">
                <div class="stat-number-label"><strong>{{ $totalFaculty }}</strong> Total Faculty</div>
            </div>
        </div>

        <div class="stat-item-horizontal">
            <div class="stat-icon-horizontal">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-content-horizontal">
                <div class="stat-number-label"><strong>{{ $totalDocuments }}</strong> Total Documents</div>
            </div>
        </div>

        <div class="stat-item-horizontal">
            <div class="stat-icon-horizontal">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-content-horizontal">
                <div class="stat-number-label"><strong>{{ $totalTasks }}</strong> Total Tasks</div>
            </div>
        </div>
    </div>

    <!-- Document Analytics -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-file-alt mr-2"></i>Document Analytics</h3>
            <span class="badge badge-success">{{ date('F Y') }}</span>
        </div>
        <div class="doc-analytics-grid">
            <div class="doc-analytics-row">
                <div class="doc-analytics-item">
                    <div class="doc-analytics-label">My Documents</div>
                    <div class="doc-analytics-value">{{ $docAnalytics['myDocs'] }}</div>
                </div>
                <div class="doc-analytics-item">
                    <div class="doc-analytics-label">Dept. Documents</div>
                    <div class="doc-analytics-value">{{ $docAnalytics['deptTotal'] }}</div>
                </div>
                <div class="doc-analytics-item">
                    <div class="doc-analytics-label">Uploaded This Month</div>
                    <div class="doc-analytics-value">{{ $docAnalytics['docsThisMonth'] }}</div>
                </div>
            </div>
            <div class="doc-analytics-row">
                <div class="doc-analytics-item">
                    <div class="doc-analytics-label">Top Document Type</div>
                    <div class="doc-analytics-value">{{ $docAnalytics['topDocType'] }} <span class="doc-analytics-sub">{{ $docAnalytics['topDocTypeCount'] > 0 ? '(' . $docAnalytics['topDocTypeCount'] . ' files)' : '' }}</span></div>
                </div>
                <div class="doc-analytics-item">
                    <div class="doc-analytics-label">Most Used Folder</div>
                    <div class="doc-analytics-value">{{ $docAnalytics['mostUsedFolder'] }} <span class="doc-analytics-sub">{{ $docAnalytics['mostUsedFolderCount'] > 0 ? '(' . $docAnalytics['mostUsedFolderCount'] . ' files)' : '' }}</span></div>
                </div>
                <div class="doc-analytics-item">
                    <div class="doc-analytics-label">Most Active Faculty</div>
                    <div class="doc-analytics-value">{{ $docAnalytics['topUploaderName'] }} <span class="doc-analytics-sub">{{ $docAnalytics['topUploaderCount'] > 0 ? '(' . $docAnalytics['topUploaderCount'] . ' uploads)' : '' }}</span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Exam & Certification Trends -->
    @include('partials.exam-trends')

    <!-- Announcements Feed Widget -->
    @include('partials.announcement-widget')

    <!-- Quick Actions -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Quick Actions</h3>
        </div>
        <div class="flex gap-4 flex-wrap">
            <a href="{{ route('coordinator.create-faculty') }}" class="btn btn-success">
                <i class="fas fa-user-plus"></i> Add Faculty Member
            </a>
        </div>
    </div>

    <!-- Recent Tasks -->
    <div class="content-card">
        <div class="card-header">
            <div class="flex justify-between items-center w-full">
                <h3 class="card-title">My Recent Tasks</h3>
                <div class="flex gap-3 items-center">
                    <a href="{{ route('coordinator.tasks') }}" class="badge badge-info no-underline cursor-pointer">View All</a>
                    <button type="button" onclick="toggleCoordinatorRecentTasks()" class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 text-sm font-medium cursor-pointer border-0">
                        <i id="coordRecentTasksIcon" class="fas fa-chevron-up"></i>
                        <span id="coordRecentTasksText">Hide</span>
                    </button>
                </div>
            </div>
        </div>
        <div id="coordRecentTasksContent" class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Task Title</th>
                        <th>Assigned By</th>
                        <th>Due Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentTasks as $task)
                    <tr>
                        <td><strong>{{ $task->task_title }}</strong></td>
                        <td>{{ $task->assignedBy->employee->full_name ?? $task->assignedBy->username }}</td>
                        <td>{{ $task->due_date ? $task->due_date->format('M d, Y') : 'N/A' }}</td>
                        <td>
                            @if($task->status === 'Completed')
                                <span class="badge badge-success">Completed</span>
                            @elseif($task->status === 'In Progress')
                                <span class="badge badge-warning">In Progress</span>
                            @else
                                <span class="badge badge-danger">Pending</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-gray-600 dark:text-gray-400">
                            No tasks assigned yet
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Faculty List -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Faculty Members</h3>
            <a href="{{ route('coordinator.faculty') }}" class="badge badge-info no-underline cursor-pointer">View All</a>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($facultyList as $faculty)
                <tr>
                    <td><strong>{{ $faculty->employee->full_name ?? 'N/A' }}</strong></td>
                    <td>{{ $faculty->email }}</td>
                    <td>{{ $faculty->employee->department ?? 'N/A' }}</td>
                    <td>
                        <span class="badge badge-success">{{ $faculty->status }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-gray-600 dark:text-gray-400">
                        No faculty members yet
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Recent Activities -->
    <div class="content-card">
        <div class="card-header">
            <div class="flex justify-between items-center w-full">
                <h3 class="card-title">Recent Activities</h3>
                <div class="flex gap-3 items-center">
                    <span class="badge badge-info">Last 10 Activities</span>
                    <button type="button" onclick="toggleCoordinatorRecentActivities()" class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 text-sm font-medium cursor-pointer border-0">
                        <i id="coordRecentActivitiesIcon" class="fas fa-chevron-up"></i>
                        <span id="coordRecentActivitiesText">Hide</span>
                    </button>
                </div>
            </div>
        </div>
        <div id="coordRecentActivitiesContent" class="overflow-x-auto">
            <table class="data-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Activity</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentActivities as $activity)
                <tr>
                    <td>
                        <strong>{{ $activity->user->employee->full_name ?? $activity->user->username }}</strong>
                        @if($activity->targetUser)
                            <i class="fas fa-arrow-right text-gray-600 dark:text-gray-400 mx-1"></i>
                            <span class="text-gray-600 dark:text-gray-400">{{ $activity->targetUser->employee->full_name ?? $activity->targetUser->username }}</span>
                        @endif
                    </td>
                    <td>
                        {{ $activity->activity }}
                        @if($activity->activity_type)
                            <span class="inline-block px-2 py-1 text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 ml-1">
                                {{ ucfirst(str_replace('_', ' ', $activity->activity_type)) }}
                            </span>
                        @endif
                    </td>
                    <td>{{ $activity->log_date->format('M d, Y h:i A') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="text-center text-gray-600 dark:text-gray-400">
                        No recent activities
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function toggleCoordinatorRecentTasks() {
    const content = document.getElementById('coordRecentTasksContent');
    const icon = document.getElementById('coordRecentTasksIcon');
    const text = document.getElementById('coordRecentTasksText');

    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
        text.textContent = 'Hide';
    } else {
        content.style.display = 'none';
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
        text.textContent = 'Show';
    }
}

function toggleCoordinatorRecentActivities() {
    const content = document.getElementById('coordRecentActivitiesContent');
    const icon = document.getElementById('coordRecentActivitiesIcon');
    const text = document.getElementById('coordRecentActivitiesText');

    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
        text.textContent = 'Hide';
    } else {
        content.style.display = 'none';
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
        text.textContent = 'Show';
    }
}
</script>
@endpush
