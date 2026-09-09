@extends('layouts.dashboard')

@section('title', 'Dean Dashboard')

@section('page-title', 'Dean Dashboard')
@section('page-subtitle', 'Action queue and system overview')

@section('sidebar')
    @include('partials.dean-sidebar')
@endsection

@section('content')
    @php
        $attentionCount = (int) ($attentionCount ?? 0);
        $pendingApprovals = (int) ($pendingApprovals ?? 0);
        $totalEmployees = (int) ($totalEmployees ?? 0);
        $docsThisSchoolYear = (int) ($docsThisSchoolYear ?? 0);
        $tasksInProgress = (int) ($tasksInProgress ?? 0);
        $bannerUrl = $bannerUrl ?? route('dean.notifications');
        $bannerCta = $bannerCta ?? 'Open notifications';
        $uploadMax = max(1, (int) ($uploadMax ?? 1));
        $taskMax = max(1, (int) ($taskMax ?? 1));
    @endphp

    {{-- Status banner + hero metric --}}
    <a href="{{ $bannerUrl }}" class="dean-status-banner {{ $attentionCount > 0 ? 'dean-status-banner--attention' : 'dean-status-banner--clear' }} no-underline mb-3">
        <div class="dean-status-banner__status">
            <span class="dean-status-banner__eyebrow">System status</span>
            @if($attentionCount > 0)
                <span class="dean-status-banner__title">{{ $attentionCount }} {{ $attentionCount === 1 ? 'item needs' : 'items need' }} review</span>
                <span class="dean-status-banner__hint">{{ $bannerCta }} · click to open the queue</span>
            @else
                <span class="dean-status-banner__title">All clear</span>
                <span class="dean-status-banner__hint">No pending approvals or password resets</span>
            @endif
        </div>
        <div class="dean-status-banner__hero">
            <span class="dean-status-banner__hero-label">Pending approvals</span>
            <span class="dean-status-banner__hero-value">{{ $pendingApprovals }}</span>
            <span class="dean-status-banner__meta">
                {{ $totalEmployees }} faculty · {{ $docsThisSchoolYear }} docs this year · {{ $tasksInProgress }} tasks in progress
            </span>
        </div>
    </a>

    {{-- Pending Reviews --}}
    @include('partials.dean-pending-review-cards')

    {{-- Feature preview: two independent stacks (avoids row-gap holes) --}}
    <div class="dean-preview-grid mb-3">
        <div class="dean-preview-col">
            {{-- Needs attention --}}
            <div class="content-card dean-preview-card">
                <div class="card-header">
                    <h3 class="card-title text-sm mb-0">
                        <i class="fas fa-exclamation-circle mr-2 text-[#028a0f]"></i>Needs attention
                    </h3>
                    <a href="{{ route('dean.notifications') }}" class="text-xs font-semibold text-[#028a0f] dark:text-[#34d399] no-underline">Notifications</a>
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
                @if(($passwordResetCount ?? 0) > 0)
                    <a href="{{ route('password-reset-requests.index', ['tab' => 'pending']) }}"
                       class="dean-preview-alert no-underline">
                        {{ $passwordResetCount }} password reset {{ $passwordResetCount === 1 ? 'request' : 'requests' }} pending
                    </a>
                @endif
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

            {{-- Announcements --}}
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

            {{-- Mini analytics --}}
            <div class="content-card dean-preview-card">
                <div class="card-header">
                    <h3 class="card-title text-sm mb-0">
                        <i class="fas fa-chart-bar mr-2 text-[#028a0f]"></i>Mini analytics
                    </h3>
                    <a href="{{ route('dean.analytics') }}" class="text-xs font-semibold text-[#028a0f] dark:text-[#34d399] no-underline">Open Analytics</a>
                </div>
                <div class="dean-mini-analytics">
                    <div class="dean-mini-analytics__block">
                        <div class="dean-mini-analytics__label">Uploads · last 7 days</div>
                        <div class="dean-mini-bars" aria-hidden="true">
                            @foreach($uploadBars as $bar)
                                <div class="dean-mini-bar">
                                    <div class="dean-mini-bar__fill" style="height: {{ max(8, (int) round(($bar['count'] / $uploadMax) * 100)) }}%"></div>
                                    <span class="dean-mini-bar__label">{{ $bar['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="dean-mini-analytics__block">
                        <div class="dean-mini-analytics__label">Tasks by status</div>
                        @forelse($taskStatusBars as $bar)
                            <div class="dean-mini-status-row">
                                <span>{{ $bar['label'] }}</span>
                                <div class="dean-mini-status-track">
                                    <div class="dean-mini-status-fill" style="width: {{ max(4, (int) round(($bar['count'] / $taskMax) * 100)) }}%"></div>
                                </div>
                                <strong>{{ $bar['count'] }}</strong>
                            </div>
                        @empty
                            <p class="dean-preview-empty mb-0">No task data yet</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="dean-preview-col">
            {{-- Recent documents --}}
            <div class="content-card dean-preview-card">
                <div class="card-header">
                    <h3 class="card-title text-sm mb-0">
                        <i class="fas fa-file-alt mr-2 text-[#028a0f]"></i>Recent documents
                    </h3>
                    <a href="{{ route('dean.documents') }}" class="text-xs font-semibold text-[#028a0f] dark:text-[#34d399] no-underline">Documents</a>
                </div>
                <ul class="dean-preview-list">
                    @forelse($recentDocuments as $doc)
                        <li>
                            <a href="{{ route('dean.view-document', $doc->document_id) }}" class="dean-preview-list__link no-underline">
                                <span class="dean-preview-list__primary">{{ $doc->document_title }}</span>
                                <span class="dean-preview-list__meta">
                                    {{ $doc->uploader?->employee?->full_name ?? $doc->uploader?->username ?? 'Unknown' }}
                                    · {{ $doc->category ?: 'Documents' }}
                                    · {{ $doc->created_at?->diffForHumans() }}
                                </span>
                            </a>
                        </li>
                    @empty
                        <li class="dean-preview-empty">No recent documents</li>
                    @endforelse
                </ul>
            </div>

            {{-- Activity pulse --}}
            <div class="content-card dean-preview-card">
                <div class="card-header">
                    <h3 class="card-title text-sm mb-0">
                        <i class="fas fa-stream mr-2 text-[#028a0f]"></i>Activity pulse
                    </h3>
                    <a href="{{ route('dean.audit-trail') }}" class="text-xs font-semibold text-[#028a0f] dark:text-[#34d399] no-underline">Audit trail</a>
                </div>
                <ul class="dean-preview-list">
                    @forelse($activityPulse as $log)
                        <li>
                            <span class="dean-preview-list__primary">{{ \Illuminate\Support\Str::limit($log->activity, 80) }}</span>
                            <span class="dean-preview-list__meta">
                                {{ $log->user?->employee?->full_name ?? $log->user?->username ?? 'System' }}
                                · {{ optional($log->log_date ?? $log->created_at)->diffForHumans() }}
                            </span>
                        </li>
                    @empty
                        <li class="dean-preview-empty">No recent activity</li>
                    @endforelse
                </ul>
            </div>

            {{-- Quick links --}}
            <div class="content-card dean-preview-card">
                <div class="card-header">
                    <h3 class="card-title text-sm mb-0">
                        <i class="fas fa-compass mr-2 text-[#028a0f]"></i>Quick links
                    </h3>
                </div>
                <div class="dean-quick-links">
                    <a href="{{ route('dean.employees') }}" class="dean-quick-link no-underline"><i class="fas fa-users"></i> Employees</a>
                    <a href="{{ route('dean.courses') }}" class="dean-quick-link no-underline"><i class="fas fa-graduation-cap"></i> Courses</a>
                    <a href="{{ route('dean.archives.index') }}" class="dean-quick-link no-underline"><i class="fas fa-archive"></i> Archives</a>
                    <a href="{{ route('dean.recycle-bin.index') }}" class="dean-quick-link no-underline"><i class="fas fa-trash-alt"></i> Recycle Bin</a>
                    <a href="{{ route('dean.backup') }}" class="dean-quick-link no-underline"><i class="fas fa-database"></i> Backup</a>
                    <a href="{{ route('password-reset-requests.index') }}" class="dean-quick-link no-underline"><i class="fas fa-key"></i> Password resets</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Tasks --}}
    <div class="content-card">
        <div class="card-header">
            <div class="flex justify-between items-center w-full">
                <h3 class="card-title">Recent Tasks</h3>
                <div class="flex items-center gap-3">
                    <a href="{{ route('dean.create-task') }}" class="btn btn-primary text-sm">
                        <i class="fas fa-plus"></i> New Task
                    </a>
                    <a href="{{ route('dean.tasks') }}" class="badge badge-info no-underline cursor-pointer">View All</a>
                </div>
            </div>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Task Title</th>
                    <th>Assigned To</th>
                    <th>Due Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentTasks as $task)
                <tr>
                    <td><strong>{{ $task->task_title }}</strong></td>
                    <td>{{ $task->assignedTo->employee->full_name ?? 'N/A' }}</td>
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
                        No tasks created yet
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
