@extends('layouts.dashboard')

@section('title', 'Faculty Dashboard')

@section('page-title', 'Faculty Dashboard')
@section('page-subtitle', 'Your action queue and work overview')

@section('sidebar')
    @include('partials.faculty-sidebar')
@endsection

@section('content')
    @php
        $attentionCount = (int) ($attentionCount ?? 0);
        $openTasksCount = (int) ($openTasksCount ?? 0);
        $totalDocuments = (int) ($totalDocuments ?? 0);
        $completedTasks = (int) ($completedTasks ?? 0);
        $overdueCount = (int) ($overdueCount ?? 0);
        $unreadCount = (int) ($unreadCount ?? 0);
        $awaitingApproval = (int) ($awaitingApproval ?? 0);
        $bannerUrl = $bannerUrl ?? route('faculty.documents');
        $bannerCta = $bannerCta ?? 'Open documents';
        $uploadMax = max(1, (int) ($uploadMax ?? 1));
        $taskMax = max(1, (int) ($taskMax ?? 1));
    @endphp

    {{-- Status banner + hero metric --}}
    <a href="{{ $bannerUrl }}" class="dean-status-banner {{ $attentionCount > 0 ? 'dean-status-banner--attention' : 'dean-status-banner--clear' }} no-underline mb-3">
        <div class="dean-status-banner__status">
            <span class="dean-status-banner__eyebrow">My status</span>
            @if($attentionCount > 0)
                <span class="dean-status-banner__title">{{ $attentionCount }} {{ $attentionCount === 1 ? 'item needs' : 'items need' }} attention</span>
                <span class="dean-status-banner__hint">{{ $bannerCta }} · click to open</span>
            @else
                <span class="dean-status-banner__title">All clear</span>
                <span class="dean-status-banner__hint">No overdue tasks, unread alerts, or waiting submissions</span>
            @endif
        </div>
        <div class="dean-status-banner__hero">
            <span class="dean-status-banner__hero-label">Open tasks</span>
            <span class="dean-status-banner__hero-value">{{ $openTasksCount }}</span>
            <span class="dean-status-banner__meta">
                {{ $totalDocuments }} docs · {{ $completedTasks }} completed · {{ $overdueCount }} overdue · {{ $unreadCount }} unread
            </span>
        </div>
    </a>

    {{-- Quick pending cards --}}
    <div class="dean-pending-review-grid mb-3">
        <a href="{{ route('faculty.tasks', ['filter' => 'pending']) }}"
           class="content-card dean-pending-review-card dean-pending-review-card--tg no-underline">
            <div class="p-3 flex items-start gap-3">
                <div class="dean-pending-review-card__icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="card-title text-base mb-1">Open Tasks</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-0">Pending and in-progress work assigned to you.</p>
                </div>
                <span class="badge {{ $openTasksCount > 0 ? 'badge-warning' : 'badge-info' }} shrink-0">{{ $openTasksCount }}</span>
            </div>
        </a>

        <a href="{{ route('faculty.tasks', ['filter' => 'overdue']) }}"
           class="content-card dean-pending-review-card dean-pending-review-card--eq no-underline">
            <div class="p-3 flex items-start gap-3">
                <div class="dean-pending-review-card__icon">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="card-title text-base mb-1">Overdue Tasks</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-0">Past due — finish these first.</p>
                </div>
                <span class="badge {{ $overdueCount > 0 ? 'badge-danger' : 'badge-info' }} shrink-0">{{ $overdueCount }}</span>
            </div>
        </a>
    </div>

    @if($awaitingApproval > 0)
    <div class="dean-pending-review-grid mb-3">
        <a href="{{ route('faculty.teaching-guides.index') }}"
           class="content-card dean-pending-review-card dean-pending-review-card--tg no-underline">
            <div class="p-3 flex items-start gap-3">
                <div class="dean-pending-review-card__icon"><i class="fas fa-book-open"></i></div>
                <div class="min-w-0 flex-1">
                    <h3 class="card-title text-base mb-1">TG Awaiting Approval</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-0">Teaching guides still pending Dean review.</p>
                </div>
                <span class="badge badge-warning shrink-0">{{ $pendingTeachingGuidesCount }}</span>
            </div>
        </a>
        <a href="{{ route('faculty.exam-questionnaires.index') }}"
           class="content-card dean-pending-review-card dean-pending-review-card--eq no-underline">
            <div class="p-3 flex items-start gap-3">
                <div class="dean-pending-review-card__icon"><i class="fas fa-file-alt"></i></div>
                <div class="min-w-0 flex-1">
                    <h3 class="card-title text-base mb-1">EQ Awaiting Approval</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-0">Exam questionnaires still pending Dean review.</p>
                </div>
                <span class="badge badge-warning shrink-0">{{ $pendingExamQuestionnairesCount }}</span>
            </div>
        </a>
    </div>
    @endif

    {{-- Feature preview stacks --}}
    <div class="dean-preview-grid mb-3">
        <div class="dean-preview-col">
            <div class="content-card dean-preview-card">
                <div class="card-header">
                    <h3 class="card-title text-sm mb-0">
                        <i class="fas fa-exclamation-circle mr-2 text-[#028a0f]"></i>Needs attention
                    </h3>
                    <a href="{{ route('faculty.notifications') }}" class="text-xs font-semibold text-[#028a0f] dark:text-[#34d399] no-underline">Notifications</a>
                </div>
                <ul class="dean-preview-list">
                    @forelse($unreadNotifications as $n)
                        <li>
                            <span class="dean-preview-list__primary">{{ \Illuminate\Support\Str::limit($n->message, 72) }}</span>
                            <span class="dean-preview-list__meta">{{ $n->created_at?->diffForHumans() }}</span>
                        </li>
                    @empty
                        <li class="dean-preview-empty">No unread notifications</li>
                    @endforelse
                </ul>
                @if(isset($overdueTasks) && $overdueTasks->count() > 0)
                    <div class="dean-preview-subhead">Overdue tasks</div>
                    <ul class="dean-preview-list">
                        @foreach($overdueTasks as $task)
                            <li>
                                <span class="dean-preview-list__primary">{{ $task->task_title }}</span>
                                <span class="dean-preview-list__meta">Due {{ $task->due_date?->format('M d, Y') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="content-card dean-preview-card">
                <div class="card-header">
                    <h3 class="card-title text-sm mb-0">
                        <i class="fas fa-bullhorn mr-2 text-[#028a0f]"></i>Announcements
                    </h3>
                    <a href="{{ route('announcements.index') }}" class="text-xs font-semibold text-[#028a0f] dark:text-[#34d399] no-underline">View all</a>
                </div>
                <ul class="dean-preview-list">
                    @forelse($announcements as $announcement)
                        <li>
                            <span class="dean-preview-list__primary">
                                @if($announcement->is_pinned)<i class="fas fa-thumbtack text-[#028a0f] text-xs mr-1"></i>@endif
                                {{ $announcement->title }}
                            </span>
                            <span class="dean-preview-list__meta">{{ $announcement->created_at?->diffForHumans() }}</span>
                        </li>
                    @empty
                        <li class="dean-preview-empty">No announcements yet</li>
                    @endforelse
                </ul>
            </div>

            <div class="content-card dean-preview-card">
                <div class="card-header">
                    <h3 class="card-title text-sm mb-0">
                        <i class="fas fa-chart-bar mr-2 text-[#028a0f]"></i>Mini analytics
                    </h3>
                    <a href="{{ route('faculty.analytics') }}" class="text-xs font-semibold text-[#028a0f] dark:text-[#34d399] no-underline">Open Analytics</a>
                </div>
                <div class="dean-mini-analytics">
                    <p class="dean-mini-analytics__summary">
                        {{ (int) ($loginTotal ?? 0) }} {{ ((int) ($loginTotal ?? 0)) === 1 ? 'login' : 'logins' }}
                        · {{ (int) ($usageTotal ?? 0) }} {{ ((int) ($usageTotal ?? 0)) === 1 ? 'action' : 'actions' }}
                        · {{ (int) ($activeDays ?? 0) }}/7 active days
                    </p>
                    <div class="dean-mini-analytics__block">
                        <div class="dean-mini-analytics__label">Logins · last 7 days</div>
                        <div class="dean-mini-bars" aria-hidden="true">
                            @foreach(($loginBars ?? []) as $bar)
                                <div class="dean-mini-bar">
                                    <div class="dean-mini-bar__fill" style="height: {{ max(8, (int) round(($bar['count'] / max(1, (int) ($loginMax ?? 1))) * 100)) }}%"></div>
                                    <span class="dean-mini-bar__label">{{ $bar['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="dean-mini-analytics__block">
                        <div class="dean-mini-analytics__label">System usage · last 7 days</div>
                        <div class="dean-mini-bars" aria-hidden="true">
                            @foreach(($usageBars ?? []) as $bar)
                                <div class="dean-mini-bar">
                                    <div class="dean-mini-bar__fill" style="height: {{ max(8, (int) round(($bar['count'] / max(1, (int) ($usageMax ?? 1))) * 100)) }}%"></div>
                                    <span class="dean-mini-bar__label">{{ $bar['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                        <p class="dean-preview-empty mb-0 mt-2" style="font-size:0.72rem;">
                            Usage = logins, views, uploads, and other actions you made in DocuDrive.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="dean-preview-col">
            <div class="content-card dean-preview-card">
                <div class="card-header">
                    <h3 class="card-title text-sm mb-0">
                        <i class="fas fa-file-alt mr-2 text-[#028a0f]"></i>Recent documents
                    </h3>
                    <a href="{{ route('faculty.documents') }}" class="text-xs font-semibold text-[#028a0f] dark:text-[#34d399] no-underline">Documents</a>
                </div>
                <ul class="dean-preview-list">
                    @forelse($recentDocuments as $doc)
                        <li>
                            <a href="{{ route('faculty.view-document', $doc->document_id) }}" class="dean-preview-list__link no-underline">
                                <span class="dean-preview-list__primary">{{ $doc->document_title }}</span>
                                <span class="dean-preview-list__meta">
                                    {{ $doc->category ?: 'Documents' }} · {{ $doc->created_at?->diffForHumans() }}
                                </span>
                            </a>
                        </li>
                    @empty
                        <li class="dean-preview-empty">No recent documents</li>
                    @endforelse
                </ul>
            </div>

            <div class="content-card dean-preview-card">
                <div class="card-header">
                    <h3 class="card-title text-sm mb-0">
                        <i class="fas fa-calendar-alt mr-2 text-[#028a0f]"></i>Upcoming deadlines
                    </h3>
                    <a href="{{ route('faculty.tasks') }}" class="text-xs font-semibold text-[#028a0f] dark:text-[#34d399] no-underline">Tasks</a>
                </div>
                <ul class="dean-preview-list">
                    @forelse($upcomingDeadlines as $task)
                        @php
                            $daysLeft = now()->startOfDay()->diffInDays($task->due_date->copy()->startOfDay(), false);
                            $dueLabel = $daysLeft < 0
                                ? 'Overdue by '.abs($daysLeft).'d'
                                : ($daysLeft === 0 ? 'Due today' : 'Due in '.$daysLeft.'d');
                        @endphp
                        <li>
                            <span class="dean-preview-list__primary">{{ \Illuminate\Support\Str::limit($task->task_title, 40) }}</span>
                            <span class="dean-preview-list__meta">
                                {{ $task->assignedBy->employee->full_name ?? $task->assignedBy->username ?? 'Assigned' }}
                                · {{ $dueLabel }}
                            </span>
                        </li>
                    @empty
                        <li class="dean-preview-empty">No upcoming deadlines</li>
                    @endforelse
                </ul>
            </div>

            <div class="content-card dean-preview-card">
                <div class="card-header">
                    <h3 class="card-title text-sm mb-0">
                        <i class="fas fa-stream mr-2 text-[#028a0f]"></i>Activity pulse
                    </h3>
                    <a href="{{ route('faculty.activity-log') }}" class="text-xs font-semibold text-[#028a0f] dark:text-[#34d399] no-underline">Activity log</a>
                </div>
                <ul class="dean-preview-list">
                    @forelse($activityPulse as $log)
                        <li>
                            <span class="dean-preview-list__primary">{{ \Illuminate\Support\Str::limit($log->activity, 80) }}</span>
                            <span class="dean-preview-list__meta">{{ optional($log->log_date ?? $log->created_at)->diffForHumans() }}</span>
                        </li>
                    @empty
                        <li class="dean-preview-empty">No recent activity</li>
                    @endforelse
                </ul>
            </div>

            <div class="content-card dean-preview-card">
                <div class="card-header">
                    <h3 class="card-title text-sm mb-0">
                        <i class="fas fa-compass mr-2 text-[#028a0f]"></i>Quick links
                    </h3>
                </div>
                <div class="dean-quick-links">
                    <a href="{{ route('faculty.documents') }}" class="dean-quick-link no-underline"><i class="fas fa-folder"></i> Documents</a>
                    <a href="{{ route('faculty.teaching-guides.index') }}" class="dean-quick-link no-underline"><i class="fas fa-book-open"></i> Teaching Guides</a>
                    <a href="{{ route('faculty.exam-questionnaires.index') }}" class="dean-quick-link no-underline"><i class="fas fa-file-alt"></i> Exam Questionnaires</a>
                    <a href="{{ route('faculty.tasks') }}" class="dean-quick-link no-underline"><i class="fas fa-tasks"></i> My Tasks</a>
                    <a href="{{ route('faculty.recycle-bin.index') }}" class="dean-quick-link no-underline"><i class="fas fa-trash-alt"></i> Recycle Bin</a>
                    <a href="{{ route('faculty.archives.list') }}" class="dean-quick-link no-underline"><i class="fas fa-archive"></i> Archives</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Tasks --}}
    <div class="content-card">
        <div class="card-header">
            <div class="flex justify-between items-center w-full">
                <h3 class="card-title">My Recent Tasks</h3>
                <a href="{{ route('faculty.tasks') }}" class="badge badge-info no-underline cursor-pointer">View All</a>
            </div>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Task Title</th>
                    <th>Assigned By</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentTasks as $task)
                <tr>
                    <td><strong>{{ $task->task_title }}</strong></td>
                    <td>{{ $task->assignedBy->employee->full_name ?? $task->assignedBy->username ?? 'N/A' }}</td>
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
                    <td>
                        @if($task->status !== 'Completed')
                        <form action="{{ route('faculty.update-task-status', $task->task_id) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <select name="status" onchange="this.form.submit()" class="form-control text-xs py-1">
                                <option value="Pending" @selected($task->status === 'Pending')>Pending</option>
                                <option value="In Progress" @selected($task->status === 'In Progress')>In Progress</option>
                                <option value="Completed" @selected($task->status === 'Completed')>Completed</option>
                            </select>
                        </form>
                        @else
                            —
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-gray-600 dark:text-gray-400">No tasks assigned yet</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
