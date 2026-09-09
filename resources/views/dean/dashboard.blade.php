@extends('layouts.dashboard')

@section('title', 'Dean Dashboard')

@section('page-title', 'Dean Dashboard')
@section('page-subtitle', 'Comprehensive overview of system analytics')

@section('sidebar')
    @include('partials.dean-sidebar')
@endsection

@section('content')
    {{-- Stat Chips --}}
    <div class="stats-grid-horizontal">
        <div class="stat-item-horizontal">
            <div class="stat-icon-horizontal">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content-horizontal">
                <div class="stat-number-label"><strong>{{ $totalEmployees }}</strong> Faculty Members</div>
            </div>
        </div>

        <div class="stat-item-horizontal">
            <div class="stat-icon-horizontal">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-content-horizontal">
                <div class="stat-number-label"><strong>{{ $docsThisSchoolYear }}</strong> Documents This Year</div>
            </div>
        </div>

        <div class="stat-item-horizontal">
            <div class="stat-icon-horizontal">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content-horizontal">
                <div class="stat-number-label"><strong>{{ $pendingApprovals }}</strong> Pending Approvals</div>
            </div>
        </div>

        <div class="stat-item-horizontal">
            <div class="stat-icon-horizontal">
                <i class="fas fa-spinner"></i>
            </div>
            <div class="stat-content-horizontal">
                <div class="stat-number-label"><strong>{{ $tasksInProgress }}</strong> Tasks In Progress</div>
            </div>
        </div>
    </div>

    {{-- Pending Reviews --}}
    @include('partials.dean-pending-review-cards')

    {{-- Recent Tasks (with Quick Action in header) --}}
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
