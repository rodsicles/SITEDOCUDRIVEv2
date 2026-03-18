@extends('layouts.dashboard')

@section('title', 'Faculty Dashboard')

@section('page-title', 'Data Analytics Dashboard')
@section('page-subtitle', 'Track your performance metrics and activities')

@section('sidebar')
    @include('partials.faculty-sidebar')
@endsection

@section('content')
    <!-- Minimalist Horizontal Stats -->
    <div class="stats-grid-horizontal">
        <div class="stat-item-horizontal">
            <div class="stat-icon-horizontal">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-content-horizontal">
                <div class="stat-number-label"><strong>{{ $totalDocuments }}</strong> Total Documents</div>
                <div class="stat-description">Submitted by you</div>
            </div>
        </div>

        <div class="stat-item-horizontal">
            <div class="stat-icon-horizontal">
                <i class="fas fa-calendar-times"></i>
            </div>
            <div class="stat-content-horizontal">
                <div class="stat-number-label"><strong>{{ $leaveThisMonth }}</strong> Total Leave</div>
                <div class="stat-description">This month | {{ $leaveThisYear }} this year</div>
            </div>
        </div>

        <div class="stat-item-horizontal">
            <div class="stat-icon-horizontal">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content-horizontal">
                <div class="stat-number-label"><strong>{{ $completedTasks }}</strong> Task Completed</div>
                <div class="stat-description">All time</div>
            </div>
        </div>
    </div>

    <!-- Announcements Feed Widget -->
    @include('partials.announcement-widget')

    <!-- Recent Tasks -->
    <div class="bg-white dark:bg-[#2a2a2a] p-6 mb-6 border border-gray-200 dark:border-gray-700">
        <div class="flex justify-between items-center mb-5 pb-4 border-b-2 border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 m-0">My Recent Tasks</h3>
            <a href="{{ route('faculty.tasks') }}" class="px-5 py-2 bg-[#028a0f] dark:bg-[#02b815] text-white text-sm font-medium hover:bg-[#026a0c] dark:hover:bg-[#028a0f] no-underline inline-block">View All Tasks</a>
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
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-3 py-4 border-b border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 text-sm"><strong>{{ $task->task_title }}</strong></td>
                        <td class="px-3 py-4 border-b border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 text-sm">{{ $task->assignedBy->employee->full_name ?? $task->assignedBy->username }}</td>
                        <td class="px-3 py-4 border-b border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 text-sm">{{ $task->due_date ? $task->due_date->format('M d, Y') : 'N/A' }}</td>
                        <td class="px-3 py-4 border-b border-gray-200 dark:border-gray-700 text-sm">
                            @if($task->status === 'Completed')
                                <span class="inline-block px-3 py-1 text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">Completed</span>
                            @elseif($task->status === 'In Progress')
                                <span class="inline-block px-3 py-1 text-xs font-semibold bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300">In Progress</span>
                            @else
                                <span class="inline-block px-3 py-1 text-xs font-semibold bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">Pending</span>
                            @endif
                        </td>
                        <td class="px-3 py-4 border-b border-gray-200 dark:border-gray-700 text-sm">
                            @if($task->status !== 'Completed')
                            <form action="{{ route('faculty.update-task-status', $task->task_id) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <select name="status" onchange="this.form.submit()" class="px-2 py-1 border border-gray-200 dark:border-gray-700 text-sm bg-white dark:bg-[#1e1e1e] text-gray-800 dark:text-gray-200 cursor-pointer hover:border-[#028a0f] dark:hover:border-[#02b815] focus:outline-none focus:border-[#028a0f] dark:focus:border-[#02b815] focus:shadow-[0_0_0_3px_rgba(2,138,15,0.1)]">
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

    <!-- Recent Activities / Notifications -->
    <div class="bg-white dark:bg-[#2a2a2a] p-6 mb-6 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex justify-between items-center mb-5 pb-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 m-0">My Recent Activities</h3>
            <span class="inline-block px-3 py-1 text-xs font-semibold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">Last 10 Activities</span>
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
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-3 py-4 border-b border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 text-sm">
                            {{ $activity->activity }}
                            @if($activity->activity_type)
                                <span class="inline-block px-3 py-1 text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 ml-1.5">
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
