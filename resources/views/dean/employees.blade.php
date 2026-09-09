@extends('layouts.dashboard')

@section('title', 'Employee Management - Dean')

@section('page-title', 'Employee Management')
@section('page-subtitle', 'Manage all employee accounts')

@section('sidebar')
    @include('partials.dean-sidebar')
@endsection

@section('content')
    <!-- Tab Navigation -->
    <div class="mb-6">
        <div class="flex gap-2 border-b-2 border-gray-200 dark:border-gray-700">
            <button class="tab-button inline-flex items-center gap-2 px-5 py-3.5 bg-transparent border-0 border-b-[3px] border-transparent text-gray-600 dark:text-gray-400 text-sm font-semibold cursor-pointer" onclick="switchTab('list')" id="listTab">
                <i class="fas fa-users"></i> Employee Directory
            </button>
            <button class="tab-button inline-flex items-center gap-2 px-5 py-3.5 bg-transparent border-0 border-b-[3px] border-transparent text-gray-600 dark:text-gray-400 text-sm font-semibold cursor-pointer" onclick="switchTab('createCoord')" id="createCoordTab">
                <i class="fas fa-user-tie"></i> Create Coordinator
            </button>
            <button class="tab-button inline-flex items-center gap-2 px-5 py-3.5 bg-transparent border-0 border-b-[3px] border-transparent text-gray-600 dark:text-gray-400 text-sm font-semibold cursor-pointer" onclick="switchTab('createFaculty')" id="createFacultyTab">
                <i class="fas fa-user-plus"></i> Create Faculty
            </button>
            <button class="tab-button inline-flex items-center gap-2 px-5 py-3.5 bg-transparent border-0 border-b-[3px] border-transparent text-gray-600 dark:text-gray-400 text-sm font-semibold cursor-pointer" onclick="switchTab('deactivated')" id="deactivatedTab">
                <i class="fas fa-user-slash"></i> Deactivated Accounts
                @if(($deactivatedEmployees->total() ?? 0) > 0)
                <span class="badge badge-danger text-[10px] py-0.5 px-1.5">{{ $deactivatedEmployees->total() }}</span>
                @endif
            </button>
        </div>
    </div>

    <!-- Tab 1: Employee Directory -->
    <div class="tab-content active" id="listContent">
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Employee Directory</h3>
                <span class="badge badge-info">{{ $employees->total() }} Active</span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 px-4 -mt-2 mb-3">Active faculty and coordinators only. Deactivated accounts are listed under <strong>Deactivated Accounts</strong>.</p>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Employee No.</th>
                        <th>Full Name</th>
                        <th>Department</th>
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                    <tr>
                        <td><strong>{{ $employee->employee_no ?? 'N/A' }}</strong></td>
                        <td>
                            <span class="inline-block w-2 h-2 rounded-full mr-1 {{ optional($employee->user)->isOnline() ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}"
                                  title="{{ optional($employee->user)->isOnline() ? 'Online' : 'Offline' }}"></span>
                            {{ $employee->full_name }}
                        </td>
                        <td>{{ $employee->department ?? 'N/A' }}</td>
                        <td>
                            <span class="badge badge-info">{{ $employee->user->role->role_name ?? ($employee->position ?? 'N/A') }}</span>
                        </td>
                        <td>
                            <a href="{{ route('dean.employee-profile', $employee->employee_id) }}" class="btn btn-primary text-xs">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-gray-500 dark:text-gray-400">No active employees found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-5">{{ $employees->links() }}</div>
        </div>
    </div>

    <!-- Tab: Deactivated Accounts -->
    <div class="tab-content" id="deactivatedContent" style="display: none;">
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Deactivated Accounts</h3>
                <span class="badge badge-danger">{{ $deactivatedEmployees->total() }} Deactivated</span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 px-4 -mt-2 mb-3">These accounts cannot sign in. Open a profile and use <strong>Reactivate Account</strong> to restore access.</p>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Employee No.</th>
                        <th>Full Name</th>
                        <th>Department</th>
                        <th>Role</th>
                        <th>Action</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deactivatedEmployees as $employee)
                    <tr>
                        <td><strong>{{ $employee->employee_no ?? 'N/A' }}</strong></td>
                        <td>{{ $employee->full_name }}</td>
                        <td>{{ $employee->department ?? 'N/A' }}</td>
                        <td>
                            <span class="badge badge-info">{{ $employee->user->role->role_name ?? ($employee->position ?? 'N/A') }}</span>
                        </td>
                        <td>
                            <a href="{{ route('dean.employee-profile', $employee->employee_id) }}" class="btn btn-primary text-xs">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                        <td>
                            <span class="badge badge-danger">Inactive</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-gray-500 dark:text-gray-400">No deactivated accounts</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-5">{{ $deactivatedEmployees->appends(['tab' => 'deactivated'])->links() }}</div>
        </div>
    </div>

    <!-- Tab 2: Create Coordinator -->
    <div class="tab-content" id="createCoordContent" style="display: none;">
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Coordinator Account Information</h3>
            </div>

            @if($errors->any() && session('_form') === 'coordinator')
                <div class="alert alert-error">
                    <strong><i class="fas fa-exclamation-circle"></i> Validation Errors:</strong>
                    <ul class="mt-2 ml-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('dean.store-coordinator') }}" method="POST">
                @csrf
                <input type="hidden" name="_form" value="coordinator">

                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" placeholder="Enter full name" required maxlength="45" value="{{ old('_form') === 'coordinator' ? old('full_name') : '' }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Department *</label>
                    <select id="coordinatorDepartment" name="department" class="form-control" required>
                        <option value="">Select Department</option>
                        <option value="Engineering" {{ (old('_form') === 'coordinator' && old('department') == 'Engineering') ? 'selected' : '' }}>Engineering</option>
                        <option value="Information Technology" {{ (old('_form') === 'coordinator' && old('department') == 'Information Technology') ? 'selected' : '' }}>Information Technology</option>
                    </select>
                </div>

                {{-- Course Assignment (loaded via AJAX when dept is chosen) --}}
                <div class="form-group" id="coordCourseSection" style="display:none">
                    <label class="form-label">Assigned Courses / Subjects</label>
                    <small class="text-xs text-gray-500 dark:text-gray-400 block mb-2">Select the subjects this coordinator will handle.</small>
                    <div class="course-picker-wrap">
                        <div class="course-picker-toolbar">
                            <input type="text" id="coordCourseSearch" class="course-search-input" placeholder="Search by code or title..." autocomplete="off">
                            <span class="course-selected-count" id="coordSelectedCount">0 selected</span>
                            <button type="button" class="course-picker-clear" id="coordCourseClear" title="Clear selection">Clear</button>
                        </div>
                        <div class="course-picker-body">
                            <div id="coordCourseList" class="course-checkbox-grid">
                                <span class="course-section-empty">Select a department first.</span>
                            </div>
                            <p class="course-no-results" id="coordNoResults">No matching courses.</p>
                        </div>
                    </div>
                    <p id="coordCourseError" class="text-xs text-red-600 dark:text-red-400 mt-1 hidden"></p>
                </div>

                <div class="form-group">
                    <label class="form-label">Employee Number</label>
                    <input type="text" id="coordinatorEmployeeNo" class="form-control bg-gray-100 dark:bg-gray-800" value="" placeholder="Select department first" readonly disabled>
                    <small class="text-xs text-gray-500 dark:text-gray-400 mt-1">Auto-generated per department (e.g. SITE-IT-COOR001, SITE-ENGR-COOR001). Existing numbers are not changed.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" class="form-control" placeholder="Enter username" required maxlength="20" value="{{ old('_form') === 'coordinator' ? old('username') : '' }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Password *</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter password (min 8 characters)" required minlength="8" maxlength="40">
                </div>

                <div class="flex gap-2.5">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-user-tie"></i> Create Coordinator Account
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="switchTab('list')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tab 3: Create Faculty -->
    <div class="tab-content" id="createFacultyContent" style="display: none;">
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Faculty Account Information</h3>
            </div>

            @if($errors->any() && session('_form') === 'faculty')
                <div class="alert alert-error">
                    <strong><i class="fas fa-exclamation-circle"></i> Validation Errors:</strong>
                    <ul class="mt-2 ml-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('dean.store-faculty') }}" method="POST">
                @csrf
                <input type="hidden" name="_form" value="faculty">

                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" placeholder="Enter full name" required maxlength="45" value="{{ old('_form') === 'faculty' ? old('full_name') : '' }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Department *</label>
                    <select id="facultyDepartment" name="department" class="form-control" required>
                        <option value="">Select Department</option>
                        <option value="Engineering" {{ (old('_form') === 'faculty' && old('department') == 'Engineering') ? 'selected' : '' }}>Engineering</option>
                        <option value="Information Technology" {{ (old('_form') === 'faculty' && old('department') == 'Information Technology') ? 'selected' : '' }}>Information Technology</option>
                    </select>
                </div>

                {{-- Course Assignment (loaded via AJAX when dept is chosen) --}}
                <div class="form-group" id="facultyCourseSection" style="display:none">
                    <label class="form-label">Assigned Courses / Subjects *</label>
                    <small class="text-xs text-gray-500 dark:text-gray-400 block mb-2">Select the subjects this faculty member will teach. At least one is required.</small>
                    <div class="course-picker-wrap">
                        <div class="course-picker-toolbar">
                            <input type="text" id="facultyCourseSearch" class="course-search-input" placeholder="Search by code or title..." autocomplete="off">
                            <span class="course-selected-count" id="facultySelectedCount">0 selected</span>
                            <button type="button" class="course-picker-clear" id="facultyCourseClear" title="Clear selection">Clear</button>
                        </div>
                        <div class="course-picker-body">
                            <div id="facultyCourseList" class="course-checkbox-grid">
                                <span class="course-section-empty">Select a department first.</span>
                            </div>
                            <p class="course-no-results" id="facultyNoResults">No matching courses.</p>
                        </div>
                    </div>
                    <p id="facultyCourseError" class="text-xs text-red-600 dark:text-red-400 mt-1 hidden"></p>
                </div>

                <div class="form-group">
                    <label class="form-label">Employee Number</label>
                    <input type="text" id="facultyEmployeeNo" class="form-control bg-gray-100 dark:bg-gray-800" value="" placeholder="Select department first" readonly disabled>
                    <small class="text-xs text-gray-500 dark:text-gray-400 mt-1">Auto-generated per department (e.g. SITE-IT-FAC001, SITE-ENGR-FAC001). Existing numbers are not changed.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" class="form-control" placeholder="Enter username" required maxlength="20" value="{{ old('_form') === 'faculty' ? old('username') : '' }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Password *</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter password (min 8 characters)" required minlength="8" maxlength="40">
                </div>

                <div class="flex gap-2.5">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-user-plus"></i> Create Faculty Account
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="switchTab('list')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
            document.querySelectorAll('.tab-button').forEach(b => {
                b.style.color = '';
                b.style.borderBottomColor = '';
                b.style.background = '';
            });

            const tabMap = {
                list: { content: 'listContent', button: 'listTab' },
                createCoord: { content: 'createCoordContent', button: 'createCoordTab' },
                createFaculty: { content: 'createFacultyContent', button: 'createFacultyTab' },
                deactivated: { content: 'deactivatedContent', button: 'deactivatedTab' },
            };

            const t = tabMap[tabName];
            if (t) {
                document.getElementById(t.content).style.display = 'block';
                const btn = document.getElementById(t.button);
                btn.style.color = 'var(--color-primary)';
                btn.style.borderBottomColor = 'var(--color-primary)';
                btn.style.background = 'rgba(2, 138, 15, 0.1)';
            }

            const url = new URL(window.location.href);
            if (tabName === 'list') {
                url.searchParams.delete('tab');
            } else if (['deactivated', 'createCoord', 'createFaculty'].includes(tabName)) {
                url.searchParams.set('tab', tabName);
            }
            window.history.replaceState({}, '', url);
        }

        // Auto-open correct tab on validation errors
        @if($errors->any() && old('_form') === 'coordinator')
            document.addEventListener('DOMContentLoaded', () => switchTab('createCoord'));
        @elseif($errors->any() && old('_form') === 'faculty')
            document.addEventListener('DOMContentLoaded', () => switchTab('createFaculty'));
        @endif

        // Default tab (supports ?tab=deactivated after deactivation)
        document.addEventListener('DOMContentLoaded', function() {
            const initialTab = @json(request('tab', 'list'));
            const allowed = ['list', 'createCoord', 'createFaculty', 'deactivated'];
            if (!document.querySelector('.tab-button[style*="color"]')) {
                switchTab(allowed.includes(initialTab) ? initialTab : 'list');
            }
        });

        const employeeNumberPreview = {
            coordinator: @json($coordinatorNumberPreview ?? []),
            faculty: @json($facultyNumberPreview ?? []),
        };

        function updateEmployeeNumberPreview(formKey) {
            const deptSelect = document.getElementById(formKey + 'Department');
            const noInput = document.getElementById(formKey + 'EmployeeNo');
            if (!deptSelect || !noInput) return;

            const dept = deptSelect.value;
            const previews = employeeNumberPreview[formKey] || {};

            if (!dept || !previews[dept]) {
                noInput.value = '';
                noInput.placeholder = 'Select department first';
                noInput.disabled = true;
                return;
            }

            noInput.value = previews[dept];
            noInput.placeholder = '';
            noInput.disabled = false;
        }

        ['coordinator', 'faculty'].forEach(formKey => {
            const deptSelect = document.getElementById(formKey + 'Department');
            if (deptSelect) {
                deptSelect.addEventListener('change', () => updateEmployeeNumberPreview(formKey));
                updateEmployeeNumberPreview(formKey);
            }
        });

        // Prevent double submit
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const btn = this.querySelector('button[type="submit"]');
                if (btn && !btn.disabled) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
                    setTimeout(() => {
                        btn.disabled = false;
                        btn.innerHTML = btn.dataset.original || btn.innerHTML;
                    }, 5000);
                }
            });
        });

        // ── Course Assignment AJAX + Search (Dean create forms) ─────────────
        const coursesByDeptUrl = @json(route('dean.courses.by-department'));

        /**
         * Attach live search + selected-count + clear-button to a course picker.
         * @param {string} searchId  - id of the <input type="text"> search field
         * @param {string} gridId    - id of the .course-checkbox-grid element
         * @param {string} countId   - id of the .course-selected-count element
         * @param {string} noResId   - id of the .course-no-results element
         * @param {string} clearId   - id of the Clear button
         */
        function attachCourseSearch(searchId, gridId, countId, noResId, clearId) {
            const searchEl = document.getElementById(searchId);
            const gridEl   = document.getElementById(gridId);
            const countEl  = document.getElementById(countId);
            const noResEl  = document.getElementById(noResId);
            const clearEl  = document.getElementById(clearId);
            if (!searchEl || !gridEl) return;

            function updateCount() {
                if (!countEl) return;
                const n = gridEl.querySelectorAll('input[type="checkbox"]:checked').length;
                countEl.textContent = n + ' selected';
            }

            function filterItems() {
                const q = searchEl.value.trim().toLowerCase();
                let visible = 0;
                gridEl.querySelectorAll('.course-checkbox-item').forEach(function(item) {
                    const match = q === '' || item.textContent.toLowerCase().includes(q);
                    item.classList.toggle('course-hidden', !match);
                    if (match) visible++;
                });
                if (noResEl) {
                    noResEl.classList.toggle('visible',
                        visible === 0 && gridEl.querySelectorAll('.course-checkbox-item').length > 0
                    );
                }
            }

            searchEl.addEventListener('input', filterItems);

            if (clearEl) {
                clearEl.addEventListener('click', function() {
                    gridEl.querySelectorAll('input[type="checkbox"]:checked').forEach(function(cb) {
                        cb.checked = false;
                        cb.closest('.course-checkbox-item').classList.remove('selected');
                    });
                    updateCount();
                });
            }

            // Observe checkbox changes inside the grid (works for dynamically added items too)
            gridEl.addEventListener('change', updateCount);

            return { updateCount, filterItems };
        }

        function renderCourseCheckboxes(listEl, courses, selectedIds, searchId, countId, noResId, clearId) {
            listEl.innerHTML = '';
            if (!courses || courses.length === 0) {
                listEl.innerHTML = '<span class="course-section-empty">No courses found for this department.</span>';
                // Reset counter
                const countEl = document.getElementById(countId);
                if (countEl) countEl.textContent = '0 selected';
                return;
            }
            courses.forEach(c => {
                const isChecked = selectedIds && selectedIds.includes(c.id);
                const item = document.createElement('label');
                item.className = 'course-checkbox-item' + (isChecked ? ' selected' : '');
                item.innerHTML =
                    '<input type="checkbox" name="course_ids[]" value="' + c.id + '"' + (isChecked ? ' checked' : '') + '>' +
                    '<span><strong>' + c.code + '</strong> &ndash; ' + c.title + '</span>';
                item.querySelector('input').addEventListener('change', function() {
                    item.classList.toggle('selected', this.checked);
                });
                listEl.appendChild(item);
            });
            // Re-attach search behaviour (items were just rebuilt)
            const searcher = attachCourseSearch(searchId, listEl.id, countId, noResId, clearId);
            if (searcher) {
                // Clear search field and reset count
                const sEl = document.getElementById(searchId);
                if (sEl) { sEl.value = ''; }
                searcher.updateCount();
            }
        }

        async function loadCourses(dept, listEl, sectionEl, errorEl, searchId, countId, noResId, clearId) {
            if (!dept) {
                sectionEl.style.display = 'none';
                listEl.innerHTML = '';
                return;
            }
            listEl.innerHTML = '<span class="course-section-empty"><i class="fas fa-spinner fa-spin mr-1"></i>Loading courses...</span>';
            sectionEl.style.display = '';
            errorEl.classList.add('hidden');
            try {
                const res = await fetch(coursesByDeptUrl + '?dept=' + encodeURIComponent(dept));
                const courses = await res.json();
                renderCourseCheckboxes(listEl, courses, [], searchId, countId, noResId, clearId);
            } catch (e) {
                listEl.innerHTML = '<span class="course-section-empty" style="color:#dc2626">Failed to load courses. Please try again.</span>';
            }
        }

        // Faculty form
        document.getElementById('facultyDepartment')?.addEventListener('change', function() {
            loadCourses(
                this.value,
                document.getElementById('facultyCourseList'),
                document.getElementById('facultyCourseSection'),
                document.getElementById('facultyCourseError'),
                'facultyCourseSearch', 'facultySelectedCount', 'facultyNoResults', 'facultyCourseClear'
            );
        });

        // Coordinator form
        document.getElementById('coordinatorDepartment')?.addEventListener('change', function() {
            loadCourses(
                this.value,
                document.getElementById('coordCourseList'),
                document.getElementById('coordCourseSection'),
                document.getElementById('coordCourseError'),
                'coordCourseSearch', 'coordSelectedCount', 'coordNoResults', 'coordCourseClear'
            );
        });

        // Validate at least 1 course on faculty form submit
        document.querySelector('form[action*="store-faculty"]')?.addEventListener('submit', function(e) {
            const section = document.getElementById('facultyCourseSection');
            if (section && section.style.display !== 'none') {
                const checked = section.querySelectorAll('input[type="checkbox"]:checked');
                if (checked.length === 0) {
                    e.preventDefault();
                    const err = document.getElementById('facultyCourseError');
                    err.textContent = 'Please assign at least one course to this faculty member.';
                    err.classList.remove('hidden');
                    section.scrollIntoView({ behavior: 'instant', block: 'center' });
                }
            }
        });
        // ─────────────────────────────────────────────────────────────────────
    </script>
@endsection
