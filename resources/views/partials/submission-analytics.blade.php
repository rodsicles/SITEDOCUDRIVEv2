@php
    $filters = $filters ?? [];
    $routeName = $analyticsRoute ?? 'dean.analytics';
    $maxMonthly = max($monthlyTrend->max('count') ?: 1, 1);
@endphp

<div class="content-card mb-6">
    <div class="card-header flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Submission Analytics</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $scopeLabel ?? '' }}</p>
        </div>
        <span class="badge badge-info">{{ number_format($totalSubmissions ?? 0) }} total submissions</span>
    </div>

    <form method="GET" action="{{ route($routeName) }}" class="submission-analytics-filters">
        @if(auth()->user()->isFaculty())
        <div class="submission-analytics-filters__grid submission-analytics-filters__grid--single">
            <div class="submission-analytics-filters__field">
                <label class="submission-analytics-filters__label" for="submission-school-year">
                    <i class="fas fa-calendar-alt"></i> School Year
                </label>
                <select id="submission-school-year" name="school_year" class="submission-analytics-filters__select" onchange="this.form.submit()">
                    @foreach($schoolYearOptions ?? [] as $value => $label)
                        <option value="{{ $value }}" @selected((string) ($filters['school_year'] ?? '') === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        @else
        <div class="submission-analytics-filters__grid">
            <div class="submission-analytics-filters__field">
                <label class="submission-analytics-filters__label" for="submission-school-year">
                    <i class="fas fa-calendar-alt"></i> School Year
                </label>
                <select id="submission-school-year" name="school_year" class="submission-analytics-filters__select">
                    @foreach($schoolYearOptions ?? [] as $value => $label)
                        <option value="{{ $value }}" @selected((string) ($filters['school_year'] ?? '') === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="submission-analytics-filters__field">
                <label class="submission-analytics-filters__label" for="submission-semester">
                    <i class="fas fa-book-open"></i> Semester
                </label>
                <select id="submission-semester" name="semester" class="submission-analytics-filters__select">
                    <option value="">All semesters</option>
                    <option value="1st" @selected(($filters['semester'] ?? '') === '1st')>1st Semester</option>
                    <option value="2nd" @selected(($filters['semester'] ?? '') === '2nd')>2nd Semester</option>
                </select>
            </div>
            @if(auth()->user()->isDean() || auth()->user()->isSecretary())
            <div class="submission-analytics-filters__field">
                <label class="submission-analytics-filters__label" for="submission-department">
                    <i class="fas fa-building"></i> Department
                </label>
                <select id="submission-department" name="department" class="submission-analytics-filters__select">
                    <option value="">All departments</option>
                    <option value="Information Technology" @selected(($filters['department'] ?? '') === 'Information Technology')>Information Technology</option>
                    <option value="Engineering" @selected(($filters['department'] ?? '') === 'Engineering')>Engineering</option>
                </select>
            </div>
            @elseif(auth()->user()->isProgramCoordinator())
            <div class="submission-analytics-filters__field">
                <label class="submission-analytics-filters__label">
                    <i class="fas fa-building"></i> Department
                </label>
                <input type="text" class="submission-analytics-filters__input submission-analytics-filters__input--readonly" value="{{ $filters['department'] ?? '—' }}" readonly>
            </div>
            @endif
            <div class="submission-analytics-filters__actions">
                <button type="submit" class="btn btn-primary submission-analytics-filters__btn">
                    <i class="fas fa-search"></i> Apply
                </button>
                <a href="{{ route($routeName) }}" class="btn btn-secondary submission-analytics-filters__btn">Reset</a>
            </div>
        </div>
        @endif
        @if(isset($activeSchoolYearStart) && (string) ($filters['school_year'] ?? '') === (string) $activeSchoolYearStart)
        <p class="submission-analytics-filters__hint">
            <i class="fas fa-check-circle text-[#028a0f]"></i>
            Showing data for the current school year.
        </p>
        @endif
    </form>

    <div class="p-4 grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div>
            <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-4">
                @if($showResponsivenessTable ?? true)
                    <i class="fas fa-tachometer-alt mr-1 text-blue-500"></i> Faculty Responsiveness
                @else
                    <i class="fas fa-user-check mr-1 text-emerald-500"></i> Your Responsiveness
                @endif
            </h4>
            @if($showResponsivenessTable ?? true)
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Faculty</th>
                            <th>Avg. Task Response</th>
                            <th>Announcement Read Rate</th>
                            <th>Submissions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($facultyResponsiveness as $row)
                        <tr>
                            <td>
                                <div>
                                    <strong>{{ $row['name'] }}</strong>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $row['department'] }}</div>
                                </div>
                            </td>
                            <td>
                                @if($row['avg_response_days'] !== null)
                                    @php
                                        $days = $row['avg_response_days'];
                                        $responseClass = $days <= 2 ? 'text-emerald-600' : ($days <= 5 ? 'text-amber-600' : 'text-red-600');
                                    @endphp
                                    <span class="font-semibold {{ $responseClass }}">{{ $days }} day{{ $days != 1 ? 's' : '' }}</span>
                                @else
                                    <span class="text-gray-400">No tasks</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $rate = $row['read_rate'];
                                    $rateClass = $rate >= 75 ? 'text-emerald-600' : ($rate >= 40 ? 'text-amber-600' : 'text-red-600');
                                @endphp
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-gray-200 dark:bg-gray-700 h-2 max-w-[80px]">
                                        <div class="h-full {{ $rate >= 75 ? 'bg-emerald-500' : ($rate >= 40 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ $rate }}%;"></div>
                                    </div>
                                    <span class="text-xs font-semibold {{ $rateClass }}">{{ $rate }}%</span>
                                </div>
                            </td>
                            <td>{{ $row['submissions'] }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-gray-500 dark:text-gray-400 py-6">No faculty data for the selected filters.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                @php $you = $facultyResponsiveness->first(); @endphp
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">Avg. Task Response</div>
                        <div class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                            @if($you && $you['avg_response_days'] !== null)
                                {{ $you['avg_response_days'] }} day{{ $you['avg_response_days'] != 1 ? 's' : '' }}
                            @else
                                —
                            @endif
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">Announcement Read Rate</div>
                        <div class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $you['read_rate'] ?? 0 }}%</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">Submissions</div>
                        <div class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $you['submissions'] ?? 0 }}</div>
                    </div>
                </div>
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
