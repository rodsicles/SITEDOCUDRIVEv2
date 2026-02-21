@extends('layouts.dashboard')

@section('title', 'Faculty Dashboard')

@section('page-title', 'Data Analytics Dashboard')
@section('page-subtitle', 'Track your performance metrics and activities')

@section('sidebar')
    <a href="{{ route('faculty.dashboard') }}" class="menu-item active">
        <i class="fas fa-chart-line"></i> Dashboard
    </a>
    <a href="{{ route('faculty.tasks') }}" class="menu-item">
        <i class="fas fa-tasks"></i> My Tasks
    </a>
    <a href="{{ route('leave.index') }}" class="menu-item">
        <i class="fas fa-calendar-alt"></i> Leave Requests
    </a>
    <a href="{{ route('calendar.index') }}" class="menu-item">
        <i class="fas fa-calendar"></i> Calendar
    </a>
    <a href="{{ route('faculty.notifications') }}" class="menu-item">
        <i class="fas fa-bell"></i> Notifications
        @if($unreadNotifications > 0)
        <span class="badge badge-danger ml-auto">{{ $unreadNotifications }}</span>
        @endif
    </a>
    <a href="{{ route('faculty.profile') }}" class="menu-item">
        <i class="fas fa-user"></i> My Profile
    </a>
    <a href="{{ route('faculty.documents') }}" class="menu-item">
        <i class="fas fa-folder"></i> Documents
    </a>
@endsection

@section('content')
    <!-- Stats Grid - 3 Metrics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-value">{{ $totalDocuments }}</div>
            <div class="stat-label">Total Documents</div>
            <small style="display: block; font-size: 0.75rem; margin-top: 0.25rem; color: #6b7280;">Submitted by you</small>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-times"></i>
            </div>
            <div class="stat-value">{{ $leaveThisMonth }}</div>
            <div class="stat-label">Total Leave</div>
            <small style="display: block; font-size: 0.75rem; margin-top: 0.25rem; color: #6b7280;">This month | {{ $leaveThisYear }} this year</small>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-value">{{ $completedTasks }}</div>
            <div class="stat-label">Task Completed</div>
            <small style="display: block; font-size: 0.75rem; margin-top: 0.25rem; color: #6b7280;">All time</small>
        </div>
    </div>

    <!-- Recent Tasks -->
    <div class="bg-white dark:bg-[#2a2a2a] rounded-xl p-6 mb-6 shadow-md border border-gray-200 dark:border-gray-700 animate-[fadeIn_0.5s_ease]">
        <div class="flex justify-between items-center mb-5 pb-4 border-b-2 border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 m-0">My Recent Tasks</h3>
            <a href="{{ route('faculty.tasks') }}" class="px-5 py-2 bg-[#028a0f] dark:bg-[#02b815] text-white rounded-lg text-sm font-medium transition-all hover:bg-[#026a0c] dark:hover:bg-[#028a0f] hover:-translate-y-0.5 hover:shadow-md no-underline inline-block">View All Tasks</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-separate border-spacing-0">
                <thead>
                    <tr>
                        <th class="bg-transparent text-gray-600 dark:text-gray-400 font-semibold text-xs uppercase tracking-wide px-3 py-3 text-left border-b border-gray-200 dark:border-gray-700">Task Title</th>
                        <th class="bg-transparent text-gray-600 dark:text-gray-400 font-semibold text-xs uppercase tracking-wide px-3 py-3 text-left border-b border-gray-200 dark:border-gray-700">Assigned By</th>
                        <th class="bg-transparent text-gray-600 dark:text-gray-400 font-semibold text-xs uppercase tracking-wide px-3 py-3 text-left border-b border-gray-200 dark:border-gray-700">Due Date</th>
                        <th class="bg-transparent text-gray-600 dark:text-gray-400 font-semibold text-xs uppercase tracking-wide px-3 py-3 text-left border-b border-gray-200 dark:border-gray-700">Status</th>
                        <th class="bg-transparent text-gray-600 dark:text-gray-400 font-semibold text-xs uppercase tracking-wide px-3 py-3 text-left border-b border-gray-200 dark:border-gray-700">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentTasks as $task)
                    <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-3 py-4 border-b border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 text-sm"><strong>{{ $task->task_title }}</strong></td>
                        <td class="px-3 py-4 border-b border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 text-sm">{{ $task->assignedBy->employee->full_name ?? $task->assignedBy->username }}</td>
                        <td class="px-3 py-4 border-b border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 text-sm">{{ $task->due_date ? $task->due_date->format('M d, Y') : 'N/A' }}</td>
                        <td class="px-3 py-4 border-b border-gray-200 dark:border-gray-700 text-sm">
                            @if($task->status === 'Completed')
                                <span class="inline-block px-3 py-1 text-xs font-semibold rounded-md bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">Completed</span>
                            @elseif($task->status === 'In Progress')
                                <span class="inline-block px-3 py-1 text-xs font-semibold rounded-md bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300">In Progress</span>
                            @else
                                <span class="inline-block px-3 py-1 text-xs font-semibold rounded-md bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">Pending</span>
                            @endif
                        </td>
                        <td class="px-3 py-4 border-b border-gray-200 dark:border-gray-700 text-sm">
                            @if($task->status !== 'Completed')
                            <form action="{{ route('faculty.update-task-status', $task->task_id) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <select name="status" onchange="this.form.submit()" class="px-2 py-1 border border-gray-200 dark:border-gray-700 rounded-md text-sm bg-white dark:bg-[#1e1e1e] text-gray-800 dark:text-gray-200 cursor-pointer transition-all hover:border-[#028a0f] dark:hover:border-[#02b815] focus:outline-none focus:border-[#028a0f] dark:focus:border-[#02b815] focus:shadow-[0_0_0_3px_rgba(2,138,15,0.1)]">
                                    <option value="Pending" {{ $task->status === 'Pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="In Progress" {{ $task->status === 'In Progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="Completed" {{ $task->status === 'Completed' ? 'selected' : '' }}>Completed</option>
                                </select>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-3 py-8 text-center text-gray-600 dark:text-gray-400">
                            No tasks assigned yet
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Notifications -->
    <div class="bg-white dark:bg-[#2a2a2a] rounded-xl p-6 mb-6 shadow-md border border-gray-200 dark:border-gray-700 animate-[fadeIn_0.5s_ease]">
        <div class="flex justify-between items-center mb-5 pb-4 border-b-2 border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 m-0">Recent Notifications</h3>
            <a href="{{ route('faculty.notifications') }}" class="px-5 py-2 bg-[#028a0f] dark:bg-[#02b815] text-white rounded-lg text-sm font-medium transition-all hover:bg-[#026a0c] dark:hover:bg-[#028a0f] hover:-translate-y-0.5 hover:shadow-md no-underline inline-block">View All</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-separate border-spacing-0">
                <thead>
                    <tr>
                        <th class="bg-transparent text-gray-600 dark:text-gray-400 font-semibold text-xs uppercase tracking-wide px-3 py-3 text-left border-b border-gray-200 dark:border-gray-700">Message</th>
                        <th class="bg-transparent text-gray-600 dark:text-gray-400 font-semibold text-xs uppercase tracking-wide px-3 py-3 text-left border-b border-gray-200 dark:border-gray-700">Date</th>
                        <th class="bg-transparent text-gray-600 dark:text-gray-400 font-semibold text-xs uppercase tracking-wide px-3 py-3 text-left border-b border-gray-200 dark:border-gray-700">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentNotifications as $notification)
                    <tr class="{{ !$notification->is_read ? 'bg-green-50 dark:bg-green-900/20 font-semibold' : 'hover:bg-gray-50 dark:hover:bg-gray-800' }} transition-colors">
                        <td class="px-3 py-4 border-b border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 text-sm">{{ $notification->message }}</td>
                        <td class="px-3 py-4 border-b border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 text-sm">{{ $notification->created_at->format('M d, Y h:i A') }}</td>
                        <td class="px-3 py-4 border-b border-gray-200 dark:border-gray-700 text-sm">
                            @if($notification->is_read)
                                <span class="inline-block px-3 py-1 text-xs font-semibold rounded-md bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">Read</span>
                            @else
                                <span class="inline-block px-3 py-1 text-xs font-semibold rounded-md bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300">Unread</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-3 py-8 text-center text-gray-600 dark:text-gray-400">
                            No notifications
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Performance Reports -->
    @if($performanceReports->count() > 0)
    <div class="bg-white dark:bg-[#2a2a2a] rounded-xl p-6 mb-6 shadow-md border border-gray-200 dark:border-gray-700 animate-[fadeIn_0.5s_ease]">
        <div class="mb-5 pb-4 border-b-2 border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semiboldtext-gray-800 dark:text-gray-200 m-0">Recent Performance Reviews</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-separate border-spacing-0">
                <thead>
                    <tr>
                        <th class="bg-transparent text-gray-600 dark:text-gray-400 font-semibold text-xs uppercase tracking-wide px-3 py-3 text-left border-b border-gray-200 dark:border-gray-700">Evaluator</th>
                        <th class="bg-transparent text-gray-600 dark:text-gray-400 font-semibold text-xs uppercase tracking-wide px-3 py-3 text-left border-b border-gray-200 dark:border-gray-700">Rating</th>
                        <th class="bg-transparent text-gray-600 dark:text-gray-400 font-semibold text-xs uppercase tracking-wide px-3 py-3 text-left border-b border-gray-200 dark:border-gray-700">Remarks</th>
                        <th class="bg-transparent text-gray-600 dark:text-gray-400 font-semibold text-xs uppercase tracking-wide px-3 py-3 text-left border-b border-gray-200 dark:border-gray-700">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($performanceReports as $report)
                    <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-3 py-4 border-b border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 text-sm">{{ $report->evaluator->employee->full_name ?? $report->evaluator->username }}</td>
                        <td class="px-3 py-4 border-b border-gray-200 dark:border-gray-700 text-sm">
                            <span class="inline-block px-3 py-1 text-xs font-semibold rounded-md {{ $report->rating >= 4 ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : ($report->rating >= 3 ? 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300' : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300') }}">
                                {{ $report->rating }}/5
                            </span>
                        </td>
                        <td class="px-3 py-4 border-b border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 text-sm">{{ $report->remarks ?? 'No remarks' }}</td>
                        <td class="px-3 py-4 border-b border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 text-sm">{{ $report->report_date->format('M d, Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Recent Activities / Notifications -->
    <div class="bg-white dark:bg-[#2a2a2a] rounded-xl p-6 mb-6 shadow-md border border-gray-200 dark:border-gray-700 animate-[fadeIn_0.5s_ease]">
        <div class="flex justify-between items-center mb-5 pb-4 border-b-2 border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 m-0">My Recent Activities</h3>
            <span class="inline-block px-3 py-1 text-xs font-semibold rounded-md bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">Last 10 Activities</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-separate border-spacing-0">
                <thead>
                    <tr>
                        <th class="bg-transparent text-gray-600 dark:text-gray-400 font-semibold text-xs uppercase tracking-wide px-3 py-3 text-left border-b border-gray-200 dark:border-gray-700">Activity</th>
                        <th class="bg-transparent text-gray-600 dark:text-gray-400 font-semibold text-xs uppercase tracking-wide px-3 py-3 text-left border-b border-gray-200 dark:border-gray-700">Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentActivities as $activity)
                    <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-3 py-4 border-b border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 text-sm">
                            {{ $activity->activity }}
                            @if($activity->activity_type)
                                <span class="inline-block px-3 py-1 text-xs font-semibold rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 ml-1.5">
                                    {{ ucfirst(str_replace('_', ' ', $activity->activity_type)) }}
                                </span>
                            @endif
                            @if($activity->user_id !== auth()->id() && $activity->user)
                                <br><small class="text-gray-600 dark:text-gray-400">
                                    <i class="fas fa-info-circle"></i> By {{ $activity->user->employee->full_name ?? $activity->user->username }}
                                </small>
                            @endif
                        </td>
                        <td class="px-3 py-4 border-b border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 text-sm">{{ $activity->log_date->format('M d, Y h:i A') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" class="px-3 py-8 text-center text-gray-600 dark:text-gray-400">
                            No recent activities
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
