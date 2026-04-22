@extends('layouts.dashboard')

@section('title', 'All Tasks - Dean')

@section('page-title', 'Task Management')
@section('page-subtitle', 'Create and manage tasks for all roles')

@section('sidebar')
    @include('partials.dean-sidebar')
@endsection

@section('content')
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">All Tasks</h3>
            <a href="{{ route('dean.create-task') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create New Task
            </a>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Task Title</th>
                    <th>Assigned To</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Attachments</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $task)
                <tr>
                    <td><strong>{{ $task->task_title }}</strong></td>
                    <td>{{ $task->assignedTo->employee->full_name ?? 'N/A' }}</td>
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
                            <span class="text-xs text-gray-500 dark:text-gray-400">No attachments</span>
                        @endif
                    </td>
                    <td>{{ $task->created_at->format('M d, Y') }}</td>
                    <td>
                        <div class="flex flex-col gap-2">
                            @if($task->status !== 'Completed')
                            <form action="{{ route('dean.update-task', $task->task_id) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <select name="status" onchange="this.form.submit()" class="form-control text-xs">
                                    <option value="Pending" {{ $task->status === 'Pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="In Progress" {{ $task->status === 'In Progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="Completed" {{ $task->status === 'Completed' ? 'selected' : '' }}>Completed</option>
                                </select>
                            </form>
                            @endif
                            <form action="{{ route('dean.tasks.attachments.store', $task->task_id) }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-2">
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
                    <td colspan="7" class="text-center text-gray-600 dark:text-gray-400">
                        No tasks created yet
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
