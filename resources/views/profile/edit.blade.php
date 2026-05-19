@extends('layouts.dashboard')

@section('title', 'Edit Profile')

@section('page-title', 'Edit Profile')
@section('page-subtitle', 'Update your personal information and password')

@section('sidebar')
    @if(auth()->user()->isDean())
        @include('partials.dean-sidebar')
    @elseif(auth()->user()->isProgramCoordinator())
        @include('partials.coordinator-sidebar')
    @else
        @include('partials.faculty-sidebar')
    @endif
@endsection

@section('content')
<div class="profile-edit-page">
    <div class="content-card profile-edit-card">
        <div class="card-header profile-edit-card-header">
            <h3 class="card-title mb-0">Profile Information</h3>
        </div>

        <form action="{{ route('profile.update') }}" method="POST" class="profile-edit-form">
            @csrf

            <div class="profile-edit-grid">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control"
                           value="{{ old('full_name', $employee->full_name) }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control"
                           value="{{ old('email', $user->email) }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Employee Number</label>
                    <input type="text" name="employee_no" class="form-control"
                           value="{{ old('employee_no', $employee->employee_no) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Department</label>
                    <input type="text" name="department" class="form-control"
                           value="{{ old('department', $employee->department) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Username (Read-only)</label>
                    <input type="text" class="form-control" value="{{ $user->username }}" disabled>
                </div>

                <div class="form-group">
                    <label class="form-label">Role (Read-only)</label>
                    <input type="text" class="form-control" value="{{ $user->role->role_name }}" disabled>
                </div>
            </div>

            <button type="submit" class="btn btn-primary profile-edit-btn">
                <i class="fas fa-save"></i> Update Profile
            </button>
        </form>
    </div>

    <div class="content-card profile-edit-card">
        <div class="card-header profile-edit-card-header">
            <h3 class="card-title mb-0">Change Password</h3>
        </div>

        <form action="{{ route('profile.change-password') }}" method="POST" class="profile-edit-form">
            @csrf

            <div class="profile-edit-grid profile-edit-grid--password">
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" required minlength="8">
                    <small class="profile-edit-note">Minimum 8 characters</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="new_password_confirmation" class="form-control" required minlength="8">
                </div>
            </div>

            <button type="submit" class="btn btn-primary profile-edit-btn">
                <i class="fas fa-key"></i> Change Password
            </button>
        </form>
    </div>
</div>
@endsection
