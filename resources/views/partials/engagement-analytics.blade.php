@php
    $eFilters = $engagementFilters ?? [];
    $isFaculty = auth()->user()->isFaculty();
    $isDeanOrSec = auth()->user()->isDean() || auth()->user()->isSecretary();
    $maxWeekly = max(($weeklyTrend ?? collect())->max('actions') ?: 1, 1);
@endphp

<div class="content-card mb-6">
    <div class="card-header flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>Engagement & Activity Analytics</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $engagementScopeLabel ?? '' }}</p>
        </div>
    </div>

    @if($isFaculty)
    {{-- Faculty: personal stats vs department average --}}
    <div class="p-4">
        <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-4">
            <i class="fas fa-user-check mr-1 text-emerald-500"></i> Your Activity vs Department Average
        </h4>
        @php
            $my = $myEngagementStats ?? [];
            $avg = $departmentAvgStats ?? [];
            $metrics = [
                ['label' => 'Total Actions', 'key' => 'total_actions', 'icon' => 'fas fa-bolt'],
                ['label' => 'Logins', 'key' => 'logins', 'icon' => 'fas fa-sign-in-alt'],
                ['label' => 'Uploads', 'key' => 'uploads', 'icon' => 'fas fa-upload'],
                ['label' => 'Document Views', 'key' => 'document_views', 'icon' => 'fas fa-eye'],
                ['label' => 'Notifications Read', 'key' => 'notifications_read', 'icon' => 'fas fa-bell'],
                ['label' => 'Read Rate', 'key' => 'read_rate', 'icon' => 'fas fa-percentage'],
            ];
        @endphp
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
            @foreach($metrics as $m)
            @php
                $myVal = $my[$m['key']] ?? 0;
                $avgVal = $avg[$m['key']] ?? 0;
                $suffix = $m['key'] === 'read_rate' ? '%' : '';
                $better = $m['key'] === 'read_rate' ? ($myVal >= $avgVal) : ($myVal >= $avgVal);
            @endphp
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div class="flex items-center gap-2 mb-2">
                    <i class="{{ $m['icon'] }} text-[#028a0f] text-sm"></i>
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $m['label'] }}</span>
                </div>
                <div class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                    {{ number_format($myVal) }}{{ $suffix }}
                </div>
                <div class="mt-1 text-xs {{ $better ? 'text-emerald-600' : 'text-amber-600' }}">
                    @if($better)
                        <i class="fas fa-arrow-up"></i>
                    @else
                        <i class="fas fa-arrow-down"></i>
                    @endif
                    Dept avg: {{ number_format($avgVal) }}{{ $suffix }}
                </div>
            </div>
            @endforeach
        </div>

        @if(($weeklyTrend ?? collect())->isNotEmpty())
        <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-3">
            <i class="fas fa-chart-bar mr-1"></i> Your Weekly Activity
        </h4>
        <div class="space-y-2">
            @foreach($weeklyTrend as $point)
            <div>
                <div class="flex justify-between mb-1 text-sm">
                    <span class="text-gray-700 dark:text-gray-300">{{ $point['week'] }}</span>
                    <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $point['actions'] }} actions</span>
                </div>
                <div class="bg-gray-200 dark:bg-gray-700 h-2.5 rounded-full">
                    <div class="bg-gradient-to-r from-[#4caf50] to-[#028a0f] h-full rounded-full" style="width: {{ ($point['actions'] / $maxWeekly) * 100 }}%;"></div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-center text-gray-500 dark:text-gray-400 py-6">No activity data for this period.</p>
        @endif
    </div>

    @else
    {{-- Dean / Secretary / Coordinator: leaderboards + inactive + trends --}}
    <div class="p-4 grid grid-cols-1 xl:grid-cols-2 gap-6">
        {{-- Faculty Activity Leaderboard --}}
        <div>
            <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-4">
                <i class="fas fa-trophy mr-1 text-amber-500"></i> Faculty Activity Leaderboard
            </h4>
            @if(($facultyLeaderboard ?? collect())->isNotEmpty())
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Faculty</th>
                        <th>Department</th>
                        <th>Actions</th>
                        <th>Logins</th>
                        <th>Uploads</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($facultyLeaderboard as $row)
                    <tr>
                        <td>
                            @if($row['rank'] <= 3)
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-white text-xs font-bold
                                    {{ $row['rank'] === 1 ? 'bg-amber-500' : ($row['rank'] === 2 ? 'bg-gray-400' : 'bg-amber-700') }}">
                                    {{ $row['rank'] }}
                                </span>
                            @else
                                {{ $row['rank'] }}
                            @endif
                        </td>
                        <td><strong>{{ $row['name'] }}</strong></td>
                        <td>{{ $row['department'] }}</td>
                        <td>{{ number_format($row['actions']) }}</td>
                        <td>{{ $row['logins'] }}</td>
                        <td>{{ $row['uploads'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <p class="text-center text-gray-500 dark:text-gray-400 py-6">No faculty activity for this period.</p>
            @endif
        </div>

        {{-- Coordinator Activity Leaderboard --}}
        <div>
            <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-4">
                <i class="fas fa-user-tie mr-1 text-blue-500"></i> Coordinator Activity Leaderboard
            </h4>
            @if(($coordinatorLeaderboard ?? collect())->isNotEmpty())
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Coordinator</th>
                        <th>Department</th>
                        <th>Actions</th>
                        <th>Logins</th>
                        <th>Uploads</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($coordinatorLeaderboard as $row)
                    <tr>
                        <td>
                            @if($row['rank'] <= 3)
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-white text-xs font-bold
                                    {{ $row['rank'] === 1 ? 'bg-amber-500' : ($row['rank'] === 2 ? 'bg-gray-400' : 'bg-amber-700') }}">
                                    {{ $row['rank'] }}
                                </span>
                            @else
                                {{ $row['rank'] }}
                            @endif
                        </td>
                        <td><strong>{{ $row['name'] }}</strong></td>
                        <td>{{ $row['department'] }}</td>
                        <td>{{ number_format($row['actions']) }}</td>
                        <td>{{ $row['logins'] }}</td>
                        <td>{{ $row['uploads'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <p class="text-center text-gray-500 dark:text-gray-400 py-6">No coordinator activity for this period.</p>
            @endif
        </div>
    </div>

    {{-- Weekly Activity Trend + Activity Breakdown --}}
    <div class="p-4 pt-0 grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div>
            <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-4">
                <i class="fas fa-chart-area mr-1 text-[#028a0f]"></i> Weekly Activity Trend
            </h4>
            @if(($weeklyTrend ?? collect())->isNotEmpty())
            <div class="space-y-2">
                @foreach($weeklyTrend as $point)
                <div>
                    <div class="flex justify-between mb-1 text-sm">
                        <span class="text-gray-700 dark:text-gray-300">{{ $point['week'] }}</span>
                        <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $point['actions'] }} actions · {{ $point['active_users'] }} users</span>
                    </div>
                    <div class="bg-gray-200 dark:bg-gray-700 h-2.5 rounded-full">
                        <div class="bg-gradient-to-r from-[#4caf50] to-[#028a0f] h-full rounded-full" style="width: {{ ($point['actions'] / $maxWeekly) * 100 }}%;"></div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-center text-gray-500 dark:text-gray-400 py-6">No weekly data for this period.</p>
            @endif
        </div>

        <div>
            <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-4">
                <i class="fas fa-chart-pie mr-1 text-indigo-500"></i> Activity Breakdown
            </h4>
            @php $maxBreakdown = max(($activityBreakdown ?? collect())->max('count') ?: 1, 1); @endphp
            @if(($activityBreakdown ?? collect())->isNotEmpty())
            <div class="space-y-3">
                @foreach($activityBreakdown as $item)
                <div>
                    <div class="flex justify-between mb-1 text-sm">
                        <span class="text-gray-700 dark:text-gray-300">{{ $item['type'] }}</span>
                        <span class="font-semibold text-gray-800 dark:text-gray-200">{{ number_format($item['count']) }}</span>
                    </div>
                    <div class="bg-gray-200 dark:bg-gray-700 h-2 rounded-full">
                        <div class="bg-gradient-to-r from-indigo-400 to-indigo-600 h-full rounded-full" style="width: {{ ($item['count'] / $maxBreakdown) * 100 }}%;"></div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-center text-gray-500 dark:text-gray-400 py-6">No activity data for this period.</p>
            @endif
        </div>
    </div>

    {{-- Inactive Users --}}
    @if(($inactiveUsers ?? collect())->isNotEmpty())
    <div class="p-4 pt-0">
        <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-4">
            <i class="fas fa-user-clock mr-1 text-red-500"></i> Inactive Users
            <span class="badge badge-danger ml-2">{{ $inactiveUsers->count() }}</span>
        </h4>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Last Activity</th>
                </tr>
            </thead>
            <tbody>
                @foreach($inactiveUsers->take(10) as $user)
                <tr>
                    <td><strong>{{ $user['name'] }}</strong></td>
                    <td>{{ $user['department'] }}</td>
                    <td>
                        @if($user['last_activity'])
                            <span class="text-amber-600">{{ \Carbon\Carbon::parse($user['last_activity'])->diffForHumans() }}</span>
                        @else
                            <span class="text-red-500">Never</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    @endif
</div>
