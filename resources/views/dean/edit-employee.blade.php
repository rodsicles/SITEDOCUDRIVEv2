@extends('layouts.dashboard')

@section('title', 'Edit Employee - Dean')

@section('page-title', 'Edit Employee')
@section('page-subtitle', 'Update employee details and reset password')

@section('sidebar')
    @include('partials.dean-sidebar')
@endsection

@section('content')
    <!-- Back Button -->
    <div class="mb-5">
        <a href="{{ route('dean.employee-profile', $employee->employee_id) }}" class="btn btn-secondary">
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

    <!-- Edit Employee Information -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Edit Employee Information</h3>
            <span class="badge badge-info">{{ $employee->user->role->role_name }}</span>
        </div>

        @if($employee->user->status === 'Inactive')
        <div class="bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-500 p-4 mb-5 text-sm text-amber-900 dark:text-amber-200">
            <i class="fas fa-info-circle mr-1"></i>
            Account is inactive. Reactivate from the employee profile before editing or resetting password.
        </div>
        @endif

        <form action="{{ route('dean.update-employee', $employee->employee_id) }}" method="POST">
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
                           maxlength="20" placeholder="e.g. SITE-IT-COOR001">
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address <span class="text-xs text-gray-500">(optional)</span></label>
                    <input type="email" name="email" class="form-control"
                           value="{{ old('email', $employee->user->email) }}"
                           maxlength="45" placeholder="employee@example.com">
                    <small class="text-gray-600 dark:text-gray-400 text-xs mt-1.5 block">
                        Optional contact field. Not used for password recovery (SMTP not yet configured).
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label">Department *</label>
                    <select name="department" class="form-control" required>
                        <option value="">Select Department</option>
                        <option value="Engineering" {{ old('department', $employee->department) === 'Engineering' ? 'selected' : '' }}>Engineering</option>
                        <option value="Information Technology" {{ old('department', $employee->department) === 'Information Technology' ? 'selected' : '' }}>Information Technology</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Username (Read-only)</label>
                <input type="text" class="form-control" value="{{ $employee->user->username }}" disabled>
                <small class="text-gray-600 dark:text-gray-400 text-xs mt-1.5 block">Username cannot be changed</small>
            </div>

            {{-- Course / Subject Assignment (Faculty & Coordinator only) --}}
            @if(isset($allCourses) && $allCourses->isNotEmpty())
            <div class="form-group">
                <label class="form-label">Assigned Courses / Subjects</label>
                <small class="text-xs text-gray-500 dark:text-gray-400 block mb-2">
                    Update which courses / subjects this employee handles.
                    Changing department above then saving will refresh courses on the next edit.
                </small>
                <div class="course-picker-wrap">
                    <div class="course-picker-toolbar">
                        <input type="text" id="editEmpCourseSearch" class="course-search-input"
                               placeholder="Search by code or title..." autocomplete="off">
                        <span class="course-selected-count" id="editEmpSelectedCount">0 selected</span>
                        <button type="button" class="course-picker-clear" id="editEmpCourseClear">Clear</button>
                    </div>
                    <div class="course-picker-body">
                        <div class="course-checkbox-grid" id="editEmpCourseGrid">
                            @foreach($allCourses as $course)
                            @php
                                $currentIds = old('course_ids', $assignedCourseIds ?? []);
                                $checked    = in_array($course->id, (array)$currentIds);
                            @endphp
                            <label class="course-checkbox-item {{ $checked ? 'selected' : '' }}">
                                <input type="checkbox" name="course_ids[]" value="{{ $course->id }}"
                                       {{ $checked ? 'checked' : '' }}>
                                <span><strong>{{ $course->code }}</strong> &ndash; {{ $course->title }}</span>
                            </label>
                            @endforeach
                        </div>
                        <p class="course-no-results" id="editEmpNoResults">No matching courses.</p>
                    </div>
                </div>
                <small class="text-xs text-gray-500 dark:text-gray-400 mt-1 block">
                    Leave all unchecked to remove course restrictions (shows all department courses).
                </small>
            </div>
            @endif

            <div class="flex gap-4 mt-6">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Information
                </button>
                <a href="{{ route('dean.employee-profile', $employee->employee_id) }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>

        @if(isset($allCourses) && $allCourses->isNotEmpty())
        <script>
        (function() {
            const gridEl   = document.getElementById('editEmpCourseGrid');
            const searchEl = document.getElementById('editEmpCourseSearch');
            const countEl  = document.getElementById('editEmpSelectedCount');
            const noResEl  = document.getElementById('editEmpNoResults');
            const clearEl  = document.getElementById('editEmpCourseClear');

            function updateCount() {
                const n = gridEl ? gridEl.querySelectorAll('input:checked').length : 0;
                if (countEl) countEl.textContent = n + ' selected';
            }

            if (gridEl) {
                gridEl.querySelectorAll('.course-checkbox-item').forEach(function(label) {
                    label.querySelector('input').addEventListener('change', function() {
                        label.classList.toggle('selected', this.checked);
                        updateCount();
                    });
                });
                updateCount();
            }

            if (searchEl && gridEl) {
                searchEl.addEventListener('input', function() {
                    const q = this.value.trim().toLowerCase();
                    let visible = 0;
                    gridEl.querySelectorAll('.course-checkbox-item').forEach(function(item) {
                        const match = q === '' || item.textContent.toLowerCase().includes(q);
                        item.classList.toggle('course-hidden', !match);
                        if (match) visible++;
                    });
                    if (noResEl) noResEl.classList.toggle('visible', visible === 0);
                });
            }

            if (clearEl && gridEl) {
                clearEl.addEventListener('click', function() {
                    gridEl.querySelectorAll('input:checked').forEach(function(cb) {
                        cb.checked = false;
                        cb.closest('.course-checkbox-item').classList.remove('selected');
                    });
                    updateCount();
                });
            }
        })();
        </script>
        @endif
    </div>

    <!-- Reset Password Section -->
    @if($employee->user->status === 'Active')
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Reset Password</h3>
        </div>

        <div class="bg-orange-50 dark:bg-orange-900/20 p-4 mb-5 border-l-4 border-orange-500">
            <p class="m-0 text-orange-800 dark:text-orange-400 text-sm">
                <i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong> Resetting the password will immediately change this employee's login credentials. Make sure to inform them of the new password.
            </p>
        </div>

        <form action="{{ route('dean.reset-password', $employee->employee_id) }}" method="POST" id="resetPasswordForm">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="form-group">
                    <label class="form-label">New Password *</label>
                    <input type="password" name="new_password" class="form-control"
                           required minlength="8" maxlength="40" placeholder="Enter new password">
                    <small class="text-gray-600 dark:text-gray-400 text-xs mt-1.5 block">Minimum 8 characters</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm New Password *</label>
                    <input type="password" name="new_password_confirmation" class="form-control"
                           required minlength="8" maxlength="40" placeholder="Confirm new password">
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
    @endif

    <script>
        function confirmPasswordReset() {
            if (confirm('Are you sure you want to reset the password for {{ $employee->full_name }}?\n\nThis action cannot be undone and will immediately change their login credentials.')) {
                document.getElementById('resetPasswordForm').submit();
            }
        }
    </script>
@endsection
