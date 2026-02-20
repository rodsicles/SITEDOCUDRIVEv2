@extends('layouts.dashboard')

@section('title', 'Create Task')

@section('page-title', 'Create New Task')
@section('page-subtitle', 'Assign a task to faculty members')

@section('sidebar')
    <a href="{{ route('coordinator.dashboard') }}" class="menu-item">
        <i class="fas fa-chart-line"></i> Dashboard
    </a>
    <a href="{{ route('coordinator.tasks') }}" class="menu-item active">
        <i class="fas fa-tasks"></i> Tasks
    </a>
    <a href="{{ route('leave.index') }}" class="menu-item">
        <i class="fas fa-calendar-alt"></i> Leave Requests
    </a>
    <a href="{{ route('calendar.index') }}" class="menu-item">
        <i class="fas fa-calendar"></i> Calendar
    </a>
    <a href="{{ route('coordinator.faculty') }}" class="menu-item">
        <i class="fas fa-users"></i> Faculty Members
    </a>
    <a href="{{ route('coordinator.documents') }}" class="menu-item">
        <i class="fas fa-folder"></i> Documents
    </a>
@endsection

@section('content')
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Task Details</h3>
        </div>
        
        <form action="{{ route('coordinator.store-task') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label class="form-label">Assign To</label>
                <select name="assigned_to" class="form-control" required>
                    <option value="">Select Faculty Member</option>
                    @foreach($facultyMembers as $faculty)
                        <option value="{{ $faculty->id }}">
                            {{ $faculty->employee->full_name }} - {{ $faculty->email }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Task Title</label>
                <input type="text" name="task_title" class="form-control" 
                       placeholder="Enter task title" required maxlength="150">
            </div>

            <div class="form-group">
                <label class="form-label">Task Description</label>
                <textarea name="task_description" class="form-control" rows="5" 
                          placeholder="Enter detailed task description"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Due Date</label>
                <input type="date" name="due_date" class="form-control" required>
            </div>

            <div class="flex gap-2.5">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Task
                </button>
                <a href="{{ route('coordinator.tasks') }}" class="btn bg-gray-600 hover:bg-gray-700 text-white">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
