@extends('layouts.dashboard')

@section('title', 'Activity Log')
@section('page-title', 'Activity Log')
@section('page-subtitle', 'A complete record of actions performed in the system')

@section('sidebar')
    @if(auth()->user()->isFaculty())
        @include('partials.faculty-sidebar')
    @elseif(auth()->user()->isProgramCoordinator())
        @include('partials.coordinator-sidebar')
    @elseif(auth()->user()->isSecretary())
        @include('partials.secretary-sidebar')
    @else
        @include('partials.dean-sidebar')
    @endif
@endsection

@section('content')
@php
    $filters = $filters ?? [];
    $activityTypes = $activityTypes ?? collect();
    $hasFilters = filled($filters['q'] ?? null) || filled($filters['activity_type'] ?? null);
    $isFaculty = auth()->user()->isFaculty();
@endphp

<div class="content-card activity-log-page">
    <div class="card-header">
        <h3 class="card-title">Activity Log</h3>
        <span class="badge badge-info">{{ $activities->total() }} records</span>
    </div>

    <form method="GET" action="{{ url()->current() }}" class="activity-log-toolbar">
        <label class="sr-only" for="activity-log-search">Search activity</label>
        <input type="search"
               id="activity-log-search"
               name="q"
               value="{{ $filters['q'] ?? '' }}"
               class="form-control activity-log-toolbar__search"
               placeholder="Search activity"
               maxlength="100"
               autocomplete="off">

        <label class="sr-only" for="activity-log-type">Activity type</label>
        <select id="activity-log-type" name="activity_type" class="form-control activity-log-toolbar__type">
            <option value="">All types</option>
            @foreach($activityTypes as $type)
                <option value="{{ $type }}" @selected(($filters['activity_type'] ?? '') === $type)>
                    {{ ucfirst(str_replace('_', ' ', $type)) }}
                </option>
            @endforeach
        </select>

        <button type="submit" class="btn btn-primary">Filter</button>
        @if($hasFilters)
            <a href="{{ url()->current() }}" class="btn btn-secondary">Clear</a>
        @endif
    </form>

    <div class="overflow-x-auto">
        <table class="data-table compact activity-log-table {{ $isFaculty ? 'activity-log-table--faculty' : 'activity-log-table--staff' }}">
            <colgroup>
                @unless($isFaculty)<col class="activity-log-col-user">@endunless
                <col class="activity-log-col-activity">
                <col class="activity-log-col-type">
                <col class="activity-log-col-date">
            </colgroup>
            <thead>
                <tr>
                    @unless($isFaculty)
                    <th>User</th>
                    @endunless
                    <th>Activity</th>
                    <th>Type</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activities as $activity)
                <tr>
                    @unless($isFaculty)
                    <td>
                        <strong>{{ $activity->user->employee->full_name ?? $activity->user->username ?? 'System' }}</strong>
                        @if($activity->targetUser)
                            <i class="fas fa-arrow-right text-gray-400 dark:text-gray-500 mx-1"></i>
                            <span class="text-gray-500 dark:text-gray-400 text-sm">{{ $activity->targetUser->employee->full_name ?? $activity->targetUser->username }}</span>
                        @endif
                    </td>
                    @endunless
                    <td class="activity-log-table__activity">
                        {{ $activity->activity }}
                        @if($isFaculty && $activity->user_id !== auth()->id() && $activity->user)
                            <span class="activity-log-table__by">By {{ $activity->user->employee->full_name ?? $activity->user->username }}</span>
                        @endif
                    </td>
                    <td class="activity-log-table__type">
                        @if($activity->activity_type)
                            <span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $activity->activity_type)) }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="activity-log-table__date">
                        {{ $activity->log_date->timezone(config('app.timezone'))->format('M d, Y g:i A') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $isFaculty ? 3 : 4 }}">
                        @include('partials.ui.empty-state', [
                            'title' => 'No activity records',
                            'text' => $hasFilters ? 'No records match this search or filter.' : 'No activity records found.',
                        ])
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $activities->links('partials.pagination') }}
</div>
@endsection
