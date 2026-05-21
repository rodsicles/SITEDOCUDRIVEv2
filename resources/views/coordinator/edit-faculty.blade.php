@extends('layouts.dashboard')

@section('title', 'Edit Faculty - Coordinator')

@section('page-title', 'Edit Faculty Information')
@section('page-subtitle', 'Update faculty member details')

@section('sidebar')
    @include('partials.coordinator-sidebar')
@endsection

@section('content')
    <!-- Back Button -->
    <div class="mb-5">
        <a href="{{ route('coordinator.faculty-profile', $employee->employee_id) }}" class="btn bg-gray-600 hover:bg-gray-700 text-white">
            <i class="fas fa-arrow-left"></i> Back to Profile
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            <strong><i class="fas fa-check-circle"></i> Success!</strong>
            <p class="mt-2">{{ session('success') }}</p>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-error">
            <strong><i class="fas fa-exclamation-circle"></i> Error!</strong>
            <ul class="mt-2 ml-6">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Edit Faculty Information -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Edit Faculty Information</h3>
        </div>

        <form action="{{ route('coordinator.update-faculty', $employee->employee_id) }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" 
                           value="{{ old('full_name', $employee->full_name) }}" 
                           required maxlength="45" placeholder="Enter full name">
                </div>

                <div class="form-group">
                    <label class="form-label">Employee Number</label>
                    <input type="text" name="employee_no" class="form-control" 
                           value="{{ old('employee_no', $employee->employee_no) }}" 
                           maxlength="15" pattern="[0-9]*" title="Numbers only" placeholder="e.g. FAC001">
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address <span class="text-xs text-gray-500">(optional)</span></label>
                    <input type="email" name="email" class="form-control" 
                           value="{{ old('email', $employee->user->email) }}" 
                           maxlength="45" placeholder="faculty@example.com">
                    <small class="text-gray-600 dark:text-gray-400 text-xs mt-1.5 block">
                        Optional contact field. Not used for password recovery (SMTP not yet configured).
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label">Department</label>
                    <input type="text" class="form-control" value="{{ auth()->user()->employee->department ?? 'N/A' }}" disabled>
                    <input type="hidden" name="department" value="{{ auth()->user()->employee->department }}">
                    <small class="modern-help-text">
                        <i class="fas fa-info-circle"></i> Auto-assigned to your department
                    </small>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Username (Read-only)</label>
                <input type="text" class="form-control" 
                       value="{{ $employee->user->username }}" disabled>
                <small class="text-gray-600 dark:text-gray-400 text-xs mt-1.5 block">Username cannot be changed</small>
            </div>

            <div class="flex gap-4 mt-6">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Information
                </button>
                <a href="{{ route('coordinator.faculty-profile', $employee->employee_id) }}" class="btn bg-gray-600 hover:bg-gray-700 text-white">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>

@endsection
