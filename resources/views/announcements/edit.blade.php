@extends('layouts.dashboard')

@section('title', 'Edit Announcement')

@section('page-title', 'Edit Announcement')
@section('page-subtitle', 'Update your announcement')

@section('sidebar')
    @if($sidebar['rolePrefix'] === 'dean')
        <a href="{{ route('dean.dashboard') }}" class="menu-item">
            <i class="fas fa-chart-line"></i> Dashboard
        </a>
        <a href="{{ route('leave.index') }}" class="menu-item">
            <i class="fas fa-calendar-alt"></i> Leave Requests
        </a>
        <a href="{{ route('calendar.index') }}" class="menu-item">
            <i class="fas fa-calendar"></i> Calendar
        </a>
        <a href="{{ route('announcements.index') }}" class="menu-item active">
            <i class="fas fa-bullhorn"></i> Announcements
        </a>
        <a href="{{ route('dean.employees') }}" class="menu-item">
            <i class="fas fa-users"></i> Faculty Members
        </a>
        <a href="{{ route('dean.reports') }}" class="menu-item">
            <i class="fas fa-file-alt"></i> Performance Reports
        </a>
        <a href="{{ route('dean.analytics') }}" class="menu-item">
            <i class="fas fa-chart-pie"></i> Analytics
        </a>
        <a href="{{ route('dean.documents') }}" class="menu-item">
            <i class="fas fa-folder"></i> Documents
        </a>
    @elseif($sidebar['rolePrefix'] === 'coordinator')
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
        <a href="{{ route('announcements.index') }}" class="menu-item active">
            <i class="fas fa-bullhorn"></i> Announcements
        </a>
        <a href="{{ route('coordinator.faculty') }}" class="menu-item">
            <i class="fas fa-users"></i> Faculty Members
        </a>
        <a href="{{ route('coordinator.documents') }}" class="menu-item">
            <i class="fas fa-folder"></i> Documents
        </a>
    @endif
@endsection

@section('content')
    <div class="content-card" style="max-width: 800px;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-edit mr-2"></i> Edit Announcement</h3>
            <a href="{{ route('announcements.index') }}" class="btn btn-primary">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>

        @if($errors->any())
        <div class="alert alert-error">
            <ul class="m-0 pl-4">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('announcements.update', $announcement->announcement_id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="form-label" for="title">Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" id="title" class="form-control" value="{{ old('title', $announcement->title) }}" placeholder="Enter announcement title..." required maxlength="255">
            </div>

            <div class="form-group">
                <label class="form-label" for="body">Content <span class="text-red-500">*</span></label>
                <textarea name="body" id="body" class="form-control" rows="8" placeholder="Write your announcement here..." required maxlength="5000">{{ old('body', $announcement->body) }}</textarea>
                <small class="text-gray-500 dark:text-gray-400 mt-1 block">
                    <span id="charCount">0</span>/5000 characters
                </small>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label" for="visibility">Visible To <span class="text-red-500">*</span></label>
                    <select name="visibility" id="visibility" class="form-control">
                        <option value="All" {{ old('visibility', $announcement->visibility) === 'All' ? 'selected' : '' }}>All Users</option>
                        <option value="Dean" {{ old('visibility', $announcement->visibility) === 'Dean' ? 'selected' : '' }}>Dean Only</option>
                        <option value="Program Coordinator" {{ old('visibility', $announcement->visibility) === 'Program Coordinator' ? 'selected' : '' }}>Program Coordinators</option>
                        <option value="Faculty Employee" {{ old('visibility', $announcement->visibility) === 'Faculty Employee' ? 'selected' : '' }}>Faculty Employees</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="department">Department <span class="text-red-500">*</span></label>
                    <select name="department" id="department" class="form-control">
                        <option value="All" {{ old('department', $announcement->department) === 'All' ? 'selected' : '' }}>All Departments</option>
                        <option value="Engineering" {{ old('department', $announcement->department) === 'Engineering' ? 'selected' : '' }}>Engineering</option>
                        <option value="Information Technology" {{ old('department', $announcement->department) === 'Information Technology' ? 'selected' : '' }}>Information Technology</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label" for="expires_at">Expires At <small class="text-gray-400 font-normal">(optional)</small></label>
                    <input type="datetime-local" name="expires_at" id="expires_at" class="form-control" value="{{ old('expires_at', $announcement->expires_at ? $announcement->expires_at->format('Y-m-d\TH:i') : '') }}">
                    <small class="text-gray-500 dark:text-gray-400 mt-1 block">Leave empty for no expiration</small>
                </div>

                <div class="form-group flex items-end pb-2">
                    <label class="flex items-center gap-3 cursor-pointer select-none">
                        <input type="hidden" name="is_pinned" value="0">
                        <input type="checkbox" name="is_pinned" value="1" class="w-5 h-5 border-gray-300 dark:border-gray-600 text-[#028a0f] focus:ring-[#028a0f]" {{ old('is_pinned', $announcement->is_pinned) ? 'checked' : '' }}>
                        <span class="form-label mb-0">
                            <i class="fas fa-thumbtack mr-1 text-[#028a0f]"></i> Pin this announcement
                        </span>
                    </label>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('announcements.index') }}" class="btn" style="background: #6b7280; color: white;">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i> Update Announcement
                </button>
            </div>
        </form>
    </div>

    <script>
        const bodyField = document.getElementById('body');
        const charCount = document.getElementById('charCount');
        bodyField.addEventListener('input', () => {
            charCount.textContent = bodyField.value.length;
        });
        charCount.textContent = bodyField.value.length;
    </script>
@endsection
