@extends('layouts.dashboard')

@section('title', 'Analytics - Dean')

@section('page-title', 'Data Analytics')
@section('page-subtitle', 'Platform engagement, submissions, and usage insights')

@section('sidebar')
    @include('partials.dean-sidebar')
@endsection

@section('content')
@php
    $taskTotal = ($taskStatusData ?? collect())->sum('count');
    $inactiveCount = ($inactiveUsers ?? collect())->count();
    $latestWeek = ($weeklyTrend ?? collect())->last();
    $taskMax = max($taskTotal ?: 1, 1);
@endphp

<div class="analytics-page">
    @if(auth()->user()->isDean())
    <section class="analytics-section content-card" aria-labelledby="monthly-history-heading">
        <div class="card-header flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 id="monthly-history-heading" class="card-title">Monthly history</h3>
                <p class="text-sm text-gray-500 mt-1">Restart this month's summary while retaining historical records and full analytics.</p>
            </div>
            <div class="flex flex-wrap gap-2">
            <form id="reset-month-form" method="POST" action="{{ route('dean.analytics.reset-this-month') }}">
                @csrf
                <button type="button" class="btn btn-secondary" @disabled(!($monthlyResetReady ?? false)) onclick="confirmResetMonth()">Reset this month's analytics</button>
            </form>
            @php $previousMonth = now()->subMonth()->startOfMonth(); @endphp
            @if(($monthlyHistoryReady ?? false) && !($monthlyHistory ?? collect())->contains(fn ($snapshot) => $snapshot->period_start->isSameMonth($previousMonth)))
            <form id="close-month-form" method="POST" action="{{ route('dean.analytics.close-month') }}">
                @csrf
                <button type="button" class="btn btn-primary" onclick="confirmCloseMonth(@js($previousMonth->format('F Y')))">Close {{ $previousMonth->format('F Y') }}</button>
            </form>
            @endif
            </div>
        </div>
        <div class="p-4">
            @unless($monthlyResetReady ?? false)<p class="text-sm text-amber-700">The analytics reset migration is pending.</p>@endunless
            <h4 class="font-semibold mb-2">{{ now()->format('F Y') }} — {{ ($currentMonthReset ?? null)?->reset_at?->isSameMonth(now()) ? 'since '.$currentMonthReset->reset_at->format('M j, g:i A') : 'month to date' }}</h4>
            <dl class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">
                @foreach($currentMonthMetrics ?? [] as $label => $value)
                    <div><dt class="text-sm text-gray-500">{{ $label }}</dt><dd class="font-semibold">{{ number_format($value) }}</dd></div>
                @endforeach
            </dl>
            @if(($monthlyResetHistory ?? collect())->isNotEmpty())
                <details class="border-b py-2"><summary class="cursor-pointer font-semibold">Earlier monthly resets</summary>
                    @foreach($monthlyResetHistory as $reset)
                    <p class="text-sm py-1">{{ $reset->reset_at->format('M j, Y g:i A') }} — {{ collect($reset->closed_metrics)->map(fn ($value, $label) => $label.': '.$value)->implode(' · ') }}</p>
                    @endforeach
                </details>
            @endif
            @forelse($monthlyHistory ?? [] as $snapshot)
                <details class="border-b py-2">
                    <summary class="cursor-pointer font-semibold">{{ $snapshot->period_start->format('F Y') }} <span class="text-sm font-normal text-gray-500">— closed {{ $snapshot->created_at->format('M j, Y') }}</span></summary>
                    <dl class="grid grid-cols-2 md:grid-cols-5 gap-3 mt-3">
                        @foreach($snapshot->metrics as $label => $value)
                            <div><dt class="text-sm text-gray-500">{{ $label }}</dt><dd class="font-semibold">{{ number_format($value) }}</dd></div>
                        @endforeach
                    </dl>
                </details>
            @empty
                <p class="text-sm text-gray-500">No closed months yet.</p>
            @endforelse
        </div>
    </section>
    @endif
    <p class="text-sm text-gray-500 mb-2">Live overview · closing a month preserves history and does not change these figures.</p>
    <div class="analytics-stats" aria-label="Key analytics">
        <div class="analytics-stat">
            <div class="analytics-stat__value">{{ number_format($totalSubmissions ?? 0) }}</div>
            <div class="analytics-stat__label">Selected school year submissions</div>
        </div>
        <div class="analytics-stat">
            <div class="analytics-stat__value">{{ $latestWeek['actions'] ?? 0 }}</div>
            <div class="analytics-stat__label">Latest week actions</div>
        </div>
        <div class="analytics-stat">
            <div class="analytics-stat__value">{{ $inactiveCount }}</div>
            <div class="analytics-stat__label">Currently inactive users</div>
        </div>
        <div class="analytics-stat">
            <div class="analytics-stat__value">{{ number_format($taskTotal) }}</div>
            <div class="analytics-stat__label">All-time tasks</div>
        </div>
    </div>

    <p class="sr-only">
        {{ number_format($totalSubmissions ?? 0) }} submissions in the selected school year.
        Latest recorded week had {{ $latestWeek['actions'] ?? 0 }} actions.
        {{ $inactiveCount }} users are inactive. {{ number_format($taskTotal) }} tasks are in the system.
    </p>

    @include('partials.engagement-analytics')

    @include('partials.submission-analytics')

    <section class="analytics-section content-card mb-0" aria-labelledby="task-status-heading">
        <div class="card-header">
            <h3 id="task-status-heading" class="card-title">Task status</h3>
        </div>
        <div class="p-4">
            @forelse($taskStatusData as $status)
                <div class="ui-meter">
                    <div class="ui-meter__row">
                        <span class="ui-meter__label">{{ $status->status }}</span>
                        <span class="ui-meter__value">{{ $status->count }} tasks</span>
                    </div>
                    <div class="ui-meter__track" aria-hidden="true">
                        <div class="ui-meter__fill" style="width: {{ ($status->count / $taskMax) * 100 }}%;"></div>
                    </div>
                </div>
            @empty
                @include('partials.ui.empty-state', ['title' => 'No task data', 'text' => 'No task data available.'])
            @endforelse
        </div>
    </section>
</div>
@push('scripts')
<script>
function confirmCloseMonth(label) {
    const form = document.getElementById('close-month-form');
    if (!form) return;
    const message = 'Save ' + label + ' figures to monthly history? Live analytics and records will not be reset.';
    if (typeof Swal === 'undefined') {
        if (confirm(message)) form.submit();
        return;
    }
    Swal.fire({title: 'Close ' + label + '?', text: message, icon: 'question', showCancelButton: true,
        confirmButtonText: 'Save monthly history', cancelButtonText: 'Cancel',
        confirmButtonColor: '#0d5c3b', customClass: {popup: 'swal-flat'}})
        .then(result => { if (result.isConfirmed) form.submit(); });
}
function confirmResetMonth() {
    const form = document.getElementById('reset-month-form');
    if (!form) return;
    const message = 'Restart this month’s summary from now? Earlier figures will be saved in history. Documents, tasks, and full analytics remain.';
    if (typeof Swal === 'undefined') { if (confirm(message)) form.submit(); return; }
    Swal.fire({title: 'Reset this month’s analytics?', text: message, icon: 'warning', showCancelButton: true,
        confirmButtonText: 'Reset monthly summary', cancelButtonText: 'Cancel', confirmButtonColor: '#0d5c3b',
        customClass: {popup: 'swal-flat'}}).then(result => { if (result.isConfirmed) form.submit(); });
}
</script>
@endpush
@endsection
