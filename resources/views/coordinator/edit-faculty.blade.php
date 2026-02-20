@extends('layouts.dashboard')

@section('title', 'Edit Faculty - Coordinator')

@section('page-title', 'Edit Faculty Information')
@section('page-subtitle', 'Update faculty member details and reset password')

@section('sidebar')
    <a href="{{ route('coordinator.dashboard') }}" class="menu-item">
        <i class="fas fa-chart-line"></i> Dashboard
    </a>
    <a href="{{ route('coordinator.tasks') }}" class="menu-item">
        <i class="fas fa-tasks"></i> Tasks
    </a>
    <a href="{{ route('leave.index') }}" class="menu-item">
        <i class="fas fa-calendar-alt"></i> Leave Requests
    </a>
    <a href="{{ route('calendar.index') }}" class="menu-item">
        <i class="fas fa-calendar"></i> Calendar
    </a>
    <a href="{{ route('coordinator.faculty') }}" class="menu-item active">
        <i class="fas fa-users"></i> Faculty Members
    </a>
    <a href="{{ route('coordinator.documents') }}" class="menu-item">
        <i class="fas fa-folder"></i> Documents
    </a>
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
                           required maxlength="100" placeholder="Enter full name">
                </div>

                <div class="form-group">
                    <label class="form-label">Employee Number</label>
                    <input type="text" name="employee_no" class="form-control" 
                           value="{{ old('employee_no', $employee->employee_no) }}" 
                           maxlength="30" placeholder="e.g. FAC001">
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address *</label>
                    <input type="email" name="email" class="form-control" 
                           value="{{ old('email', $employee->user->email) }}" 
                           required maxlength="100" placeholder="faculty@example.com">
                </div>

                <div class="form-group">
                    <label class="form-label">Department *</label>
                    <select name="department" class="form-control" required>
                        <option value="">Select Department</option>
                        <option value="Information Technology" {{ old('department', $employee->department) === 'Information Technology' ? 'selected' : '' }}>
                            Information Technology
                        </option>
                        <option value="Engineering" {{ old('department', $employee->department) === 'Engineering' ? 'selected' : '' }}>
                            Engineering
                        </option>
                    </select>
                    <small class="modern-help-text">
                        <i class="fas fa-info-circle"></i> Select the department where this faculty is assigned
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

    <!-- Reset Password Section -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Reset Password</h3>
        </div>

        <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg mb-5 border-l-4 border-orange-500">
            <p class="m-0 text-orange-800 dark:text-orange-400 text-sm">
                <i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong> Resetting the password will immediately change the faculty member's login credentials. Make sure to inform them of the new password.
            </p>
        </div>

        <form action="{{ route('coordinator.reset-faculty-password', $employee->employee_id) }}" method="POST" id="resetPasswordForm">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="form-group">
                    <label class="form-label">New Password *</label>
                    <input type="password" name="new_password" class="form-control" 
                           required minlength="8" placeholder="Enter new password">
                    <small class="text-gray-600 dark:text-gray-400 text-xs mt-1.5 block">Minimum 8 characters</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm New Password *</label>
                    <input type="password" name="new_password_confirmation" class="form-control" 
                           required minlength="8" placeholder="Confirm new password">
                    <small class="text-gray-600 dark:text-gray-400 text-xs mt-1.5 block">Must match the new password</small>
                </div>
            </div>

            <div class="mt-6">
                <button type="button" class="btn btn-danger" onclick="confirmPasswordReset()">
                    <i class="fas fa-key"></i> Reset Password
                </button>
            </div>
        </form>
    </div>

    <script>
        function confirmPasswordReset() {
            if (confirm('Are you sure you want to reset the password for {{ $employee->full_name }}?\n\nThis action cannot be undone and will immediately change their login credentials.')) {
                document.getElementById('resetPasswordForm').submit();
            }
        }
    </script>
@endsection
