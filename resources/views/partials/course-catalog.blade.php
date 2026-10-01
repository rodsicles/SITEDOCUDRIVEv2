@php
    use App\Models\Course;
    use App\Services\CourseService;

    $routePrefix = $routePrefix ?? 'dean';
    $lockedDepartment = $lockedDepartment ?? null;
    $departments = $departments ?? CourseService::departments();
    $departmentFilter = $departmentFilter ?? 'all';
    $search = $search ?? null;
    $courses = $courses ?? collect();

    $indexRoute = $routePrefix . '.courses';
    $storeRoute = $routePrefix . '.courses.store';
    $updateRouteBase = url($routePrefix . '/courses');
    $destroyRoute = fn (Course $course) => route($routePrefix . '.courses.destroy', $course);
    $restoreRoute = fn (Course $course) => route($routePrefix . '.courses.restore', $course);

    if ($lockedDepartment) {
        $deptSlug = $deptSlug ?? CourseService::departmentToSlug($lockedDepartment);
        $deptLinks = [
            ['label' => 'All courses', 'value' => 'all'],
            ['label' => $lockedDepartment, 'value' => $deptSlug],
            ['label' => 'Inactive', 'value' => 'inactive'],
        ];
    } else {
        $deptLinks = [
            ['label' => 'All courses', 'value' => 'all'],
            ...collect(array_keys($departments))->map(fn ($code) => ['label' => $code, 'value' => strtolower($code)])->all(),
            ['label' => 'Inactive', 'value' => 'inactive'],
        ];
    }

    $dept = $departmentFilter;
    $hasUnitColumns = \Illuminate\Support\Facades\Schema::hasColumn('courses', 'lecture_units')
        && \Illuminate\Support\Facades\Schema::hasColumn('courses', 'lab_units');
@endphp

<div class="content-card mb-6">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>Add Course</h3>
    </div>
    <form action="{{ route($storeRoute) }}" method="POST" class="p-4">
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
            @if($lockedDepartment)
            <div class="form-group mb-0">
                <label class="form-label">Program</label>
                <input type="text" class="form-control bg-gray-100 dark:bg-gray-800" value="{{ $lockedDepartment }}" readonly disabled>
            </div>
            @else
            <div class="form-group mb-0">
                <label class="form-label">Program <span class="text-red-500">*</span></label>
                <select name="program" class="form-control" required>
                    @foreach($departments as $value => $label)
                        <option value="{{ $value }}" @selected(old('program', CourseService::slugToDepartment($departmentFilter)) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @endif
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 mb-3">
            @if($lockedDepartment)
                Faculty and coordinators in <strong>{{ $lockedDepartment }}</strong> will see this course when uploading to Teaching Guides or Exam Questionnaires.
            @else
                Faculty and coordinators in the selected program will see this course when uploading to Teaching Guides or Exam Questionnaires.
            @endif
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
            <form action="{{ route($indexRoute) }}" method="GET" class="flex items-center gap-2 w-full sm:w-auto">
                @if($dept !== 'all')
                    <input type="hidden" name="program" value="{{ $dept }}">
                @endif
                <input type="search" name="search" value="{{ $search ?? '' }}" class="form-control text-sm w-full sm:min-w-[220px]" placeholder="Search code or title...">
                <button type="submit" class="btn btn-primary text-sm whitespace-nowrap">
                    <i class="fas fa-search"></i>
                </button>
                @if($search)
                    <a href="{{ route($indexRoute, array_filter(['program' => $dept !== 'all' ? $dept : null])) }}" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </form>
            <span class="badge badge-info whitespace-nowrap">{{ $courses->count() }} shown</span>
        </div>
    </div>

    <div class="px-4 pb-3 flex flex-wrap gap-2 border-b border-gray-200 dark:border-gray-700">
        @foreach($deptLinks as $link)
            <a href="{{ route($indexRoute, array_filter(['program' => $link['value'], 'search' => $search ?? null])) }}"
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
                <th>Program</th>
                <th>Status</th>
                <th class="text-right">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($courses as $course)
            <tr>
                <td><strong>{{ $course->code }}</strong></td>
                <td>{{ $course->title }}</td>
                <td>{{ $course->program }}</td>
                <td>
                    @if($course->is_active)
                        <span class="badge badge-success">Active</span>
                    @else
                        <span class="badge badge-warning">Removed</span>
                    @endif
                </td>
                <td class="text-right course-action-cell">
                    <div class="course-action-wrap">
                        <button type="button"
                                class="course-catalog-actions-btn"
                                data-course-id="{{ $course->id }}"
                                aria-label="Actions for {{ $course->code }}"
                                aria-expanded="false"
                                aria-haspopup="true"
                                aria-controls="course-popover-{{ $course->id }}">
                            <i class="fas fa-ellipsis-v" aria-hidden="true"></i>
                        </button>
                        <div id="course-popover-{{ $course->id }}"
                             class="course-catalog-popover"
                             data-popover-id="{{ $course->id }}"
                             role="menu"
                             hidden>
                            <button type="button"
                                    class="course-catalog-popover-item course-catalog-view-btn"
                                    data-id="{{ $course->id }}"
                                    data-code="{{ $course->code }}"
                                    data-title="{{ $course->title }}"
                                    data-program="{{ $course->program }}"
                                    data-program-label="{{ \App\Models\Program::OPTIONS[$course->program] ?? ($course->program ?? '—') }}"
                                    data-status="{{ $course->is_active ? 'Active' : 'Removed' }}"
                                    data-year="{{ $course->year_level ? $course->year_level.' Year' : '—' }}"
                                    data-semester="{{ $course->semester ? (\App\Support\SchoolTerm::label($course->semester) ?: $course->semester) : '—' }}"
                                    data-lec="{{ $hasUnitColumns ? ($course->lecture_units ?? '—') : '' }}"
                                    data-lab="{{ $hasUnitColumns ? ($course->lab_units ?? '—') : '' }}"
                                    role="menuitem">
                                <i class="fas fa-eye text-xs" aria-hidden="true"></i> View details
                            </button>
                            <button type="button"
                                    class="course-catalog-popover-item course-catalog-rename-btn"
                                    data-id="{{ $course->id }}"
                                    data-code="{{ $course->code }}"
                                    data-title="{{ $course->title }}"
                                    role="menuitem">
                                <i class="fas fa-pen text-xs" aria-hidden="true"></i> Rename
                            </button>
                            @if($course->is_active)
                            <form action="{{ $destroyRoute($course) }}" method="POST"
                                  onsubmit="return confirm('Remove this course from faculty upload choices?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="course-catalog-popover-item course-catalog-action-remove" role="menuitem">
                                    <i class="fas fa-trash text-xs" aria-hidden="true"></i> Remove
                                </button>
                            </form>
                            @else
                            <form action="{{ $restoreRoute($course) }}" method="POST">
                                @csrf
                                <button type="submit" class="course-catalog-popover-item course-catalog-action-restore" role="menuitem">
                                    <i class="fas fa-undo text-xs" aria-hidden="true"></i> Restore
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
                        No courses available for this program. Add official courses when available.
                    @endif
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

{{-- Course details Modal --}}
<div id="courseDetailsModal" class="course-catalog-modal" aria-hidden="true">
    <div class="bg-white dark:bg-[#1e1e1e] rounded-lg shadow-xl max-w-lg w-full course-catalog-modal__panel" role="dialog" aria-modal="true" aria-labelledby="courseDetailsTitle">
        <div class="p-6">
            <div class="flex items-start justify-between gap-3 mb-4">
                <h3 id="courseDetailsTitle" class="text-lg font-bold text-gray-800 dark:text-white m-0">
                    <i class="fas fa-book-open mr-2 text-[var(--site-primary,#0d5c3b)]"></i>Course details
                </h3>
                <button type="button" id="courseDetailsClose" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm" aria-label="Close">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <dl class="course-details-grid">
                <div><dt>Code</dt><dd id="detailCode">—</dd></div>
                <div class="course-details-grid__wide"><dt>Title</dt><dd id="detailTitle">—</dd></div>
                <div><dt>Program</dt><dd id="detailProgram">—</dd></div>
                <div><dt>Status</dt><dd id="detailStatus">—</dd></div>
                <div><dt>Year level</dt><dd id="detailYear">—</dd></div>
                <div><dt>Semester</dt><dd id="detailSemester">—</dd></div>
                <div id="detailLecWrap" hidden><dt>Lecture units</dt><dd id="detailLec">—</dd></div>
                <div id="detailLabWrap" hidden><dt>Lab units</dt><dd id="detailLab">—</dd></div>
            </dl>
            <div class="flex justify-end mt-6">
                <button type="button" id="courseDetailsDone" class="btn btn-primary">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Rename Modal --}}
<div id="renameModal" class="course-catalog-modal" aria-hidden="true">
    <div class="bg-white dark:bg-[#1e1e1e] rounded-lg shadow-xl max-w-md w-full course-catalog-modal__panel">
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
    var detailsModal = document.getElementById('courseDetailsModal');
    var updateRouteBase = @json($updateRouteBase);

    function openModal(modal) {
        if (!modal) return;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    }

    function closePopover(popover, toggleBtn) {
        if (!popover) return;
        popover.classList.remove('is-open');
        popover.setAttribute('hidden', '');
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
    }

    function closeAllPopovers() {
        document.querySelectorAll('.course-catalog-popover').forEach(function (popover) {
            var id = popover.dataset.popoverId;
            var btn = document.querySelector('.course-catalog-actions-btn[data-course-id="' + id + '"]');
            closePopover(popover, btn);
        });
    }

    function openPopover(popover, toggleBtn) {
        closeAllPopovers();
        popover.classList.add('is-open');
        popover.removeAttribute('hidden');
        toggleBtn.setAttribute('aria-expanded', 'true');
    }

    document.querySelectorAll('.course-catalog-actions-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var id = btn.dataset.courseId;
            var popover = document.getElementById('course-popover-' + id);
            if (!popover) return;
            if (popover.classList.contains('is-open')) {
                closePopover(popover, btn);
            } else {
                openPopover(popover, btn);
            }
        });
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.course-action-wrap')) {
            closeAllPopovers();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeAllPopovers();
            closeModal(renameModal);
            closeModal(detailsModal);
        }
    });

    document.querySelectorAll('.course-catalog-view-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            closeAllPopovers();
            document.getElementById('detailCode').textContent = btn.dataset.code || '—';
            document.getElementById('detailTitle').textContent = btn.dataset.title || '—';
            document.getElementById('detailProgram').textContent = (btn.dataset.program || '')
                ? ((btn.dataset.programLabel || btn.dataset.program) + (btn.dataset.programLabel && btn.dataset.program ? ' (' + btn.dataset.program + ')' : ''))
                : '—';
            document.getElementById('detailStatus').textContent = btn.dataset.status || '—';
            document.getElementById('detailYear').textContent = btn.dataset.year || '—';
            document.getElementById('detailSemester').textContent = btn.dataset.semester || '—';

            var lecWrap = document.getElementById('detailLecWrap');
            var labWrap = document.getElementById('detailLabWrap');
            if (btn.dataset.lec !== undefined && btn.dataset.lec !== '') {
                document.getElementById('detailLec').textContent = btn.dataset.lec;
                document.getElementById('detailLab').textContent = btn.dataset.lab || '—';
                lecWrap.hidden = false;
                labWrap.hidden = false;
            } else {
                lecWrap.hidden = true;
                labWrap.hidden = true;
            }
            openModal(detailsModal);
        });
    });

    ['courseDetailsClose', 'courseDetailsDone'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el && detailsModal) {
            el.addEventListener('click', function () { closeModal(detailsModal); });
        }
    });
    if (detailsModal) {
        detailsModal.addEventListener('click', function (e) {
            if (e.target === detailsModal) closeModal(detailsModal);
        });
    }

    document.querySelectorAll('.course-catalog-rename-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            closeAllPopovers();
            document.getElementById('renameCode').value = btn.dataset.code;
            document.getElementById('renameTitle').value = btn.dataset.title;
            document.getElementById('renameForm').action = updateRouteBase + '/' + btn.dataset.id;
            openModal(renameModal);
        });
    });

    var cancelBtn = document.getElementById('renameModalCancel');
    if (cancelBtn && renameModal) {
        cancelBtn.addEventListener('click', function () {
            closeModal(renameModal);
        });
        renameModal.addEventListener('click', function (e) {
            if (e.target === renameModal) closeModal(renameModal);
        });
    }
});
</script>
