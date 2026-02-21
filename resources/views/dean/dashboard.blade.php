@extends('layouts.dashboard')

@section('title', 'Dean Dashboard')

@section('page-title', 'Data Analytics Dashboard')
@section('page-subtitle', 'Comprehensive overview of system analytics')

@section('sidebar')
    <a href="{{ route('dean.dashboard') }}" class="menu-item active">
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
    <a href="{{ route('dean.analytics') }}" class="menu-item">
        <i class="fas fa-chart-pie"></i> Analytics
    </a>
    <a href="{{ route('dean.documents') }}" class="menu-item">
        <i class="fas fa-folder"></i> Documents
    </a>
@endsection

@section('content')
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-value">{{ $totalEmployees }}</div>
            <div class="stat-label">Total Employees</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-value">{{ $totalDocuments }}</div>
            <div class="stat-label">Total Documents</div>
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
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-value">{{ $totalTasks }}</div>
            <div class="stat-label">Total Tasks</div>
        </div>
    </div>

    <!-- System Usage Analytics Chart -->
    <div class="bg-white dark:bg-[#2a2a2a] rounded-xl p-6 mb-6 shadow-md border border-gray-200 dark:border-gray-700 animate-[fadeIn_0.5s_ease]">
        <div class="flex justify-between items-center mb-5 pb-4 border-b-2 border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 m-0">
                <i class="fas fa-chart-bar mr-2"></i>System Usage Analytics ({{ date('Y') }})
            </h3>
            <span class="inline-block px-3 py-1 text-xs font-semibold rounded-md bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">Monthly Activity</span>
        </div>
        
        <!-- Bar Chart -->
        <div class="relative" style="height: 300px;">
            <canvas id="systemUsageChart"></canvas>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        const ctx = document.getElementById('systemUsageChart').getContext('2d');
        const monthlyData = @json(array_values($monthlyUsage));
        const monthLabels = @json($monthNames);
        
        // Calculate total for percentage
        const totalActivities = monthlyData.reduce((sum, val) => sum + val, 0);
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'System Activities',
                    data: monthlyData,
                    backgroundColor: 'rgba(2, 138, 15, 0.65)',
                    borderColor: '#028a0f',
                    borderWidth: 2,
                    borderRadius: 8,
                    hoverBackgroundColor: '#028a0f'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#374151',
                            font: {
                                size: 12,
                                weight: '600'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#028a0f',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed.y;
                                const percentage = totalActivities > 0 ? ((value / totalActivities) * 100).toFixed(1) : 0;
                                return [
                                    'Activities: ' + value,
                                    'Percentage: ' + percentage + '%'
                                ];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280',
                            font: {
                                size: 11
                            },
                            stepSize: 1
                        },
                        grid: {
                            color: document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb',
                            drawBorder: false
                        }
                    },
                    x: {
                        ticks: {
                            color: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280',
                            font: {
                                size: 11,
                                weight: '600'
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>

    <!-- Top Performers -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Top Performers</h3>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Employee Name</th>
                    <th>Department</th>
                    <th>Average Rating</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topPerformers as $performer)
                <tr>
                    <td><strong>{{ $performer->employee->full_name }}</strong></td>
                    <td>{{ $performer->employee->department ?? 'N/A' }}</td>
                    <td>
                        <span class="badge badge-success">
                            {{ number_format($performer->avg_rating, 1) }}/5.0
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-success">Active</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-gray-500 dark:text-gray-400">
                        No performance data available yet
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Recent Activities -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Recent Activities</h3>
            <span class="badge badge-info">Last 10 Activities</span>
        </div>
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
                        <strong>{{ $activity->user->employee->full_name ?? $activity->user->username ?? 'System' }}</strong>
                        @if($activity->targetUser)
                            <i class="fas fa-arrow-right text-gray-500 dark:text-gray-400 mx-1.5"></i>
                            <span class="text-gray-500 dark:text-gray-400">{{ $activity->targetUser->employee->full_name ?? $activity->targetUser->username }}</span>
                        @endif
                    </td>
                    <td>
                        {{ $activity->activity }}
                        @if($activity->activity_type)
                            <span class="badge badge-neutral text-[0.7rem] ml-1.5">
                                {{ ucfirst(str_replace('_', ' ', $activity->activity_type)) }}
                            </span>
                        @endif
                    </td>
                    <td>{{ $activity->log_date->format('M d, Y h:i A') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="text-center text-gray-500 dark:text-gray-400">
                        No recent activities
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
