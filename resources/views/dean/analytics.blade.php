@extends('layouts.dashboard')

@section('title', 'Analytics - Dean')

@section('page-title', 'Data Analytics')
@section('page-subtitle', 'Comprehensive insights and trends')

@section('sidebar')
    <a href="{{ route('dean.dashboard') }}" class="menu-item">
        <i class="fas fa-chart-line"></i> Dashboard
    </a>
    <a href="{{ route('leave.index') }}" class="menu-item">
        <i class="fas fa-calendar-alt"></i> Leave Requests
    </a>
    <a href="{{ route('calendar.index') }}" class="menu-item">
        <i class="fas fa-calendar"></i> Calendar
    </a>
    <a href="{{ route('dean.employees') }}" class="menu-item">
        <i class="fas fa-users"></i> Employees
    </a>
    <a href="{{ route('dean.reports') }}" class="menu-item">
        <i class="fas fa-file-alt"></i> Performance Reports
    </a>
    <a href="{{ route('dean.analytics') }}" class="menu-item active">
        <i class="fas fa-chart-pie"></i> Analytics
    </a>
    <a href="{{ route('dean.documents') }}" class="menu-item">
        <i class="fas fa-folder"></i> Documents
    </a>
@endsection

@section('content')
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Task Status Distribution</h3>
        </div>
        <div class="py-2">
            @forelse($taskStatusData as $status)
                <div class="mb-5">
                    <div class="flex justify-between mb-2">
                        <span class="font-semibold text-sm text-gray-800 dark:text-gray-200">{{ $status->status }}</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $status->count }} tasks</span>
                    </div>
                    <div class="bg-gray-200 dark:bg-gray-700 h-2.5 rounded-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-[#4caf50] to-[#028a0f] h-full transition-all duration-500 rounded-lg" style="width: {{ ($status->count / $taskStatusData->sum('count')) * 100 }}%;"></div>
                    </div>
                </div>
            @empty
                <p class="text-center text-gray-500 dark:text-gray-400 py-8">No task data available</p>
            @endforelse
        </div>
    </div>

    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Department Distribution</h3>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Employee Count</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                @forelse($departmentData as $dept)
                <tr>
                    <td><strong>{{ $dept->department }}</strong></td>
                    <td>{{ $dept->count }}</td>
                    <td>
                        <span class="badge badge-info">
                            {{ number_format(($dept->count / $departmentData->sum('count')) * 100, 1) }}%
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="text-center text-gray-500 dark:text-gray-400">
                        No department data available
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Monthly Performance Trends</h3>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Average Rating</th>
                    <th>Total Reports</th>
                </tr>
            </thead>
            <tbody>
                @forelse($monthlyPerformance as $month)
                <tr>
                    <td><strong>{{ $month->month }}</strong></td>
                    <td>
                        <span class="badge {{ $month->avg_rating >= 4 ? 'badge-success' : ($month->avg_rating >= 3 ? 'badge-warning' : 'badge-danger') }}">
                            {{ number_format($month->avg_rating, 2) }}/5.0
                        </span>
                    </td>
                    <td>{{ $month->total_reports }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="text-center text-gray-500 dark:text-gray-400">
                        No performance data available
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
