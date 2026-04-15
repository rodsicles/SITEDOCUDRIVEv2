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
    <!-- Profile Information -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Profile Information</h3>
        </div>
        
        <form action="{{ route('profile.update') }}" method="POST">
            @csrf
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
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
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
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
            </div>

            <div class="form-group">
                <label class="form-label">Username (Read-only)</label>
                <input type="text" class="form-control" value="{{ $user->username }}" disabled>
            </div>

            <div class="form-group">
                <label class="form-label">Role (Read-only)</label>
                <input type="text" class="form-control" value="{{ $user->role->role_name }}" disabled>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Update Profile
            </button>
        </form>
    </div>

    <!-- Change Password -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Change Password</h3>
        </div>
        
        <form action="{{ route('profile.change-password') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" required minlength="8">
                <small style="color: var(--text-light);">Minimum 8 characters</small>
            </div>

            <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="new_password_confirmation" class="form-control" required minlength="8">
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-key"></i> Change Password
            </button>
        </form>
    </div>

    @if(auth()->user()->isFaculty())
    <!-- Skill Tags -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-tags mr-2"></i>My Skill Tags</h3>
            <span class="badge badge-info">{{ $skillTags->count() }} Tags</span>
        </div>

        <form action="{{ route('skill-tags.store') }}" method="POST" class="mb-5">
            @csrf
            <div class="flex gap-3 items-end">
                <div class="form-group mb-0 flex-1">
                    <label class="form-label">Add a Skill Tag</label>
                    <input type="text" name="tag_name" class="form-control" placeholder="e.g. PHP, Networking, UI/UX" required maxlength="50">
                </div>
                <button type="submit" class="btn btn-primary border-0">
                    <i class="fas fa-plus"></i> Add Tag
                </button>
            </div>
            @error('tag_name')
                <p class="text-red-600 dark:text-red-400 text-sm mt-2">{{ $message }}</p>
            @enderror
        </form>

        @if($skillTags->count() > 0)
            <div class="py-2">
                @foreach($skillTags as $tag)
                    <span class="skill-tag">
                        {{ $tag->tag_name }}
                        <form action="{{ route('skill-tags.destroy', $tag->skill_tag_id) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="skill-tag-remove" title="Remove tag">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </span>
                @endforeach
            </div>
        @else
            <div class="text-center py-10 text-gray-500 dark:text-gray-400">
                <i class="fas fa-tags text-5xl mb-4 opacity-50"></i>
                <p>No skill tags added yet. Add your first tag above.</p>
            </div>
        @endif
    </div>
    @endif
@endsection
