@extends('layouts.dashboard')

@section('title', 'Create Task')

@section('page-title', 'Create New Task')
@section('page-subtitle', 'Assign a task to coordinators or faculty members')

@section('sidebar')
    @include('partials.dean-sidebar')
@endsection

@section('content')
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Task Details</h3>
        </div>

        <form action="{{ route('dean.store-task') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label class="form-label">Assign To</label>
                <select name="assigned_to" class="form-control" required>
                    <option value="">Select User</option>
                    @foreach($assignableUsers as $user)
                        <option value="{{ $user->id }}">
                            {{ $user->employee->full_name ?? $user->username }} - {{ $user->role->role_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Task Title</label>
                <input type="text" name="task_title" class="form-control"
                       placeholder="Enter task title" required maxlength="15">
            </div>

            <div class="form-group">
                <label class="form-label">Task Description</label>
                <textarea name="task_description" class="form-control" rows="5"
                          placeholder="Enter detailed task description" maxlength="150"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Due Date</label>
                <input type="date" name="due_date" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label">Task Attachments</label>
                <input type="file" name="attachments[]" class="form-control" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.jpg,.jpeg,.png">
                <small class="text-gray-500 dark:text-gray-400 block mt-1">Optional. Upload up to 5 reference files.</small>
            </div>

            <div class="flex gap-2.5">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Task
                </button>
                <a href="{{ route('dean.tasks') }}" class="btn bg-gray-600 text-white">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
