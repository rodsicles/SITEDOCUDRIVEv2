@extends('layouts.dashboard')

@section('title', 'Edit Profile')

@section('page-title', 'Edit Profile')
@section('page-subtitle', 'Update your personal information and password')

@section('sidebar')
    @if(auth()->user()->isDean())
        @include('partials.dean-sidebar')
    @elseif(auth()->user()->isProgramCoordinator())
        @include('partials.coordinator-sidebar')
    @elseif(auth()->user()->isSecretary())
        @include('partials.secretary-sidebar')
    @else
        @include('partials.faculty-sidebar')
    @endif
@endsection

@section('content')
@php
    $displayName = optional($employee)->full_name ?? $user->username;
    $roleName = $user->role->role_name ?? '';
    $departmentValue = old('department', optional($employee)->department);
    $departments = ['Engineering', 'Information Technology'];
@endphp

<div class="profile-edit-page">
    <div class="content-card profile-edit-card">
        <section class="profile-identity" aria-label="Profile photo and name">
            <form action="{{ route('profile.avatar') }}" method="POST" enctype="multipart/form-data" id="avatarForm" class="profile-identity__avatar-form">
                @csrf
                <div class="profile-identity__avatar">
                    @if($user->avatarUrl())
                        <img src="{{ $user->avatarUrl() }}"
                             alt="Profile picture"
                             class="profile-identity__photo"
                             id="avatarPreview"
                             onerror="this.classList.add('hidden'); document.getElementById('avatarFallback')?.classList.remove('hidden');">
                        <div id="avatarFallback" class="profile-identity__fallback hidden">
                            {{ $user->initials() }}
                        </div>
                    @else
                        <div class="profile-identity__fallback">
                            {{ $user->initials() }}
                        </div>
                    @endif
                    <label for="avatarInput" class="profile-identity__camera" title="Change photo">
                        <i class="fas fa-camera" aria-hidden="true"></i>
                        <span class="sr-only">Upload profile photo</span>
                    </label>
                    <input type="file"
                           name="avatar"
                           id="avatarInput"
                           accept="image/png,image/jpeg,image/webp,.png,.jpg,.jpeg,.webp"
                           class="hidden"
                           onchange="if (this.files?.length) document.getElementById('avatarForm').submit()">
                </div>
            </form>

            <div class="profile-identity__meta">
                <div class="profile-identity__name">{{ $displayName }}</div>
                @if($roleName !== '')
                    <span class="profile-identity__role">{{ $roleName }}</span>
                @endif
                <p class="profile-identity__hint">Click the camera to upload a photo (JPG, PNG, or WebP, max 2MB)</p>
            </div>
        </section>

        @error('avatar')
            <p class="profile-edit-error">{{ $message }}</p>
        @enderror

        <form action="{{ route('profile.update') }}" method="POST" class="profile-edit-form">
            @csrf

            <section class="profile-section" aria-labelledby="profile-personal-heading">
                <h3 id="profile-personal-heading" class="profile-section__title">Personal details</h3>

                <div class="profile-edit-grid">
                    <div class="form-group">
                        <label class="form-label" for="profileFullName">
                            Full name
                            @if(!$canEditFullName)
                                <span class="profile-field-tag">Read-only</span>
                            @endif
                        </label>
                        <input type="text"
                               id="profileFullName"
                               name="full_name"
                               class="form-control {{ !$canEditFullName ? 'is-readonly' : '' }} @error('full_name') is-invalid @enderror"
                               value="{{ old('full_name', optional($employee)->full_name) }}"
                               @if($canEditFullName) required @else readonly disabled @endif>
                        @if(!$canEditFullName)
                            <small class="profile-edit-note">Only the Dean or Program Coordinator can change your name.</small>
                        @endif
                        @error('full_name')
                            <small class="profile-edit-error-inline">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="profileEmail">
                            Email address
                            <span class="profile-field-tag profile-field-tag--muted">Optional</span>
                        </label>
                        <input type="email"
                               id="profileEmail"
                               name="email"
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $user->email) }}"
                               placeholder="you@example.com">
                        @error('email')
                            <small class="profile-edit-error-inline">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="profileUsername">
                            Username
                            <span class="profile-field-tag">Read-only</span>
                        </label>
                        <input type="text" id="profileUsername" class="form-control is-readonly" value="{{ $user->username }}" disabled>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="profileEmployeeNo">Employee number</label>
                        <input type="text"
                               id="profileEmployeeNo"
                               name="employee_no"
                               class="form-control @error('employee_no') is-invalid @enderror"
                               value="{{ old('employee_no', optional($employee)->employee_no) }}">
                        @error('employee_no')
                            <small class="profile-edit-error-inline">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="profileDepartment">Department</label>
                        <select id="profileDepartment"
                                name="department"
                                class="form-control @error('department') is-invalid @enderror">
                            <option value="">Select department</option>
                            @foreach($departments as $department)
                                <option value="{{ $department }}" @selected($departmentValue === $department)>{{ $department }}</option>
                            @endforeach
                        </select>
                        @error('department')
                            <small class="profile-edit-error-inline">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="profileRole">
                            Role
                            <span class="profile-field-tag">Read-only</span>
                        </label>
                        <input type="text" id="profileRole" class="form-control is-readonly" value="{{ $roleName }}" disabled>
                    </div>
                </div>

                <div class="profile-edit-actions">
                    <button type="submit" class="btn btn-primary profile-edit-btn">
                        <i class="fas fa-save" aria-hidden="true"></i> Update Profile
                    </button>
                </div>
            </section>
        </form>
    </div>

    <div id="change-password" class="content-card profile-edit-card profile-edit-card--password">
        <form action="{{ route('profile.change-password') }}" method="POST" class="profile-edit-form">
            @csrf

            <section class="profile-section profile-section--flush" aria-labelledby="profile-security-heading">
                <h3 id="profile-security-heading" class="profile-section__title">Account security</h3>
                <p class="profile-section__lede">Change the password you use to sign in to SITE DocuDrive.</p>

                <div class="profile-edit-grid profile-edit-grid--password">
                    <div class="form-group profile-edit-grid__span">
                        <label class="form-label" for="currentPassword">Current password</label>
                        <input type="password"
                               id="currentPassword"
                               name="current_password"
                               class="form-control @error('current_password') is-invalid @enderror"
                               required
                               autocomplete="current-password">
                        @error('current_password')
                            <small class="profile-edit-error-inline">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="newPassword">New password</label>
                        <input type="password"
                               id="newPassword"
                               name="new_password"
                               class="form-control @error('new_password') is-invalid @enderror"
                               required
                               minlength="8"
                               autocomplete="new-password">
                        <small class="profile-edit-note">Minimum 8 characters</small>
                        @error('new_password')
                            <small class="profile-edit-error-inline">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirmNewPassword">Confirm new password</label>
                        <input type="password"
                               id="confirmNewPassword"
                               name="new_password_confirmation"
                               class="form-control"
                               required
                               minlength="8"
                               autocomplete="new-password">
                    </div>
                </div>

                <div class="profile-edit-actions">
                    <button type="submit" class="btn btn-primary profile-edit-btn">
                        <i class="fas fa-key" aria-hidden="true"></i> Change Password
                    </button>
                </div>
            </section>
        </form>
    </div>
</div>
@endsection
