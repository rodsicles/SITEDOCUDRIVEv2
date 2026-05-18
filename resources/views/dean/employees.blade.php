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
        </div>
    </div>

    <!-- Tab 1: Employee Directory -->
    <div class="tab-content active" id="listContent">
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Employee Directory</h3>
                <span class="badge badge-info">{{ $employees->total() }} Total</span>
            </div>

            @if(session('success'))
                <div class="alert alert-success">
                    <strong><i class="fas fa-check-circle"></i> Success!</strong>
                    <p>{{ session('success') }}</p>
                </div>
            @endif

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Employee No.</th>
                        <th>Full Name</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Role</th>
                        <th>Action</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                    <tr>
                        <td><strong>{{ $employee->employee_no ?? 'N/A' }}</strong></td>
                        <td>{{ $employee->full_name }}</td>
                        <td>{{ $employee->department ?? 'N/A' }}</td>
                        <td>{{ $employee->position ?? 'N/A' }}</td>
                        <td>
                            <span class="badge badge-info">{{ $employee->user->role->role_name }}</span>
                        </td>
                        <td>
                            <a href="{{ route('dean.employee-profile', $employee->employee_id) }}" class="btn btn-primary text-xs">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                        <td>
                            @if($employee->user->status === 'Active')
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">Inactive</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-gray-500 dark:text-gray-400">No employees found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-5">{{ $employees->links() }}</div>
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
            };

            const t = tabMap[tabName];
            if (t) {
                document.getElementById(t.content).style.display = 'block';
                const btn = document.getElementById(t.button);
                btn.style.color = 'var(--color-primary)';
                btn.style.borderBottomColor = 'var(--color-primary)';
                btn.style.background = 'rgba(2, 138, 15, 0.1)';
            }
        }

        // Auto-open correct tab on validation errors
        @if($errors->any() && old('_form') === 'coordinator')
            document.addEventListener('DOMContentLoaded', () => switchTab('createCoord'));
        @elseif($errors->any() && old('_form') === 'faculty')
            document.addEventListener('DOMContentLoaded', () => switchTab('createFaculty'));
        @endif

        // Default tab
        document.addEventListener('DOMContentLoaded', function() {
            if (!document.querySelector('.tab-button[style*="color"]')) {
                switchTab('list');
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
    </script>
@endsection
