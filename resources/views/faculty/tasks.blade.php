@extends('layouts.dashboard')

@section('title', 'My Tasks - Faculty')

@section('page-title', 'My Tasks')
@section('page-subtitle', 'View and manage your assigned tasks')

@section('sidebar')
    @include('partials.faculty-sidebar')
@endsection

@section('content')
    @php
        $filter = $filter ?? 'all';
        $counts = $counts ?? ['all'=>0,'today'=>0,'week'=>0,'overdue'=>0,'pending'=>0,'completed'=>0];
        $chips = [
            ['key' => 'all',       'label' => 'All',          'icon' => 'fa-list'],
            ['key' => 'today',     'label' => 'Today',        'icon' => 'fa-calendar-day'],
            ['key' => 'week',      'label' => 'This Week',    'icon' => 'fa-calendar-week'],
            ['key' => 'overdue',   'label' => 'Overdue',      'icon' => 'fa-triangle-exclamation'],
            ['key' => 'pending',   'label' => 'Pending',      'icon' => 'fa-hourglass-half'],
            ['key' => 'completed', 'label' => 'Completed',    'icon' => 'fa-check'],
        ];
    @endphp

    {{-- Quick Filter Chips --}}
    <div class="content-card mb-4">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mr-2">
                <i class="fas fa-filter mr-1"></i> Filter:
            </span>
            @foreach($chips as $chip)
                @php
                    $isActive = $filter === $chip['key'];
                    $count = $counts[$chip['key']] ?? 0;
                    $base = 'inline-flex items-center gap-2 px-3 py-1.5 text-xs font-semibold no-underline border-0';
                    $active = 'bg-[#028a0f] text-white';
                    $inactive = 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600';
                    $tone = $chip['key'] === 'overdue' && $count > 0 && !$isActive
                        ? 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 hover:bg-red-100'
                        : $inactive;
                @endphp
                <a href="{{ route('faculty.tasks', ['filter' => $chip['key']]) }}"
                   class="{{ $base }} {{ $isActive ? $active : $tone }}">
                    <i class="fas {{ $chip['icon'] }}"></i>
                    {{ $chip['label'] }}
                    <span class="px-1.5 py-0.5 text-[10px] font-bold {{ $isActive ? 'bg-white text-[#028a0f]' : 'bg-white dark:bg-[#1e1e1e] text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-600' }}">
                        {{ $count }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>

    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">
                @if($filter === 'all')      All Tasks
                @elseif($filter === 'today') Tasks Due Today
                @elseif($filter === 'week')  Tasks Due This Week
                @elseif($filter === 'overdue') Overdue Tasks
                @elseif($filter === 'pending') Pending Tasks
                @elseif($filter === 'completed') Completed Tasks
                @endif
            </h3>
            <span class="badge badge-info">{{ $tasks->total() }} Showing</span>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Task Title</th>
                    <th>Description</th>
                    <th>Assigned By</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Attachments</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $task)
                <tr>
                    <td><strong>{{ $task->task_title }}</strong></td>
                    <td>{{ Str::limit($task->task_description ?? 'No description', 50) }}</td>
                    <td>{{ $task->assignedBy->employee->full_name ?? $task->assignedBy->username }}</td>
                    <td>
                        {{ $task->due_date ? $task->due_date->format('M d, Y') : 'N/A' }}
                        @if($task->due_date && $task->due_date->isPast() && $task->status !== 'Completed')
                            <span class="badge badge-danger ml-2">Overdue</span>
                        @endif
                    </td>
                    <td>
                        @if($task->status === 'Completed')
                            <span class="badge badge-success">Completed</span>
                        @elseif($task->status === 'In Progress')
                            <span class="badge badge-warning">In Progress</span>
                        @else
                            <span class="badge badge-danger">Pending</span>
                        @endif
                    </td>
                    <td>
                        @if($task->attachments->isNotEmpty())
                            <div class="flex flex-col gap-1">
                                @foreach($task->attachments as $attachment)
                                    <a href="{{ route('task-attachments.download', $attachment->task_attachment_id) }}" class="text-xs text-blue-700 dark:text-blue-300 no-underline">
                                        <i class="fas fa-paperclip mr-1"></i>{{ Str::limit($attachment->original_name, 24) }}
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <span class="text-gray-500 dark:text-gray-400 text-xs">No attachments</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex flex-col gap-2">
                            @if($task->status !== 'Completed')
                            <form action="{{ route('faculty.update-task-status', $task->task_id) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <select name="status" class="form-control text-xs py-2 px-3 border-2" onchange="this.form.submit()">
                                    <option value="Pending" {{ $task->status === 'Pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="In Progress" {{ $task->status === 'In Progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="Completed" {{ $task->status === 'Completed' ? 'selected' : '' }}>Completed</option>
                                </select>
                            </form>
                            @endif
                            <form action="{{ route('faculty.tasks.attachments.store', $task->task_id) }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-2">
                                @csrf
                                <input type="file" name="attachment" class="form-control text-xs" required>
                                <button type="submit" class="btn btn-primary text-xs">
                                    <i class="fas fa-upload"></i> Upload File
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-gray-500 dark:text-gray-400">
                        No tasks assigned yet
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        <div class="mt-5">
            {{ $tasks->links() }}
        </div>
    </div>
@endsection
