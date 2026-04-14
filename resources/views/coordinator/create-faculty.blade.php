@extends('layouts.dashboard')

@section('title', 'Create Faculty Account')

@section('page-title', 'Add Faculty Member')
@section('page-subtitle', 'Create a new faculty employee account')

@section('sidebar')
    @include('partials.coordinator-sidebar')
@endsection

@section('content')
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Faculty Account Information</h3>
        </div>
        
        <form action="{{ route('coordinator.store-faculty') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" 
                       placeholder="Enter full name" required maxlength="45" value="{{ old('full_name') }}">
            </div>

            <div class="form-group">
                <label class="form-label">Employee Number</label>
                <input type="text" name="employee_no" class="form-control" 
                       placeholder="Enter employee number (optional)" maxlength="15" pattern="[0-9]*" title="Numbers only" value="{{ old('employee_no') }}">
            </div>

            <div class="form-group">
                <label class="form-label">Department</label>
                <input type="text" class="form-control" value="{{ auth()->user()->employee->department ?? 'N/A' }}" disabled>
                <input type="hidden" name="department" value="{{ auth()->user()->employee->department }}">
                <small class="text-xs text-gray-500 dark:text-gray-400 mt-1">Auto-assigned to your department</small>
            </div>

            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" 
                       placeholder="Enter username" required maxlength="20" value="{{ old('username') }}">
            </div>

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" 
                       placeholder="Enter email address" required maxlength="45" value="{{ old('email') }}">
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" 
                       placeholder="Enter password (min 8 characters)" required minlength="8" maxlength="40">
            </div>

            <div class="flex gap-2.5">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-user-plus"></i> Create Faculty Account
                </button>
                <a href="{{ route('coordinator.faculty') }}" class="btn bg-gray-600 hover:bg-gray-700 text-white">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
