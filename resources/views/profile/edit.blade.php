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
@endphp

<div class="profile-edit-page">
    <div class="content-card profile-edit-card">
        <div class="card-header profile-edit-card__header">
            <h3 class="card-title profile-edit-card__title mb-0">
                <i class="fas fa-user-circle mr-1.5" aria-hidden="true"></i>
                Profile Information
            </h3>
        </div>

        {{-- Identity strip --}}
        <div class="profile-identity">
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

            <div class="profile-identity__meta min-w-0">
                <div class="profile-identity__name">{{ $displayName }}</div>
                @if($roleName !== '')
                    <span class="profile-identity__role">{{ $roleName }}</span>
                @endif
                <p class="profile-identity__hint">Click the camera to upload a photo (JPG, PNG, or WebP, max 2MB)</p>
            </div>
        </div>

        @error('avatar')
            <p class="profile-edit-error">{{ $message }}</p>
        @enderror

        <form action="{{ route('profile.update') }}" method="POST" class="profile-edit-form">
            @csrf

            <section class="profile-section" aria-labelledby="profile-account-heading">
                <h4 id="profile-account-heading" class="profile-section__title">Account</h4>
                <div class="profile-edit-grid">
                    <div class="form-group">
                        <label class="form-label">
                            Full Name
                            @if(!$canEditFullName)
                                <span class="profile-field-tag">Read-only</span>
                            @endif
                        </label>
                        <input type="text"
                               name="full_name"
                               class="form-control {{ !$canEditFullName ? 'is-readonly' : '' }}"
                               value="{{ old('full_name', optional($employee)->full_name) }}"
                               @if($canEditFullName) required @else readonly disabled @endif>
                        @if(!$canEditFullName)
                            <small class="profile-edit-note">Only the Dean or Program Coordinator can change your name.</small>
                        @endif
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Email Address
                            <span class="profile-field-tag profile-field-tag--muted">Optional</span>
                        </label>
                        <input type="email"
                               name="email"
                               class="form-control"
                               value="{{ old('email', $user->email) }}"
                               placeholder="you@example.com">
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Username
                            <span class="profile-field-tag">Read-only</span>
                        </label>
                        <input type="text" class="form-control is-readonly" value="{{ $user->username }}" disabled>
                    </div>
                </div>
            </section>

            <section class="profile-section" aria-labelledby="profile-employment-heading">
                <h4 id="profile-employment-heading" class="profile-section__title">Employment</h4>
                <div class="profile-edit-grid">
                    <div class="form-group">
                        <label class="form-label">Employee Number</label>
                        <input type="text"
                               name="employee_no"
                               class="form-control"
                               value="{{ old('employee_no', optional($employee)->employee_no) }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <input type="text"
                               name="department"
                               class="form-control"
                               value="{{ old('department', optional($employee)->department) }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Role
                            <span class="profile-field-tag">Read-only</span>
                        </label>
                        <input type="text" class="form-control is-readonly" value="{{ $roleName }}" disabled>
                    </div>
                </div>
            </section>

            <div class="profile-edit-actions">
                <button type="submit" class="btn btn-primary profile-edit-btn">
                    <i class="fas fa-save" aria-hidden="true"></i> Update Profile
                </button>
                <a href="#change-password" class="profile-edit-link">
                    <i class="fas fa-key" aria-hidden="true"></i> Change password
                </a>
            </div>
        </form>
    </div>

    <div id="change-password" class="content-card profile-edit-card profile-edit-card--password">
        <div class="card-header profile-edit-card__header">
            <h3 class="card-title profile-edit-card__title mb-0">
                <i class="fas fa-lock mr-1.5" aria-hidden="true"></i>
                Change Password
            </h3>
        </div>

        <form action="{{ route('profile.change-password') }}" method="POST" class="profile-edit-form">
            @csrf

            <div class="profile-edit-grid profile-edit-grid--password">
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
                </div>

                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" required minlength="8" autocomplete="new-password">
                    <small class="profile-edit-note">Minimum 8 characters</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="new_password_confirmation" class="form-control" required minlength="8" autocomplete="new-password">
                </div>
            </div>

            <div class="profile-edit-actions">
                <button type="submit" class="btn btn-primary profile-edit-btn">
                    <i class="fas fa-key" aria-hidden="true"></i> Change Password
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
