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
    <div class="analytics-stats" aria-label="Key analytics">
        <div class="analytics-stat">
            <div class="analytics-stat__value">{{ number_format($totalSubmissions ?? 0) }}</div>
            <div class="analytics-stat__label">Submissions</div>
        </div>
        <div class="analytics-stat">
            <div class="analytics-stat__value">{{ $latestWeek['actions'] ?? 0 }}</div>
            <div class="analytics-stat__label">Latest week actions</div>
        </div>
        <div class="analytics-stat">
            <div class="analytics-stat__value">{{ $inactiveCount }}</div>
            <div class="analytics-stat__label">Inactive users</div>
        </div>
        <div class="analytics-stat">
            <div class="analytics-stat__value">{{ number_format($taskTotal) }}</div>
            <div class="analytics-stat__label">Tasks</div>
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
@endsection
