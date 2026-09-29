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
        @include('partials.account-create-form', [
            'formKey' => 'coordinator',
            'action' => route('dean.store-coordinator'),
            'coursesUrl' => route('dean.courses.by-program'),
            'numberPreview' => $coordinatorNumberPreview ?? [],
            'title' => 'Create coordinator account',
            'intro' => 'Enter employee details, set sign-in credentials, and assign subjects.',
            'submitLabel' => 'Create Coordinator Account',
            'submitIcon' => 'fa-user-tie',
            'cancelOnclick' => "switchTab('list')",
            'numberExample' => 'SITE-IT-COOR001',
        ])
    </div>

    <!-- Tab 3: Create Faculty -->
    <div class="tab-content" id="createFacultyContent" hidden>
        @include('partials.account-create-form', [
            'formKey' => 'faculty',
            'action' => route('dean.store-faculty'),
            'coursesUrl' => route('dean.courses.by-program'),
            'numberPreview' => $facultyNumberPreview ?? [],
            'title' => 'Create faculty account',
            'intro' => 'Enter employee details, set sign-in credentials, and assign subjects.',
            'submitLabel' => 'Create Faculty Account',
            'submitIcon' => 'fa-user-plus',
            'cancelOnclick' => "switchTab('list')",
            'numberExample' => 'SITE-IT-FAC001',
        ])
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

        // Reopen the form that failed validation so its restored input and subjects are visible.
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

        // Prevent double submit
        document.querySelectorAll('form[data-account-form]').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (e.defaultPrevented) return;
                const btn = this.querySelector('button[type="submit"]');
                if (btn && !btn.disabled) {
                    btn.dataset.original = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
                    setTimeout(() => {
                        btn.disabled = false;
                        btn.innerHTML = btn.dataset.original;
                    }, 5000);
                }
            });
        });
    </script>
    @include('partials.course-assignment-guide-script')
@endsection
