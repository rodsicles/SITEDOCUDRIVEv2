@extends('layouts.dashboard')

@section('title', 'Employee Profile')

@section('page-title', 'Employee Profile')
@section('page-subtitle', 'Detailed employee information and history')

@section('sidebar')
    @if(auth()->user()->isDean())
        <a href="{{ route('dean.dashboard') }}" class="menu-item">
            <i class="fas fa-chart-line"></i> Dashboard
        </a>
        <a href="{{ route('dean.employees') }}" class="menu-item">
            <i class="fas fa-users"></i> Employees
        </a>
        <a href="{{ route('dean.reports') }}" class="menu-item">
            <i class="fas fa-file-alt"></i> Performance Reports
        </a>
        <a href="{{ route('dean.analytics') }}" class="menu-item">
            <i class="fas fa-chart-pie"></i> Analytics
        </a>
        <a href="{{ route('dean.documents') }}" class="menu-item">
            <i class="fas fa-folder"></i> Documents
        </a>
    @else
        <a href="{{ route('coordinator.dashboard') }}" class="menu-item">
            <i class="fas fa-chart-line"></i> Dashboard
        </a>
        <a href="{{ route('coordinator.tasks') }}" class="menu-item">
            <i class="fas fa-tasks"></i> Tasks
        </a>
        <a href="{{ route('coordinator.faculty') }}" class="menu-item">
            <i class="fas fa-users"></i> Faculty Members
        </a>
        <a href="{{ route('coordinator.documents') }}" class="menu-item">
            <i class="fas fa-folder"></i> Documents
        </a>
    @endif
@endsection

@section('content')
    <!-- Back Button -->
    <div class="mb-5">
        @if(auth()->user()->isDean())
            <a href="{{ route('dean.employees') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Employees
            </a>
        @else
            <a href="{{ route('coordinator.faculty') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Faculty
            </a>
        @endif
    </div>

    <!-- Employee Basic Information -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Basic Information</h3>
            <div class="flex gap-2.5 items-center">
                @if(auth()->user()->role_id === 2)
                    <a href="{{ route('coordinator.edit-faculty', $employee->employee_id) }}" class="btn btn-primary py-2 px-5 text-sm">
                        <i class="fas fa-edit"></i> Edit Information
                    </a>
                @endif
                <span class="badge {{ $employee->user->status === 'Active' ? 'badge-success' : 'badge-danger' }}">
                    {{ $employee->user->status }}
                </span>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 py-2.5">
            <div>
                <p class="text-gray-500 dark:text-gray-400 mb-1 text-sm">Employee Number</p>
                <p class="font-semibold text-base">{{ $employee->employee_no ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400 mb-1 text-sm">Full Name</p>
                <p class="font-semibold text-base">{{ $employee->full_name }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400 mb-1 text-sm">Department</p>
                <p class="font-semibold text-base">{{ $employee->department ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400 mb-1 text-sm">Position</p>
                <p class="font-semibold text-base">{{ $employee->position }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400 mb-1 text-sm">Role</p>
                <p class="font-semibold text-base">
                    <span class="badge badge-info">{{ $employee->user->role->role_name }}</span>
                </p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400 mb-1 text-sm">Hire Date</p>
                <p class="font-semibold text-base">{{ $employee->hire_date ? $employee->hire_date->format('M d, Y') : 'N/A' }}</p>
            </div>
        </div>
    </div>

    <!-- Account Information -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Account Information</h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 py-2.5">
            <div>
                <p class="text-gray-500 dark:text-gray-400 mb-1 text-sm">Username</p>
                <p class="font-semibold text-base">{{ $employee->user->username }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400 mb-1 text-sm">Email</p>
                <p class="font-semibold text-base">{{ $employee->user->email }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400 mb-1 text-sm">Account Created</p>
                <p class="font-semibold text-base">{{ $employee->user->created_at->format('M d, Y h:i A') }}</p>
            </div>
        </div>
    </div>

    @if(auth()->user()->isDean())
    <!-- Performance Reports -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Performance History</h3>
            <span class="badge badge-info">{{ $performanceReports->count() }} Reviews</span>
        </div>
        @if($performanceReports->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Evaluator</th>
                        <th>Rating</th>
                        <th>Remarks</th>
                        <th>Review Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($performanceReports as $report)
                    <tr>
                        <td><strong>{{ $report->evaluator->employee->full_name ?? $report->evaluator->username }}</strong></td>
                        <td>
                            <span class="badge {{ $report->rating >= 4 ? 'badge-success' : ($report->rating >= 3 ? 'badge-warning' : 'badge-danger') }}">
                                {{ $report->rating }}/5
                            </span>
                        </td>
                        <td>{{ $report->remarks ?? 'No remarks provided' }}</td>
                        <td>{{ $report->report_date->format('M d, Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="text-center py-10 text-gray-500 dark:text-gray-400">
                <i class="fas fa-chart-line text-5xl mb-4 opacity-50"></i>
                <p>No performance reviews yet</p>
            </div>
        @endif
    </div>
    @endif

    <!-- Submitted Documents -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Submitted Documents</h3>
            <span class="badge badge-info">{{ $documentStats['total'] }} Documents</span>
        </div>
        
        @if($documentStats['total'] > 0)
            <!-- Document Stats by Type -->
            <div class="flex flex-wrap gap-2.5 mb-5 p-2.5 bg-gray-100 dark:bg-gray-800 rounded-lg">
                @foreach($documentStats['byType'] as $type => $count)
                    <div class="py-2 px-4 bg-[#028a0f] text-white rounded-full text-sm">
                        <i class="fas fa-file-alt"></i> {{ $type ?? 'Other' }}: <strong>{{ $count }}</strong>
                    </div>
                @endforeach
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Document Title</th>
                        <th>Type</th>
                        <th>File Name</th>
                        <th>Upload Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($documents as $document)
                    <tr>
                        <td><strong>{{ $document->document_title }}</strong></td>
                        <td>
                            <span class="badge badge-info">
                                {{ $document->document_type ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="font-mono text-xs">
                            {{ basename($document->file_path) }}
                        </td>
                        <td>{{ $document->created_at->format('M d, Y h:i A') }}</td>
                        <td>
                            <a href="{{ asset($document->file_path) }}" target="_blank" class="btn btn-primary py-1 px-2.5 text-xs mr-1">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="{{ asset($document->file_path) }}" download class="btn btn-success py-1 px-2.5 text-xs">
                                <i class="fas fa-download"></i> Download
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="text-center py-10 text-gray-500 dark:text-gray-400">
                <i class="fas fa-folder-open text-5xl mb-4 opacity-50"></i>
                <p>No documents submitted yet</p>
            </div>
        @endif
    </div>

    <!-- Submitted Reports -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Submitted Reports</h3>
            <span class="badge badge-info">{{ $reportStats['total'] ?? 0 }} Reports</span>
        </div>
        
        @if(isset($reports) && $reports->count() > 0)
            <!-- Report Stats by Category -->
            <div class="flex flex-wrap gap-2.5 mb-5 p-2.5 bg-gray-100 dark:bg-gray-800 rounded-lg">
                @foreach($reportStats['byCategory'] as $category => $count)
                    <div class="py-2 px-4 bg-[#028a0f] text-white rounded-full text-sm">
                        <i class="fas fa-file-pdf"></i> {{ $category ?? 'Other' }}: <strong>{{ $count }}</strong>
                    </div>
                @endforeach
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Report Title</th>
                        <th>Category</th>
                        <th>File Name</th>
                        <th>Submission Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reports as $report)
                    <tr>
                        <td><strong>{{ $report->report_title }}</strong></td>
                        <td>
                            <span class="badge badge-warning">
                                {{ $report->report_category ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="font-mono text-xs">
                            {{ basename($report->file_path) }}
                        </td>
                        <td>{{ $report->created_at->format('M d, Y h:i A') }}</td>
                        <td>
                            <a href="{{ asset($report->file_path) }}" target="_blank" class="btn btn-primary py-1 px-2.5 text-xs mr-1">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="{{ asset($report->file_path) }}" download class="btn btn-success py-1 px-2.5 text-xs">
                                <i class="fas fa-download"></i> Download
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="text-center py-10 text-gray-500 dark:text-gray-400">
                <i class="fas fa-file-pdf text-5xl mb-4 opacity-50"></i>
                <p>No reports submitted yet</p>
            </div>
        @endif
    </div>

    <!-- Task History -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Task History</h3>
            <span class="badge badge-info">{{ $tasks->count() }} Tasks</span>
        </div>
        @if($tasks->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Task Title</th>
                        <th>Assigned By</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Created Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tasks as $task)
                    <tr>
                        <td><strong>{{ $task->task_title }}</strong></td>
                        <td>{{ $task->assignedBy->employee->full_name ?? $task->assignedBy->username }}</td>
                        <td>
                            {{ $task->due_date ? $task->due_date->format('M d, Y') : 'N/A' }}
                            @if($task->due_date && $task->due_date->isPast() && $task->status !== 'Completed')
                                <span class="badge badge-danger">Overdue</span>
                            @endif
                        </td>
                        <td>
                            @if($task->status === 'Completed')
                                <span class="badge badge-success">Completed</span>
                            @elseif($task->status === 'In Progress')
                                <span class="badge badge-warning">In Progress</span>
                            @else
                                <span class="badge badge-danger">Pending</span>
                            @endif
                        </td>
                        <td>{{ $task->created_at->format('M d, Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="text-center py-10 text-gray-500 dark:text-gray-400">
                <i class="fas fa-tasks text-5xl mb-4 opacity-50"></i>
                <p>No tasks assigned yet</p>
            </div>
        @endif
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-value">{{ $taskStats['total'] }}</div>
            <div class="stat-label">Total Tasks</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-value">{{ $taskStats['completed'] }}</div>
            <div class="stat-label">Completed Tasks</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-value">{{ $taskStats['pending'] }}</div>
            <div class="stat-label">Pending Tasks</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-folder"></i>
            </div>
            <div class="stat-value">{{ $documentStats['total'] }}</div>
            <div class="stat-label">Documents Submitted</div>
        </div>

        @if(auth()->user()->isDean() && $performanceReports->count() > 0)
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-value">{{ number_format($performanceReports->avg('rating'), 1) }}</div>
            <div class="stat-label">Average Rating</div>
        </div>
        @endif
    </div>
@endsection
