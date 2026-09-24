@extends('layouts.dashboard')

@section('title', 'Employee Management - Dean')

@section('page-title', 'Employee Management')
@section('page-subtitle', 'Manage all employee accounts')

@section('sidebar')
    @include('partials.dean-sidebar')
@endsection

@section('content')
<div class="ui-page ui-page--employees">
    <!-- Tab Navigation -->
    <div class="mb-6">
        <div class="ui-segmented" role="tablist" aria-label="Employee management">
            <button type="button" class="tab-button" onclick="switchTab('list')" id="listTab" role="tab" aria-selected="false">
                <i class="fas fa-users"></i> Employee Directory
            </button>
            <button type="button" class="tab-button" onclick="switchTab('createCoord')" id="createCoordTab" role="tab" aria-selected="false">
                <i class="fas fa-user-tie"></i> Create Coordinator
            </button>
            <button type="button" class="tab-button" onclick="switchTab('createFaculty')" id="createFacultyTab" role="tab" aria-selected="false">
                <i class="fas fa-user-plus"></i> Create Faculty
            </button>
            <button type="button" class="tab-button" onclick="switchTab('deactivated')" id="deactivatedTab" role="tab" aria-selected="false">
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
                        <th>Program</th>
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
                        <td>{{ \App\Models\Program::OPTIONS[$employee->program] ?? ($employee->program ?? 'N/A') }}</td>
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
    <div class="tab-content" id="deactivatedContent" hidden>
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
                        <th>Program</th>
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
                        <td>{{ \App\Models\Program::OPTIONS[$employee->program] ?? ($employee->program ?? 'N/A') }}</td>
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
    <div class="tab-content" id="createCoordContent" hidden>
        <div class="content-card employee-account-card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Create coordinator account</h3>
                    <p class="employee-account-card__intro">Enter employee details and set their sign-in credentials.</p>
                </div>
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

            <form action="{{ route('dean.store-coordinator') }}" method="POST" class="account-form">
                @csrf
                <input type="hidden" name="_form" value="coordinator">

                <div class="account-form__grid">
                    <div class="account-form__col">
                        <div class="ui-form-section">
                            <h4 class="ui-form-section__title">Employee details</h4>
                            <div class="form-group">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="full_name" class="form-control" placeholder="Enter full name" required maxlength="45" value="{{ old('_form') === 'coordinator' ? old('full_name') : '' }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Program *</label>
                                <select id="coordinatorDepartment" name="program" class="form-control" required>
                                    <option value="">Select Program</option>
                                    @foreach(\App\Models\Program::labels() as $code => $label)<option value="{{ $code }}" @selected(old('program', $employee->program ?? null) === $code)>{{ $label }}</option>@endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Employee Number</label>
                                <input type="text" id="coordinatorEmployeeNo" class="form-control bg-gray-100 dark:bg-gray-800" value="" placeholder="Select program first" readonly disabled>
                                <small class="text-xs text-gray-500 dark:text-gray-400 mt-1">Auto-generated per program (e.g. SITE-IT-COOR001). Existing numbers are not changed.</small>
                            </div>
                        </div>
                    </div>
                    <div class="account-form__col">
                        <div class="ui-form-section">
                            <h4 class="ui-form-section__title">Account access</h4>
                            <div class="form-group">
                                <label class="form-label">Username *</label>
                                <input type="text" name="username" class="form-control" autocomplete="off" autocapitalize="none" spellcheck="false" placeholder="Choose a username" required maxlength="20" value="{{ old('_form') === 'coordinator' ? old('username') : '' }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Password *</label>
                                <input type="password" name="password" class="form-control" autocomplete="new-password" placeholder="Minimum 8 characters" required minlength="8" maxlength="40">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="account-form__courses form-group" id="coordCourseSection" hidden>
                    <label class="form-label">Assigned Courses / Subjects</label>
                    <small class="text-xs text-gray-500 dark:text-gray-400 block mb-2">Select the subjects this coordinator will handle. Defaults to the current school term.</small>
                    <div class="course-assignment-guide" data-course-guide="coordCourses" data-default-term="{{ \App\Support\SchoolTerm::current() }}">
                        <div class="course-guide-bar">
                            <div class="course-guide-bar__term">
                                <label class="course-guide-label" for="coordCourseTerm">Current term</label>
                                <select id="coordCourseTerm" class="form-control course-guide-term" data-guide-term>
                                    @foreach(\App\Support\SchoolTerm::labels() as $value => $label)
                                        <option value="{{ $value }}" @selected($value === \App\Support\SchoolTerm::current())>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="course-guide-bar__years" role="group" aria-label="Year level filter">
                                <span class="course-guide-label">Year</span>
                                <div class="course-guide-year-chips">
                                    <button type="button" class="course-guide-chip is-active" data-guide-year="" aria-pressed="true">All</button>
                                    @for($y = 1; $y <= 4; $y++)
                                        <button type="button" class="course-guide-chip" data-guide-year="{{ $y }}" aria-pressed="false">{{ $y }}Y</button>
                                    @endfor
                                </div>
                            </div>
                            <label class="course-guide-unlock">
                                <input type="checkbox" data-guide-unlock>
                                <span>Also show courses from other terms</span>
                            </label>
                        </div>
                        <div class="course-picker-wrap">
                            <div class="course-picker-toolbar">
                                <input type="text" id="coordCourseSearch" class="course-search-input" data-guide-search placeholder="Search by code or title..." autocomplete="off">
                                <span class="course-selected-count" id="coordSelectedCount" data-guide-count>0 selected</span>
                                <button type="button" class="course-picker-clear" id="coordCourseClear" data-guide-clear title="Clear selection">Clear</button>
                            </div>
                            <div class="course-picker-body">
                                <div id="coordCourseList" class="course-checkbox-grid" data-guide-grid>
                                    <span class="course-section-empty">Select a program first.</span>
                                </div>
                                <p class="course-no-results" id="coordNoResults" data-guide-empty>No matching courses for this term filter.</p>
                            </div>
                        </div>
                    </div>
                    <p id="coordCourseError" class="text-xs text-red-600 dark:text-red-400 mt-1 hidden"></p>
                </div>

                <div class="account-form__actions">
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
    <div class="tab-content" id="createFacultyContent" hidden>
        <div class="content-card employee-account-card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Create faculty account</h3>
                    <p class="employee-account-card__intro">Enter employee details, set sign-in credentials, and assign subjects.</p>
                </div>
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

            <form action="{{ route('dean.store-faculty') }}" method="POST" class="account-form">
                @csrf
                <input type="hidden" name="_form" value="faculty">

                <div class="account-form__grid">
                    <div class="account-form__col">
                        <div class="ui-form-section">
                            <h4 class="ui-form-section__title">Employee details</h4>
                            <div class="form-group">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="full_name" class="form-control" placeholder="Enter full name" required maxlength="45" value="{{ old('_form') === 'faculty' ? old('full_name') : '' }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Program *</label>
                                <select id="facultyDepartment" name="program" class="form-control" required>
                                    <option value="">Select Program</option>
                                    @foreach(\App\Models\Program::labels() as $code => $label)<option value="{{ $code }}" @selected(old('program', $employee->program ?? null) === $code)>{{ $label }}</option>@endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="facultyType">Faculty Type *</label>
                                <select id="facultyType" name="faculty_type" class="form-control" required>
                                    <option value="">Select Faculty Type</option>
                                    @foreach(\App\Models\Employee::FACULTY_TYPES as $value => $label)
                                        <option value="{{ $value }}" @selected(old('_form') === 'faculty' && old('faculty_type') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <small class="text-xs text-gray-500 dark:text-gray-400 mt-1">Shared faculty keeps one home program while teaching assigned subjects.</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Employee Number</label>
                                <input type="text" id="facultyEmployeeNo" class="form-control bg-gray-100 dark:bg-gray-800" value="" placeholder="Select program first" readonly disabled>
                                <small class="text-xs text-gray-500 dark:text-gray-400 mt-1">Auto-generated per program (e.g. SITE-IT-FAC001). Existing numbers are not changed.</small>
                            </div>
                        </div>
                    </div>
                    <div class="account-form__col">
                        <div class="ui-form-section">
                            <h4 class="ui-form-section__title">Account access</h4>
                            <div class="form-group">
                                <label class="form-label">Username *</label>
                                <input type="text" name="username" class="form-control" autocomplete="off" autocapitalize="none" spellcheck="false" placeholder="Choose a username" required maxlength="20" value="{{ old('_form') === 'faculty' ? old('username') : '' }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Password *</label>
                                <input type="password" name="password" class="form-control" autocomplete="new-password" placeholder="Minimum 8 characters" required minlength="8" maxlength="40">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="account-form__courses form-group" id="facultyCourseSection" hidden>
                    <label class="form-label">Assigned Courses / Subjects *</label>
                    <small class="text-xs text-gray-500 dark:text-gray-400 block mb-2">Select subjects for the current school term. Unlock to include other terms. At least one is required.</small>
                    <div class="course-assignment-guide" data-course-guide="facultyCourses" data-default-term="{{ \App\Support\SchoolTerm::current() }}">
                        <div class="course-guide-bar">
                            <div class="course-guide-bar__term">
                                <label class="course-guide-label" for="facultyCourseTerm">Current term</label>
                                <select id="facultyCourseTerm" class="form-control course-guide-term" data-guide-term>
                                    @foreach(\App\Support\SchoolTerm::labels() as $value => $label)
                                        <option value="{{ $value }}" @selected($value === \App\Support\SchoolTerm::current())>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="course-guide-bar__years" role="group" aria-label="Year level filter">
                                <span class="course-guide-label">Year</span>
                                <div class="course-guide-year-chips">
                                    <button type="button" class="course-guide-chip is-active" data-guide-year="" aria-pressed="true">All</button>
                                    @for($y = 1; $y <= 4; $y++)
                                        <button type="button" class="course-guide-chip" data-guide-year="{{ $y }}" aria-pressed="false">{{ $y }}Y</button>
                                    @endfor
                                </div>
                            </div>
                            <label class="course-guide-unlock">
                                <input type="checkbox" data-guide-unlock>
                                <span>Also show courses from other terms</span>
                            </label>
                        </div>
                        <div class="course-picker-wrap">
                            <div class="course-picker-toolbar">
                                <input type="text" id="facultyCourseSearch" class="course-search-input" data-guide-search placeholder="Search by code or title..." autocomplete="off">
                                <span class="course-selected-count" id="facultySelectedCount" data-guide-count>0 selected</span>
                                <button type="button" class="course-picker-clear" id="facultyCourseClear" data-guide-clear title="Clear selection">Clear</button>
                            </div>
                            <div class="course-picker-body">
                                <div id="facultyCourseList" class="course-checkbox-grid" data-guide-grid>
                                    <span class="course-section-empty">Select a program first.</span>
                                </div>
                                <p class="course-no-results" id="facultyNoResults" data-guide-empty>No matching courses for this term filter.</p>
                            </div>
                        </div>
                    </div>
                    <p id="facultyCourseError" class="text-xs text-red-600 dark:text-red-400 mt-1 hidden"></p>
                </div>

                <div class="account-form__actions">
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

    </div>

    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(c => c.hidden = true);
            document.querySelectorAll('.tab-button').forEach(b => {
                b.classList.remove('is-active');
                b.setAttribute('aria-selected', 'false');
            });

            const tabMap = {
                list: { content: 'listContent', button: 'listTab' },
                createCoord: { content: 'createCoordContent', button: 'createCoordTab' },
                createFaculty: { content: 'createFacultyContent', button: 'createFacultyTab' },
                deactivated: { content: 'deactivatedContent', button: 'deactivatedTab' },
            };

            const t = tabMap[tabName];
            if (t) {
                document.getElementById(t.content).hidden = false;
                const btn = document.getElementById(t.button);
                btn.classList.add('is-active');
                btn.setAttribute('aria-selected', 'true');
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
            if (!document.querySelector('.tab-button.is-active')) {
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
                noInput.placeholder = 'Select program first';
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

        // ── Course Assignment AJAX + guided term filter (Dean create forms) ──
        const coursesByDeptUrl = @json(route('dean.courses.by-program'));

        function loadCourses(dept, guideRoot, sectionEl, errorEl) {
            if (!dept) {
                sectionEl.hidden = true;
                return;
            }
            const grid = guideRoot.querySelector('[data-guide-grid]');
            if (grid) {
                grid.innerHTML = '<span class="course-section-empty"><i class="fas fa-spinner fa-spin mr-1"></i>Loading courses...</span>';
            }
            sectionEl.hidden = false;
            errorEl.classList.add('hidden');
            window.CourseAssignmentGuide.bindRoot(guideRoot);
            fetch(coursesByDeptUrl + '?dept=' + encodeURIComponent(dept))
                .then(function (res) { return res.json(); })
                .then(function (courses) {
                    window.CourseAssignmentGuide.renderCourses(guideRoot, courses, []);
                })
                .catch(function () {
                    if (grid) {
                        grid.innerHTML = '<span class="course-section-empty" style="color:#dc2626">Failed to load courses. Please try again.</span>';
                    }
                });
        }

        document.getElementById('facultyDepartment')?.addEventListener('change', function() {
            loadCourses(
                this.value,
                document.querySelector('[data-course-guide="facultyCourses"]'),
                document.getElementById('facultyCourseSection'),
                document.getElementById('facultyCourseError')
            );
        });

        document.getElementById('coordinatorDepartment')?.addEventListener('change', function() {
            loadCourses(
                this.value,
                document.querySelector('[data-course-guide="coordCourses"]'),
                document.getElementById('coordCourseSection'),
                document.getElementById('coordCourseError')
            );
        });

        document.querySelector('form[action*="store-faculty"]')?.addEventListener('submit', function(e) {
            const section = document.getElementById('facultyCourseSection');
            if (section && !section.hidden) {
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
    @include('partials.course-assignment-guide-script')
@endsection
