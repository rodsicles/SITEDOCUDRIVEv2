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
                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="form-group">
                        <label class="form-label" for="facultyFirstName">First name *</label>
                        <input id="facultyFirstName" type="text" name="first_name" class="form-control" value="{{ old('first_name', $employee->first_name) }}" maxlength="45" autocomplete="given-name" @if($employee->first_name) required @endif>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="facultyMiddleName">Middle name (optional)</label>
                        <input id="facultyMiddleName" type="text" name="middle_name" class="form-control" value="{{ old('middle_name', $employee->middle_name) }}" maxlength="45" autocomplete="additional-name">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="facultyLastName">Last name *</label>
                        <input id="facultyLastName" type="text" name="last_name" class="form-control" value="{{ old('last_name', $employee->last_name) }}" maxlength="45" autocomplete="family-name" @if($employee->last_name) required @endif>
                    </div>
                    @if(!$employee->first_name || !$employee->last_name)
                        <p class="md:col-span-3 text-xs text-gray-600 dark:text-gray-400">Current name: {{ $employee->full_name }}. These parts were not recorded before. Leave them blank to keep this name, or enter first and last name to replace it.</p>
                    @endif
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
                    <label class="form-label" for="editFacultyProgram">Program *</label>
                    <select id="editFacultyProgram" name="program" class="form-control" required>
                        @foreach(\App\Models\Program::labels() as $code => $programLabel)
                            <option value="{{ $code }}" @selected(old('program', $employee->program) === $code)>{{ $programLabel }}</option>
                        @endforeach
                    </select>
                    <small class="modern-help-text">
                        <i class="fas fa-info-circle"></i> Changing the program reloads the subject list below.
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label">Position</label>
                    <input type="text" name="position" class="form-control"
                           value="{{ old('position', $employee->position) }}"
                           maxlength="100" placeholder="e.g. Faculty Employee">
                </div>

                <div class="form-group">
                    <label class="form-label">Faculty Type *</label>
                    <select name="faculty_type" class="form-control" required>
                        @foreach(\App\Models\Employee::FACULTY_TYPES as $value => $typeLabel)
                            <option value="{{ $value }}" @selected(old('faculty_type', $employee->faculty_type ?? 'full_time') === $value)>{{ $typeLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Hire date</label>
                    <input type="date" name="hire_date" class="form-control"
                           value="{{ old('hire_date', optional($employee->hire_date)->format('Y-m-d')) }}">
                    <small class="text-gray-600 dark:text-gray-400 text-xs mt-1.5 block">
                        Years of service updates from this date.
                    </small>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Username (Read-only)</label>
                <input type="text" class="form-control" 
                       value="{{ $employee->user->username }}" disabled>
                <small class="text-gray-600 dark:text-gray-400 text-xs mt-1.5 block">Username cannot be changed</small>
            </div>

            @php $editProgram = old('program', $employee->program); @endphp
            @include('partials.course-assignment-picker', [
                'pickerId' => 'editFaculty',
                'program' => $editProgram,
                'courses' => $editProgram === $employee->program ? $courses : null,
                'selectedIds' => old('course_ids', $assignedCourseIds ?? []),
                'programSelect' => 'editFacultyProgram',
                'coursesUrl' => route('coordinator.courses.by-program'),
                'hint' => 'Update the subjects this faculty member handles. Defaults to the current school term; unlock to include other terms.',
            ])

            <div class="flex gap-4 mt-6">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Information
                </button>
                <a href="{{ route('coordinator.faculty-profile', $employee->employee_id) }}" class="btn bg-gray-600 hover:bg-gray-700 text-white">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>

        @include('partials.course-assignment-guide-script')
    </div>

@endsection
