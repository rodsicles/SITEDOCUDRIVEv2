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
                    <td>{{ $task->created_at->format('M d, Y') }}</td>
                    <td>
                        @if($task->status !== 'Completed')
                        <form action="{{ route('dean.update-task', $task->task_id) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <select name="status" onchange="this.form.submit()" class="px-2 py-1 border border-gray-200 dark:border-gray-700 text-sm bg-white dark:bg-[#1e1e1e] text-gray-800 dark:text-gray-200 cursor-pointer">
                                <option value="Pending" {{ $task->status === 'Pending' ? 'selected' : '' }}>Pending</option>
                                <option value="In Progress" {{ $task->status === 'In Progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="Completed" {{ $task->status === 'Completed' ? 'selected' : '' }}>Completed</option>
                            </select>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-gray-600 dark:text-gray-400">
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
