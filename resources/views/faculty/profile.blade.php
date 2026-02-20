@extends('layouts.dashboard')

@section('title', 'My Profile - Faculty')

@section('page-title', 'My Profile')
@section('page-subtitle', 'View your information and performance')

@section('sidebar')
    <a href="{{ route('faculty.dashboard') }}" class="menu-item">
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
    </a>
    <a href="{{ route('faculty.profile') }}" class="menu-item active">
        <i class="fas fa-user"></i> My Profile
    </a>
    <a href="{{ route('faculty.documents') }}" class="menu-item">
        <i class="fas fa-folder"></i> Documents
    </a>
@endsection

@section('content')
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Employee Information</h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 py-2.5">
            <div>
                <p class="text-gray-500 dark:text-gray-400 mb-1.5 text-sm">Employee Number</p>
                <p class="font-semibold text-base">{{ $employee->employee_no ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400 mb-1.5 text-sm">Full Name</p>
                <p class="font-semibold text-base">{{ $employee->full_name }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400 mb-1.5 text-sm">Department</p>
                <p class="font-semibold text-base">{{ $employee->department ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400 mb-1.5 text-sm">Position</p>
                <p class="font-semibold text-base">{{ $employee->position }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400 mb-1.5 text-sm">Hire Date</p>
                <p class="font-semibold text-base">{{ $employee->hire_date ? $employee->hire_date->format('M d, Y') : 'N/A' }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400 mb-1.5 text-sm">Email</p>
                <p class="font-semibold text-base">{{ auth()->user()->email }}</p>
            </div>
        </div>
    </div>

    @if($performanceReports->count() > 0)
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Performance History</h3>
            <span class="badge badge-info">{{ $performanceReports->count() }} Reviews</span>
        </div>
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
    </div>
    @else
    <div class="content-card">
        <div class="text-center py-10 text-gray-500 dark:text-gray-400">
            <i class="fas fa-chart-line text-5xl mb-4 opacity-50"></i>
            <p>No performance reviews yet</p>
        </div>
    </div>
    @endif
@endsection
