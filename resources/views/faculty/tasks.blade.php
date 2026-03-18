@extends('layouts.dashboard')

@section('title', 'My Tasks - Faculty')

@section('page-title', 'My Tasks')
@section('page-subtitle', 'View and manage your assigned tasks')

@section('sidebar')
    @include('partials.faculty-sidebar')
@endsection

@section('content')
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">All Tasks</h3>
            <span class="badge badge-info">{{ $tasks->total() }} Total</span>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Task Title</th>
                    <th>Description</th>
                    <th>Assigned By</th>
                    <th>Due Date</th>
                    <th>Status</th>
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
                        @else
                            <span class="text-gray-500 dark:text-gray-400 text-xs">No action needed</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-gray-500 dark:text-gray-400">
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
