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

        <div class="flex items-center gap-3 px-4 pt-4">
            <form action="{{ route('profile.avatar') }}" method="POST" enctype="multipart/form-data" id="avatarForm">
                @csrf
                <div class="relative w-14 h-14 flex-shrink-0">
                    @if($user->avatarUrl())
                        <img src="{{ $user->avatarUrl() }}" alt="Profile picture" class="w-14 h-14 rounded-full object-cover border-2 border-[#026a0c]">
                    @else
                        <div class="w-14 h-14 rounded-full bg-[#028a0f] text-white flex items-center justify-center font-semibold text-lg border-2 border-[#026a0c]">
                            {{ $user->initials() }}
                        </div>
                    @endif
                    <label for="avatarInput" class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-white dark:bg-[#2a2a2a] border border-gray-300 dark:border-gray-600 flex items-center justify-center cursor-pointer" title="Change photo">
                        <i class="fas fa-camera text-[10px] text-gray-600 dark:text-gray-300"></i>
                    </label>
                    <input type="file" name="avatar" id="avatarInput" accept="image/png,image/jpeg,image/webp" class="hidden" onchange="document.getElementById('avatarForm').submit()">
                </div>
            </form>
            <div>
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 m-0">{{ optional($employee)->full_name ?? $user->username }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 m-0">{{ $user->role->role_name ?? '' }}</p>
            </div>
        </div>

        <form action="{{ route('profile.update') }}" method="POST" class="profile-edit-form">
            @csrf

            <div class="profile-edit-grid">
                <div class="form-group">
                    <label class="form-label">Full Name @if(!$canEditFullName)<span class="text-xs text-gray-500">(Read-only)</span>@endif</label>
                    <input type="text"
                           name="full_name"
                           class="form-control {{ !$canEditFullName ? 'bg-gray-100 dark:bg-gray-800' : '' }}"
                           value="{{ old('full_name', optional($employee)->full_name) }}"
                           @if($canEditFullName) required @else readonly disabled @endif>
                    @if(!$canEditFullName)
                    <small class="profile-edit-note">Only the Dean or Program Coordinator can change your name. Contact them if it needs updating.</small>
                    @endif
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address <span class="text-xs text-gray-500">(optional)</span></label>
                    <input type="email" name="email" class="form-control"
                           value="{{ old('email', $user->email) }}" placeholder="you@example.com">
                </div>

                <div class="form-group">
                    <label class="form-label">Employee Number</label>
                    <input type="text" name="employee_no" class="form-control"
                           value="{{ old('employee_no', optional($employee)->employee_no) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Department</label>
                    <input type="text" name="department" class="form-control"
                           value="{{ old('department', optional($employee)->department) }}">
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
