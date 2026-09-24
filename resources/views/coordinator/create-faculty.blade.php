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
        
        <form action="{{ route('coordinator.store-faculty') }}" method="POST" class="account-form">
            @csrf

            <div class="account-form__grid">
                <div class="account-form__col">
                    <div class="ui-form-section">
                        <h4 class="ui-form-section__title">Identity</h4>
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control"
                                   placeholder="Enter full name" required maxlength="45" value="{{ old('full_name') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Program</label>
                            <input type="text" class="form-control" value="{{ $dept ?? auth()->user()->employee->program ?? 'N/A' }}" disabled>
                            <input type="hidden" name="program" value="{{ $dept ?? auth()->user()->employee->program }}">
                            <small class="text-xs text-gray-500 dark:text-gray-400 mt-1">Auto-assigned to your program</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="facultyType">Faculty Type</label>
                            <select id="facultyType" name="faculty_type" class="form-control" required>
                                <option value="">Select Faculty Type</option>
                                @foreach(\App\Models\Employee::FACULTY_TYPES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('faculty_type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <small class="text-xs text-gray-500 dark:text-gray-400 mt-1">Shared faculty remains assigned to your home program.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Employee Number</label>
                            <input type="text" class="form-control bg-gray-100 dark:bg-gray-800" value="{{ $nextFacultyNo }}" readonly>
                            <small class="text-xs text-gray-500 dark:text-gray-400 mt-1">Auto-generated for your program.</small>
                        </div>
                    </div>
                </div>
                <div class="account-form__col">
                    <div class="ui-form-section">
                        <h4 class="ui-form-section__title">Credentials</h4>
                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control"
                                   placeholder="Enter username" required maxlength="20" value="{{ old('username') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control"
                                   placeholder="Minimum 8 characters" required minlength="8" maxlength="40">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Course / Subject Assignment --}}
            @if($courses->isNotEmpty())
            <div class="account-form__courses form-group">
                <label class="form-label">Assigned Courses / Subjects *</label>
                @include('partials.course-assignment-picker', [
                    'pickerId' => 'createFaculty',
                    'courses' => $courses,
                    'selectedIds' => old('course_ids', []),
                    'required' => true,
                    'hint' => 'Select subjects for <strong>'.e($dept).'</strong>. Defaults to the current school term; unlock to include other terms. At least one course is required.',
                ])
                <p id="createFacultyCourseError" class="text-xs text-red-600 dark:text-red-400 mt-1 hidden"></p>
            </div>
            @endif

            <div class="account-form__actions">
                <button type="submit" class="btn btn-success" id="createFacultySubmitBtn">
                    <i class="fas fa-user-plus"></i> Create Faculty Account
                </button>
                <a href="{{ route('coordinator.faculty') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>

        @if($courses->isNotEmpty())
        @include('partials.course-assignment-guide-script')
        <script>
        document.querySelector('form[action="{{ route('coordinator.store-faculty') }}"]')
            ?.addEventListener('submit', function(e) {
                const grid = document.querySelector('[data-course-guide="createFaculty"] [data-guide-grid]');
                const checked = grid ? grid.querySelectorAll('input:checked') : [];
                if (checked.length === 0) {
                    e.preventDefault();
                    const err = document.getElementById('createFacultyCourseError');
                    err.textContent = 'Please assign at least one course to this faculty member.';
                    err.classList.remove('hidden');
                    err.scrollIntoView({ behavior: 'instant', block: 'center' });
                }
            });
        </script>
        @endif
    </div>
@endsection
