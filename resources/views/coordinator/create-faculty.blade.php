@extends('layouts.dashboard')

@section('title', 'Create Faculty Account')

@section('page-title', 'Add Faculty Member')
@section('page-subtitle', 'Create a new faculty employee account')

@section('sidebar')
    @include('partials.coordinator-sidebar')
@endsection

@section('content')
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Faculty Account Information</h3>
        </div>
        
        <form action="{{ route('coordinator.store-faculty') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" 
                       placeholder="Enter full name" required maxlength="45" value="{{ old('full_name') }}">
            </div>

            <div class="form-group">
                <label class="form-label">Department</label>
                <input type="text" class="form-control" value="{{ $dept ?? auth()->user()->employee->department ?? 'N/A' }}" disabled>
                <input type="hidden" name="department" value="{{ $dept ?? auth()->user()->employee->department }}">
                <small class="text-xs text-gray-500 dark:text-gray-400 mt-1">Auto-assigned to your department</small>
            </div>

            <div class="form-group">
                <label class="form-label">Employee Number</label>
                <input type="text" class="form-control bg-gray-100 dark:bg-gray-800" value="{{ $nextFacultyNo }}" readonly>
                <small class="text-xs text-gray-500 dark:text-gray-400 mt-1">Auto-generated for your department (e.g. SITE-IT-FAC001, SITE-ENGR-FAC001).</small>
            </div>

            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" 
                       placeholder="Enter username" required maxlength="20" value="{{ old('username') }}">
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" 
                       placeholder="Enter password (min 8 characters)" required minlength="8" maxlength="40">
            </div>

            {{-- Course / Subject Assignment --}}
            @if($courses->isNotEmpty())
            <div class="form-group">
                <label class="form-label">Assigned Courses / Subjects *</label>
                <small class="text-xs text-gray-500 dark:text-gray-400 block mb-2">
                    Select the subjects this faculty member will teach in <strong>{{ $dept }}</strong>.
                    At least one course must be selected.
                </small>
                <div class="course-picker-wrap">
                    <div class="course-picker-toolbar">
                        <input type="text" id="createFacultyCourseSearch" class="course-search-input"
                               placeholder="Search by code or title..." autocomplete="off">
                        <span class="course-selected-count" id="createFacultySelectedCount">0 selected</span>
                        <button type="button" class="course-picker-clear" id="createFacultyCourseClear">Clear</button>
                    </div>
                    <div class="course-picker-body">
                        <div class="course-checkbox-grid" id="createFacultyCourseGrid">
                            @foreach($courses as $course)
                            @php $oldIds = old('course_ids', []); $isChecked = in_array($course->id, (array)$oldIds); @endphp
                            <label class="course-checkbox-item {{ $isChecked ? 'selected' : '' }}">
                                <input type="checkbox" name="course_ids[]" value="{{ $course->id }}"
                                       {{ $isChecked ? 'checked' : '' }}>
                                <span><strong>{{ $course->code }}</strong> &ndash; {{ $course->title }}</span>
                            </label>
                            @endforeach
                        </div>
                        <p class="course-no-results" id="createFacultyNoResults">No matching courses.</p>
                    </div>
                </div>
                <p id="createFacultyCourseError" class="text-xs text-red-600 dark:text-red-400 mt-1 hidden"></p>
            </div>
            @endif

            <div class="flex gap-2.5">
                <button type="submit" class="btn btn-success" id="createFacultySubmitBtn">
                    <i class="fas fa-user-plus"></i> Create Faculty Account
                </button>
                <a href="{{ route('coordinator.faculty') }}" class="btn bg-gray-600 hover:bg-gray-700 text-white">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>

        @if($courses->isNotEmpty())
        <script>
        (function() {
            const gridEl    = document.getElementById('createFacultyCourseGrid');
            const searchEl  = document.getElementById('createFacultyCourseSearch');
            const countEl   = document.getElementById('createFacultySelectedCount');
            const noResEl   = document.getElementById('createFacultyNoResults');
            const clearEl   = document.getElementById('createFacultyCourseClear');

            // Selected-count helper
            function updateCount() {
                const n = gridEl ? gridEl.querySelectorAll('input:checked').length : 0;
                if (countEl) countEl.textContent = n + ' selected';
            }

            // Toggle .selected on each item + wire count
            if (gridEl) {
                gridEl.querySelectorAll('.course-checkbox-item').forEach(function(label) {
                    label.querySelector('input').addEventListener('change', function() {
                        label.classList.toggle('selected', this.checked);
                        updateCount();
                    });
                });
                updateCount();
            }

            // Live search / filter
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

            // Clear button
            if (clearEl && gridEl) {
                clearEl.addEventListener('click', function() {
                    gridEl.querySelectorAll('input:checked').forEach(function(cb) {
                        cb.checked = false;
                        cb.closest('.course-checkbox-item').classList.remove('selected');
                    });
                    updateCount();
                });
            }

            // Require at least one course before submitting
            document.querySelector('form[action="{{ route('coordinator.store-faculty') }}"]')
                ?.addEventListener('submit', function(e) {
                    const checked = gridEl ? gridEl.querySelectorAll('input:checked') : [];
                    if (checked.length === 0) {
                        e.preventDefault();
                        const err = document.getElementById('createFacultyCourseError');
                        err.textContent = 'Please assign at least one course to this faculty member.';
                        err.classList.remove('hidden');
                        err.scrollIntoView({ behavior: 'instant', block: 'center' });
                    }
                });
        })();
        </script>
        @endif
    </div>
@endsection
