@extends('layouts.dashboard')

@section('title', 'Course Catalog - Dean')

@section('page-title', 'Course Catalog')
@section('page-subtitle', 'Manage ITE and Engineering courses for faculty uploads')

@section('sidebar')
    @include('partials.dean-sidebar')
@endsection

@section('content')
    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger mb-4">{{ session('error') }}</div>
    @endif

    <div class="content-card mb-6">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>Add Course</h3>
        </div>
        <form action="{{ route('dean.courses.store') }}" method="POST" class="p-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div class="form-group mb-0">
                    <label class="form-label">Course Code <span class="text-red-500">*</span></label>
                    <input type="text" name="code" class="form-control" placeholder="e.g. ITE127" value="{{ old('code') }}" required maxlength="20">
                </div>
                <div class="form-group mb-0 md:col-span-2">
                    <label class="form-label">Course Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" class="form-control" placeholder="Full course title" value="{{ old('title') }}" required maxlength="150">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Department <span class="text-red-500">*</span></label>
                    <select name="department" class="form-control" required>
                        @foreach($departments as $value => $label)
                            <option value="{{ $value }}" @selected(old('department') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 mb-3">
                Faculty and coordinators in the selected department will see this course when uploading to Teaching Guides or Exam Questionnaires.
            </p>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Course
            </button>
        </form>
    </div>

    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">All Courses</h3>
            <span class="badge badge-info">{{ $courses->count() }} total</span>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Title</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($courses as $course)
                <tr>
                    <td><strong>{{ $course->code }}</strong></td>
                    <td>{{ $course->title }}</td>
                    <td>{{ $course->department }}</td>
                    <td>
                        @if($course->is_active)
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-warning">Removed</span>
                        @endif
                    </td>
                    <td>
                        @if($course->is_active)
                        <form action="{{ route('dean.courses.destroy', $course) }}" method="POST" class="inline" onsubmit="return confirm('Remove this course from faculty upload choices?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger text-xs"><i class="fas fa-trash"></i> Remove</button>
                        </form>
                        @else
                        <form action="{{ route('dean.courses.restore', $course) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="btn btn-success text-xs"><i class="fas fa-undo"></i> Restore</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-gray-500 py-8">No courses yet. Run migrations and seed, or add courses above.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
