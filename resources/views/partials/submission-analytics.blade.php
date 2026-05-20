@php
    $filters = $filters ?? [];
    $routeName = $analyticsRoute ?? 'dean.analytics';
    $maxMonthly = max($monthlyTrend->max('count') ?: 1, 1);
    $maxTop = max($topFaculty->max('count') ?: 1, 1);
@endphp

<div class="content-card mb-6">
    <div class="card-header flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Submission Analytics</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $scopeLabel ?? '' }}</p>
        </div>
        <span class="badge badge-info">{{ number_format($totalSubmissions ?? 0) }} total submissions</span>
    </div>

    <form method="GET" action="{{ route($routeName) }}" class="p-4 border-b border-gray-200 dark:border-gray-700">
        @if(auth()->user()->isFaculty())
        <div class="max-w-xs">
            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">School Year</label>
            <select name="school_year" class="form-select w-full" onchange="this.form.submit()">
                @foreach($schoolYearOptions ?? [] as $value => $label)
                    <option value="{{ $value }}" @selected((string) ($filters['school_year'] ?? '') === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @else
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">School Year</label>
                <select name="school_year" class="form-select w-full">
                    @foreach($schoolYearOptions ?? [] as $value => $label)
                        <option value="{{ $value }}" @selected((string) ($filters['school_year'] ?? '') === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">Semester</label>
                <select name="semester" class="form-select w-full">
                    <option value="">All semesters</option>
                    <option value="1st" @selected(($filters['semester'] ?? '') === '1st')>1st Semester</option>
                    <option value="2nd" @selected(($filters['semester'] ?? '') === '2nd')>2nd Semester</option>
                </select>
            </div>
            @if(auth()->user()->isDean() || auth()->user()->isSecretary())
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">Department</label>
                <select name="department" class="form-select w-full">
                    <option value="">All departments</option>
                    <option value="Information Technology" @selected(($filters['department'] ?? '') === 'Information Technology')>Information Technology</option>
                    <option value="Engineering" @selected(($filters['department'] ?? '') === 'Engineering')>Engineering</option>
                </select>
            </div>
            @elseif(auth()->user()->isProgramCoordinator())
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">Department</label>
                <input type="text" class="form-input w-full bg-gray-100 dark:bg-gray-800" value="{{ $filters['department'] ?? '—' }}" readonly>
            </div>
            @endif
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary flex-1">
                    <i class="fas fa-search mr-1"></i> Apply
                </button>
                <a href="{{ route($routeName) }}" class="btn btn-secondary">Reset</a>
            </div>
        </div>
        @endif
    </form>

    <div class="p-4 grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div>
            <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-4">
                @if($showTopFacultyTable ?? true)
                    <i class="fas fa-trophy mr-1 text-amber-500"></i> Top Faculty Submitters
                @else
                    <i class="fas fa-user-check mr-1 text-emerald-500"></i> Your Submissions
                @endif
            </h4>
            @if($showTopFacultyTable ?? true)
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Faculty</th>
                            <th>Department</th>
                            <th>Submissions</th>
                            <th>Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topFaculty as $row)
                        <tr>
                            <td>{{ $row['rank'] }}</td>
                            <td><strong>{{ $row['name'] }}</strong></td>
                            <td>{{ $row['department'] }}</td>
                            <td>{{ $row['count'] }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-gray-200 dark:bg-gray-700 h-2">
                                        <div class="bg-gradient-to-r from-[#4caf50] to-[#028a0f] h-full" style="width: {{ ($row['count'] / $maxTop) * 100 }}%;"></div>
                                    </div>
                                    <span class="text-xs text-gray-500">{{ $row['percent'] }}%</span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-gray-500 dark:text-gray-400 py-6">No submissions for the selected filters.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                @php $you = $topFaculty->first() ?? ['count' => 0, 'department' => auth()->user()->employee?->department ?? '—']; @endphp
                <div class="doc-analytics-grid">
                    <div class="doc-analytics-row">
                        <div class="doc-analytics-item">
                            <div class="doc-analytics-label">Total Submissions</div>
                            <div class="doc-analytics-value">{{ $you['count'] ?? 0 }}</div>
                        </div>
                        <div class="doc-analytics-item">
                            <div class="doc-analytics-label">Department</div>
                            <div class="doc-analytics-value">{{ $you['department'] ?? '—' }}</div>
                        </div>
                    </div>
                </div>
                @if(empty($you['count']))
                    <p class="text-center text-gray-500 dark:text-gray-400 py-4">No submissions for the selected filters.</p>
                @endif
            @endif
        </div>

        <div>
            <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-4">
                <i class="fas fa-chart-bar mr-1"></i> Monthly Submission Trend
            </h4>
            @if($monthlyTrend->isNotEmpty())
                <div class="space-y-3">
                    @foreach($monthlyTrend as $point)
                    <div>
                        <div class="flex justify-between mb-1 text-sm">
                            <span class="text-gray-700 dark:text-gray-300">{{ $point['label'] }}</span>
                            <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $point['count'] }}</span>
                        </div>
                        <div class="bg-gray-200 dark:bg-gray-700 h-2.5">
                            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-full" style="width: {{ ($point['count'] / $maxMonthly) * 100 }}%;"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <p class="text-center text-gray-500 dark:text-gray-400 py-8">No monthly data for the selected filters.</p>
            @endif
        </div>
    </div>

    @if(auth()->user()->isFaculty() && $monthlyTrend->isNotEmpty())
    <div class="p-4 pt-0 border-t border-gray-200 dark:border-gray-700">
        <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-3">
            <i class="fas fa-chart-line mr-1 text-[#028a0f]"></i> Submission overview
        </h4>
        <div class="submission-overview-chart" role="img" aria-label="Monthly submission bar chart">
            @foreach($monthlyTrend as $point)
            <div class="submission-overview-chart__bar-col">
                <div class="submission-overview-chart__bar" style="height: {{ max(8, ($point['count'] / $maxMonthly) * 100) }}%;" title="{{ $point['label'] }}: {{ $point['count'] }}"></div>
                <span class="submission-overview-chart__label">{{ $point['label'] }}</span>
                <span class="submission-overview-chart__value">{{ $point['count'] }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
