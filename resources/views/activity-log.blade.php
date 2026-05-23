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

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-history mr-2"></i> Activity Log
        </h3>
        <span class="badge badge-info">{{ $activities->total() }} Total Records</span>
    </div>

    <div class="overflow-x-auto">
        <table class="data-table compact" style="width: 100%; table-layout: fixed;">
            <colgroup>
                @if(!auth()->user()->isFaculty())
                <col style="width: 25%;">
                <col style="width: 40%;">
                <col style="width: 18%;">
                <col style="width: 17%;">
                @else
                <col style="width: 55%;">
                <col style="width: 22%;">
                <col style="width: 23%;">
                @endif
            </colgroup>
            <thead>
                <tr>
                    @if(!auth()->user()->isFaculty())
                    <th>User</th>
                    @endif
                    <th>Activity</th>
                    <th>Type</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activities as $activity)
                <tr>
                    @if(!auth()->user()->isFaculty())
                    <td>
                        <strong>{{ $activity->user->employee->full_name ?? $activity->user->username ?? 'System' }}</strong>
                        @if($activity->targetUser)
                            <i class="fas fa-arrow-right text-gray-400 dark:text-gray-500 mx-1"></i>
                            <span class="text-gray-500 dark:text-gray-400 text-sm">{{ $activity->targetUser->employee->full_name ?? $activity->targetUser->username }}</span>
                        @endif
                    </td>
                    @endif
                    <td>
                        {{ $activity->activity }}
                        @if(auth()->user()->isFaculty() && $activity->user_id !== auth()->id() && $activity->user)
                            <br><small class="text-gray-500 dark:text-gray-400"><i class="fas fa-info-circle mr-0.5"></i> By {{ $activity->user->employee->full_name ?? $activity->user->username }}</small>
                        @endif
                    </td>
                    <td>
                        @if($activity->activity_type)
                            <span class="badge badge-neutral text-[0.7rem]">
                                {{ ucfirst(str_replace('_', ' ', $activity->activity_type)) }}
                            </span>
                        @else
                            <span class="text-gray-400 dark:text-gray-500 text-sm">—</span>
                        @endif
                    </td>
                    <td class="text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                        {{ $activity->log_date->timezone(config('app.timezone'))->format('M d, Y g:i A') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ auth()->user()->isFaculty() ? 3 : 4 }}" class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-history text-2xl mb-2 block text-gray-300 dark:text-gray-600"></i>
                        No activity records found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 px-2 pb-4">
        {{ $activities->links('partials.pagination') }}
    </div>
</div>

@endsection
