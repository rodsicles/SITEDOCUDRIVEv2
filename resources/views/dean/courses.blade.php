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
        <div class="card-header flex-col sm:flex-row gap-3 items-start sm:items-center">
            <h3 class="card-title mb-0">All Courses</h3>
            <div class="flex flex-col sm:flex-row sm:items-center gap-3 w-full sm:w-auto sm:ml-auto">
                <form action="{{ route('dean.courses') }}" method="GET" class="flex items-center gap-2 w-full sm:w-auto">
                    @if(($departmentFilter ?? 'all') !== 'all')
                        <input type="hidden" name="department" value="{{ $departmentFilter }}">
                    @endif
                    <input type="search" name="search" value="{{ $search ?? '' }}" class="form-control text-sm w-full sm:min-w-[220px]" placeholder="Search code or title...">
                    <button type="submit" class="btn btn-primary text-sm whitespace-nowrap">
                        <i class="fas fa-search"></i>
                    </button>
                    @if($search ?? false)
                        <a href="{{ route('dean.courses', ['department' => $departmentFilter ?? 'all']) }}" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </form>
                <span class="badge badge-info whitespace-nowrap">{{ $courses->count() }} shown</span>
            </div>
        </div>

        <div class="px-4 pb-3 flex flex-wrap gap-2 border-b border-gray-200 dark:border-gray-700">
            @php
                $dept = $departmentFilter ?? 'all';
                $deptLinks = [
                    ['label' => 'All courses', 'value' => 'all'],
                    ['label' => 'Information Technology', 'value' => 'it'],
                    ['label' => 'Engineering', 'value' => 'engineering'],
                    ['label' => 'Inactive', 'value' => 'inactive'],
                ];
            @endphp
            @foreach($deptLinks as $link)
                <a href="{{ route('dean.courses', array_filter(['department' => $link['value'], 'search' => $search ?? null])) }}"
                   class="btn text-sm {{ $dept === $link['value'] ? ($link['value'] === 'inactive' ? 'btn-danger' : 'btn-primary') : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>

        <div class="course-catalog-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Title</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th class="text-right">Action</th>
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
                    <td class="text-right course-action-cell">
                        <div class="course-action-wrap">
                            <button type="button" class="course-menu-toggle" data-course-id="{{ $course->id }}" aria-label="Course actions" aria-expanded="false" aria-haspopup="true">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="course-menu-dropdown" data-menu-id="{{ $course->id }}" role="menu">
                                <button type="button"
                                        class="course-rename-btn"
                                        data-id="{{ $course->id }}"
                                        data-code="{{ $course->code }}"
                                        data-title="{{ $course->title }}"
                                        role="menuitem">
                                    <i class="fas fa-pen text-xs"></i> Rename
                                </button>
                                @if($course->is_active)
                                <form action="{{ route('dean.courses.destroy', $course) }}" method="POST"
                                      onsubmit="return confirm('Remove this course from faculty upload choices?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="course-menu-remove" role="menuitem">
                                        <i class="fas fa-trash text-xs"></i> Remove
                                    </button>
                                </form>
                                @else
                                <form action="{{ route('dean.courses.restore', $course) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="course-menu-restore" role="menuitem">
                                        <i class="fas fa-undo text-xs"></i> Restore
                                    </button>
                                </form>
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-gray-500 py-8">
                        @if($dept === 'inactive')
                            No inactive courses.
                        @else
                            No courses yet. Run migrations and seed, or add courses above.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    {{-- Rename Modal --}}
    <div id="renameModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 p-4">
        <div class="bg-white dark:bg-[#1e1e1e] rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">
                    <i class="fas fa-pen mr-2 text-blue-500"></i>Rename Course
                </h3>
                <form id="renameForm" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-semibold text-gray-600 dark:text-gray-300 block mb-1">Course Code</label>
                            <input type="text" name="code" id="renameCode" class="form-control" required maxlength="20">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-600 dark:text-gray-300 block mb-1">Course Title</label>
                            <input type="text" name="title" id="renameTitle" class="form-control" required maxlength="150">
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" id="renameModalCancel"
                                class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i> Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var renameModal = document.getElementById('renameModal');

        function closeAllMenus() {
            document.querySelectorAll('.course-menu-dropdown').forEach(function (m) {
                m.classList.remove('is-open');
            });
            document.querySelectorAll('.course-menu-toggle').forEach(function (btn) {
                btn.setAttribute('aria-expanded', 'false');
            });
        }

        function openMenu(menu, toggleBtn) {
            closeAllMenus();
            menu.classList.add('is-open');
            toggleBtn.setAttribute('aria-expanded', 'true');
        }

        document.querySelectorAll('.course-menu-toggle').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var id = btn.dataset.courseId;
                var menu = document.querySelector('.course-menu-dropdown[data-menu-id="' + id + '"]');
                if (!menu) return;
                if (menu.classList.contains('is-open')) {
                    closeAllMenus();
                } else {
                    openMenu(menu, btn);
                }
            });
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('.course-action-wrap')) {
                closeAllMenus();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeAllMenus();
                if (renameModal) renameModal.classList.remove('is-open');
            }
        });

        document.querySelectorAll('.course-rename-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                closeAllMenus();
                document.getElementById('renameCode').value = btn.dataset.code;
                document.getElementById('renameTitle').value = btn.dataset.title;
                document.getElementById('renameForm').action = '/dean/courses/' + btn.dataset.id;
                if (renameModal) renameModal.classList.add('is-open');
            });
        });

        var cancelBtn = document.getElementById('renameModalCancel');
        if (cancelBtn && renameModal) {
            cancelBtn.addEventListener('click', function () {
                renameModal.classList.remove('is-open');
            });
            renameModal.addEventListener('click', function (e) {
                if (e.target === renameModal) renameModal.classList.remove('is-open');
            });
        }
    });
    </script>
@endsection
