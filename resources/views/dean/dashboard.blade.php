@extends('layouts.dashboard')

@section('title', 'Dean Dashboard')

@section('page-title', 'Dean Dashboard')
@section('page-subtitle', 'Comprehensive overview of system analytics')

@section('sidebar')
    @include('partials.dean-sidebar')
@endsection

@section('content')
    {{-- Weekly Insight Briefing — auto-generated narrative analytics --}}
    @include('partials.insight-briefing')

    <!-- Minimalist Horizontal Stats -->
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

    @include('partials.dean-pending-review-cards')

    <!-- Document Analytics -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-file-alt mr-2"></i>Document Analytics</h3>
            <span class="badge badge-success">{{ date('F Y') }}</span>
        </div>
        <div class="doc-analytics-grid">
            <div class="doc-analytics-row">
                <div class="doc-analytics-item">
                    <div class="doc-analytics-label">Total Documents</div>
                    <div class="doc-analytics-value">{{ $docAnalytics['totalDocs'] }}</div>
                </div>
                <div class="doc-analytics-item">
                    <div class="doc-analytics-label">Uploaded This Month</div>
                    <div class="doc-analytics-value">{{ $docAnalytics['docsThisMonth'] }}</div>
                </div>
                <div class="doc-analytics-item">
                    <div class="doc-analytics-label">Total Folders</div>
                    <div class="doc-analytics-value">{{ $docAnalytics['totalFolders'] }}</div>
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
            <a href="{{ route('dean.create-task') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create New Task
            </a>
        </div>
    </div>

    <!-- Recent Tasks -->
    <div class="content-card">
        <div class="card-header">
            <div class="flex justify-between items-center w-full">
                <h3 class="card-title">Recent Tasks</h3>
                <a href="{{ route('dean.tasks') }}" class="badge badge-info no-underline cursor-pointer">View All</a>
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

    <!-- System Usage Analytics Chart -->
    <div class="bg-white dark:bg-[#2a2a2a] p-6 mb-6 border border-gray-200 dark:border-gray-700">
        <div class="flex justify-between items-center mb-5 pb-4 border-b-2 border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 m-0">
                <i class="fas fa-chart-bar mr-2"></i>System Usage Analytics ({{ date('Y') }})
            </h3>
            <span class="inline-block px-3 py-1 text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">Monthly Activity</span>
        </div>
        
        <!-- Bar Chart -->
        <div class="relative" style="height: 300px;">
            <canvas id="systemUsageChart"></canvas>
        </div>
    </div>

    <script>
        // Wait for Chart.js to be loaded via lazy loading
        function initChart() {
            if (typeof Chart === 'undefined') {
                setTimeout(initChart, 100);
                return;
            }

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
        }

        // Start initializing the chart
        initChart();
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

@endsection

@push('scripts')
<script>
</script>
@endpush
