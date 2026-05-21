@extends('layouts.dashboard')

@section('title', 'Analytics - Dean')

@section('page-title', 'Data Analytics')
@section('page-subtitle', 'Platform engagement, submissions, and usage insights')

@section('sidebar')
    @include('partials.dean-sidebar')
@endsection

@section('content')
    @include('partials.engagement-analytics')

    @include('partials.submission-analytics')

    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-tasks mr-2"></i>Task Status Distribution</h3>
        </div>
        <div class="py-2">
            @forelse($taskStatusData as $status)
                <div class="mb-5">
                    <div class="flex justify-between mb-2">
                        <span class="font-semibold text-sm text-gray-800 dark:text-gray-200">{{ $status->status }}</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $status->count }} tasks</span>
                    </div>
                    <div class="bg-gray-200 dark:bg-gray-700 h-2.5 overflow-hidden">
                        <div class="bg-gradient-to-r from-[#4caf50] to-[#028a0f] h-full" style="width: {{ ($status->count / max($taskStatusData->sum('count'), 1)) * 100 }}%;"></div>
                    </div>
                </div>
            @empty
                <p class="text-center text-gray-500 dark:text-gray-400 py-8">No task data available</p>
            @endforelse
        </div>
    </div>
@endsection
