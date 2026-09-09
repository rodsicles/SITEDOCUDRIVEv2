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
                           maxlength="20" placeholder="e.g. SITE-IT-FAC001">
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

            {{-- Course / Subject Assignment --}}
            @if(isset($courses) && $courses->isNotEmpty())
            <div class="form-group">
                <label class="form-label">Assigned Courses / Subjects</label>
                <small class="text-xs text-gray-500 dark:text-gray-400 block mb-2">
                    Update the subjects this faculty member handles.
                </small>
                <div class="course-picker-wrap">
                    <div class="course-picker-toolbar">
                        <input type="text" id="editFacultyCourseSearch" class="course-search-input"
                               placeholder="Search by code or title..." autocomplete="off">
                        <span class="course-selected-count" id="editFacultySelectedCount">0 selected</span>
                        <button type="button" class="course-picker-clear" id="editFacultyCourseClear">Clear</button>
                    </div>
                    <div class="course-picker-body">
                        <div class="course-checkbox-grid" id="editFacultyCourseGrid">
                            @foreach($courses as $course)
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
                        <p class="course-no-results" id="editFacultyNoResults">No matching courses.</p>
                    </div>
                </div>
            </div>
            @endif

            <div class="flex gap-4 mt-6">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Information
                </button>
                <a href="{{ route('coordinator.faculty-profile', $employee->employee_id) }}" class="btn bg-gray-600 hover:bg-gray-700 text-white">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>

        @if(isset($courses) && $courses->isNotEmpty())
        <script>
        (function() {
            const gridEl   = document.getElementById('editFacultyCourseGrid');
            const searchEl = document.getElementById('editFacultyCourseSearch');
            const countEl  = document.getElementById('editFacultySelectedCount');
            const noResEl  = document.getElementById('editFacultyNoResults');
            const clearEl  = document.getElementById('editFacultyCourseClear');

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

@endsection
