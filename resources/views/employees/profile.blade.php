@extends('layouts.dashboard')

@section('title', 'Employee Profile')

@section('page-title', 'Employee Profile')
@section('page-subtitle', 'Detailed employee information and history')

@section('sidebar')
    @if(auth()->user()->isDean())
        @include('partials.dean-sidebar')
    @else
        @include('partials.coordinator-sidebar')
    @endif
@endsection

@section('content')
    <!-- Back Button -->
    <div class="mb-5">
        @if(auth()->user()->isDean())
            <a href="{{ route('dean.employees', $employee->user->status === 'Inactive' ? ['tab' => 'deactivated'] : []) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> {{ $employee->user->status === 'Inactive' ? 'Back to Deactivated Accounts' : 'Back to Faculty Members' }}
            </a>
        @else
            <a href="{{ route('coordinator.faculty') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Faculty
            </a>
        @endif
    </div>

    <!-- Employee Basic Information -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Basic Information</h3>
            <div class="flex flex-wrap gap-2.5 items-center">
                @if(auth()->user()->isDeanOrSecretary() && $employee->user->role_id !== 1)
                    <a href="{{ route('dean.edit-employee', $employee->employee_id) }}" class="btn btn-primary py-2 px-5 text-sm">
                        <i class="fas fa-edit"></i> Edit Information
                    </a>
                    @if($employee->user->status === 'Active' && (int) $employee->user_id !== (int) auth()->id())
                    <form action="{{ route('dean.deactivate-employee', $employee->employee_id) }}" method="POST" class="m-0" id="deactivateAccountForm">
                        @csrf
                        <button type="button" class="btn btn-danger py-2 px-5 text-sm" onclick="confirmDeactivateAccount()">
                            <i class="fas fa-user-slash"></i> Deactivate Account
                        </button>
                    </form>
                    @elseif($employee->user->status === 'Inactive')
                    <form action="{{ route('dean.reactivate-employee', $employee->employee_id) }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-primary py-2 px-5 text-sm" style="background:#028a0f;border-color:#028a0f;">
                            <i class="fas fa-user-check"></i> Reactivate Account
                        </button>
                    </form>
                    @endif
                @elseif(auth()->user()->role_id === 2)
                    <a href="{{ route('coordinator.edit-faculty', $employee->employee_id) }}" class="btn btn-primary py-2 px-5 text-sm">
                        <i class="fas fa-edit"></i> Edit Information
                    </a>
                @endif
                <span class="badge {{ $employee->user->status === 'Active' ? 'badge-success' : 'badge-danger' }}">
                    {{ $employee->user->status }}
                </span>
            </div>
        </div>
        @if(auth()->user()->isDeanOrSecretary() && $employee->user->status === 'Inactive')
        <div class="mx-0 mb-0 mt-0 px-5 pb-4">
            <div class="bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-500 p-4 text-sm text-amber-900 dark:text-amber-200">
                <i class="fas fa-info-circle mr-1"></i>
                This account is <strong>inactive</strong>. The user cannot sign in or appear in task assignment and user search.
                Their uploaded documents and folders remain in the system for archiving and records.
            </div>
        </div>
        @endif
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 py-2.5">
            <div>
                <p class="text-gray-500 dark:text-gray-400 mb-1 text-sm">Employee Number</p>
                <p class="font-semibold text-base">{{ $employee->employee_no ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400 mb-1 text-sm">Full Name</p>
                <p class="font-semibold text-base">{{ $employee->full_name }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400 mb-1 text-sm">Department</p>
                <p class="font-semibold text-base">{{ $employee->department ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400 mb-1 text-sm">Position</p>
                <p class="font-semibold text-base">{{ $employee->position }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400 mb-1 text-sm">Role</p>
                <p class="font-semibold text-base">
                    <span class="badge badge-info">{{ $employee->user->role->role_name }}</span>
                </p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400 mb-1 text-sm">Hire Date</p>
                <p class="font-semibold text-base">{{ $employee->hire_date ? $employee->hire_date->format('M d, Y') : 'N/A' }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400 mb-1 text-sm">Years of Service</p>
                <p class="font-semibold text-base">
                    @if($employee->getYearsOfService() !== null)
                        {{ $employee->getYearsOfService() }} year(s)
                        @if($employee->getServiceMilestone())
                            <span class="milestone-badge milestone-{{ $employee->getServiceMilestone() }}">
                                <i class="fas fa-award"></i> {{ $employee->getServiceMilestone() }} Years
                            </span>
                        @endif
                    @else
                        N/A
                    @endif
                </p>
            </div>
        </div>
    </div>

    <!-- Account Information -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Account Information</h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 py-2.5">
            <div>
                <p class="text-gray-500 dark:text-gray-400 mb-1 text-sm">Username</p>
                <p class="font-semibold text-base">{{ $employee->user->username }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400 mb-1 text-sm">Email</p>
                <p class="font-semibold text-base">{{ $employee->user->email }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400 mb-1 text-sm">Account Created</p>
                <p class="font-semibold text-base">{{ $employee->user->created_at->format('M d, Y h:i A') }}</p>
            </div>
        </div>
    </div>

    @if(auth()->user()->isDean())
    <!-- Performance Reports -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Performance History</h3>
            <span class="badge badge-info">{{ $performanceReports->count() }} Reviews</span>
        </div>
        @if($performanceReports->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Evaluator</th>
                        <th>Rating</th>
                        <th>Remarks</th>
                        <th>Review Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($performanceReports as $report)
                    <tr>
                        <td><strong>{{ $report->evaluator->employee->full_name ?? $report->evaluator->username }}</strong></td>
                        <td>
                            <span class="badge {{ $report->rating >= 4 ? 'badge-success' : ($report->rating >= 3 ? 'badge-warning' : 'badge-danger') }}">
                                {{ $report->rating }}/5
                            </span>
                        </td>
                        <td>{{ $report->remarks ?? 'No remarks provided' }}</td>
                        <td>{{ $report->report_date->format('M d, Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="text-center py-10 text-gray-500 dark:text-gray-400">
                <i class="fas fa-chart-line text-5xl mb-4 opacity-50"></i>
                <p>No performance reviews yet</p>
            </div>
        @endif
    </div>
    @endif

    <!-- Submitted Documents -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Submitted Documents</h3>
            <span class="badge badge-info">{{ $documentStats['total'] }} Documents</span>
        </div>
        
        @if($documentStats['total'] > 0)
            <!-- Folder Filter Buttons -->
            <!-- Document Stats by Category -->
            <div class="flex flex-wrap gap-2.5 mb-5 p-2.5 bg-gray-100 dark:bg-gray-800">
                @foreach(($documentStats['byCategory'] ?? []) as $category => $count)
                    <div class="py-2 px-4 bg-[#028a0f] text-white text-sm">
                        <i class="fas fa-folder"></i> {{ $category }}: <strong>{{ $count }}</strong>
                    </div>
                @endforeach
            </div>
            <div class="p-4">
                @php $viewRoute = auth()->user()->isDean() ? 'dean.view-document' : 'coordinator.view-document'; @endphp
                @include('partials.faculty-document-tree', ['documentTree' => $documentTree ?? [], 'viewRoute' => $viewRoute])
            </div>
            @if(false)<table class="data-table">
                <thead>
                    <tr>
                        <th>Document Title</th>
                        <th>Folder</th>
                        <th>Type</th>
                        <th>File Name</th>
                        <th>Upload Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($documents as $document)
                    <tr class="document-row" data-folder="{{ $document->folder_id ?? 'uncategorized' }}">
                        <td><strong>{{ $document->document_title }}</strong></td>
                        <td>
                            @if($document->folder)
                                <span class="px-2 py-1 text-xs" style="background: {{ $document->folder->color }}20; color: {{ $document->folder->color }}; border: 1px solid {{ $document->folder->color }}">
                                    <i class="fas fa-folder"></i> {{ $document->folder->folder_name }}
                                </span>
                            @else
                                <span class="text-gray-500 text-xs">
                                    <i class="fas fa-folder-open"></i> Uncategorized
                                </span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-info">
                                {{ $document->document_type ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="font-mono text-xs">
                            {{ basename($document->file_path) }}
                        </td>
                        <td>{{ $document->created_at->format('M d, Y h:i A') }}</td>
                        <td>
                            <a href="{{ asset($document->file_path) }}" target="_blank" class="btn btn-primary py-1 px-2.5 text-xs mr-1">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="{{ asset($document->file_path) }}" download class="btn btn-success py-1 px-2.5 text-xs">
                                <i class="fas fa-download"></i> Download
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>@endif
        @else
            <div class="text-center py-10 text-gray-500 dark:text-gray-400">
                <i class="fas fa-folder-open text-5xl mb-4 opacity-50"></i>
                <p>No documents submitted yet</p>
            </div>
        @endif
    </div>

    <!-- Submitted Reports -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Submitted Reports</h3>
            <span class="badge badge-info">{{ $reportStats['total'] ?? 0 }} Reports</span>
        </div>
        
        @if(isset($reports) && $reports->count() > 0)
            <!-- Report Stats by Category -->
            <div class="flex flex-wrap gap-2.5 mb-5 p-2.5 bg-gray-100 dark:bg-gray-800">
                @foreach($reportStats['byCategory'] as $category => $count)
                    <div class="py-2 px-4 bg-[#028a0f] text-white text-sm">
                        <i class="fas fa-file-pdf"></i> {{ $category ?? 'Other' }}: <strong>{{ $count }}</strong>
                    </div>
                @endforeach
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Report Title</th>
                        <th>Category</th>
                        <th>File Name</th>
                        <th>Submission Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reports as $report)
                    <tr>
                        <td><strong>{{ $report->report_title }}</strong></td>
                        <td>
                            <span class="badge badge-warning">
                                {{ $report->report_category ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="font-mono text-xs">
                            {{ basename($report->file_path) }}
                        </td>
                        <td>{{ $report->created_at->format('M d, Y h:i A') }}</td>
                        <td>
                            <a href="{{ asset($report->file_path) }}" target="_blank" class="btn btn-primary py-1 px-2.5 text-xs mr-1">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="{{ asset($report->file_path) }}" download class="btn btn-success py-1 px-2.5 text-xs">
                                <i class="fas fa-download"></i> Download
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="text-center py-10 text-gray-500 dark:text-gray-400">
                <i class="fas fa-file-pdf text-5xl mb-4 opacity-50"></i>
                <p>No reports submitted yet</p>
            </div>
        @endif
    </div>

    <!-- Task History -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Task History</h3>
            <span class="badge badge-info">{{ $tasks->count() }} Tasks</span>
        </div>
        @if($tasks->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Task Title</th>
                        <th>Assigned By</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Created Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tasks as $task)
                    <tr>
                        <td><strong>{{ $task->task_title }}</strong></td>
                        <td>{{ $task->assignedBy->employee->full_name ?? $task->assignedBy->username }}</td>
                        <td>
                            {{ $task->due_date ? $task->due_date->format('M d, Y') : 'N/A' }}
                            @if($task->due_date && $task->due_date->isPast() && $task->status !== 'Completed')
                                <span class="badge badge-danger">Overdue</span>
                            @endif
                        </td>
                        <td>
                            @if($task->status === 'Completed')
                                <span class="badge badge-success">Completed</span>
                            @elseif($task->status === 'In Progress')
                                <span class="badge badge-warning">In Progress</span>
                            @else
                                <span class="badge badge-danger">Pending</span>
                            @endif
                        </td>
                        <td>{{ $task->created_at->format('M d, Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="text-center py-10 text-gray-500 dark:text-gray-400">
                <i class="fas fa-tasks text-5xl mb-4 opacity-50"></i>
                <p>No tasks assigned yet</p>
            </div>
        @endif
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-value">{{ $taskStats['total'] }}</div>
            <div class="stat-label">Total Tasks</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-value">{{ $taskStats['completed'] }}</div>
            <div class="stat-label">Completed Tasks</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-value">{{ $taskStats['pending'] }}</div>
            <div class="stat-label">Pending Tasks</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-folder"></i>
            </div>
            <div class="stat-value">{{ $documentStats['total'] }}</div>
            <div class="stat-label">Documents Submitted</div>
        </div>

        @if(auth()->user()->isDean() && $performanceReports->count() > 0)
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-value">{{ number_format($performanceReports->avg('rating'), 1) }}</div>
            <div class="stat-label">Average Rating</div>
        </div>
        @endif
    </div>

    @if(auth()->user()->isDeanOrSecretary() && config('employee.allow_hard_delete') && in_array((int) $employee->user->role_id, [2, 3], true) && (int) $employee->user_id !== (int) auth()->id())
    <div class="content-card account-delete-danger mt-6">
        <div class="card-header">
            <h3 class="card-title text-red-700 dark:text-red-400">
                <i class="fas fa-exclamation-triangle mr-2"></i> Dry-run cleanup — permanent delete
            </h3>
        </div>
        <div class="p-5 pt-0">
            <p class="text-sm text-gray-700 dark:text-gray-300 mb-4">
                <strong>Temporary feature.</strong> This removes the account and <strong>all</strong> related data:
                documents (including recycle bin), teaching guides, exam questionnaires, tasks, notifications,
                custom folders, announcements they authored, and activity tied to this user. <strong>This cannot be undone.</strong>
            </p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Prefer <strong>Deactivate</strong> if you only need to block login but keep files for archiving.
            </p>

            @error('confirm_username')
                <p class="text-red-600 text-sm mb-2">{{ $message }}</p>
            @enderror
            @error('confirm_phrase')
                <p class="text-red-600 text-sm mb-2">{{ $message }}</p>
            @enderror
            @error('error')
                <p class="text-red-600 text-sm mb-2">{{ $message }}</p>
            @enderror

            <form action="{{ route('dean.destroy-employee', $employee->employee_id) }}" method="POST" id="permanentDeleteForm" class="max-w-lg">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="confirm_username">Type username to confirm</label>
                    <input type="text" name="confirm_username" id="confirm_username" class="form-control"
                           value="{{ old('confirm_username') }}"
                           placeholder="{{ $employee->user->username }}" autocomplete="off" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm_phrase">Type DELETE PERMANENTLY</label>
                    <input type="text" name="confirm_phrase" id="confirm_phrase" class="form-control"
                           value="{{ old('confirm_phrase') }}"
                           placeholder="DELETE PERMANENTLY" autocomplete="off" required>
                </div>
                <button type="button" class="btn btn-danger" onclick="confirmPermanentDeleteAccount()">
                    <i class="fas fa-trash-alt"></i> Permanently delete account and all data
                </button>
            </form>
        </div>
    </div>
    @endif
@endsection

@push('scripts')
<script>
function confirmPermanentDeleteAccount() {
    var form = document.getElementById('permanentDeleteForm');
    if (!form) return;

    var expectedUser = @json($employee->user->username);
    var usernameInput = document.getElementById('confirm_username');
    var phraseInput = document.getElementById('confirm_phrase');

    if (usernameInput && usernameInput.value.trim() !== expectedUser) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ title: 'Username mismatch', text: 'Enter the exact username: ' + expectedUser, icon: 'error', confirmButtonColor: '#028a0f', customClass: { popup: 'swal-flat' } });
        } else {
            alert('Username does not match.');
        }
        return;
    }

    if (phraseInput && phraseInput.value.trim() !== 'DELETE PERMANENTLY') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ title: 'Confirmation required', text: 'Type DELETE PERMANENTLY in all caps.', icon: 'error', confirmButtonColor: '#028a0f', customClass: { popup: 'swal-flat' } });
        } else {
            alert('Type DELETE PERMANENTLY to confirm.');
        }
        return;
    }

    var message = 'This will permanently delete every file, task, and record for this person. This cannot be undone.';

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Permanently delete account?',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete everything',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            customClass: { popup: 'swal-flat' }
        }).then(function (result) {
            if (result.isConfirmed) form.submit();
        });
        return;
    }

    if (confirm(message)) {
        form.submit();
    }
}

function confirmDeactivateAccount() {
    var form = document.getElementById('deactivateAccountForm');
    if (!form) return;

    var message = 'Deactivate this account? They will not be able to sign in or be selected for new tasks. Uploaded documents and folders stay in the system for records.';

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Deactivate account?',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, deactivate',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            customClass: { popup: 'swal-flat' }
        }).then(function (result) {
            if (result.isConfirmed) form.submit();
        });
        return;
    }

    if (confirm(message)) {
        form.submit();
    }
}

function filterDocuments(folderId) {
    const rows = document.querySelectorAll('.document-row');
    const buttons = document.querySelectorAll('.folder-filter-btn');
    
    // Update active button
    buttons.forEach(btn => btn.classList.remove('active'));
    document.querySelector(`[data-folder="${folderId}"]`).classList.add('active');
    
    // Filter rows
    rows.forEach(row => {
        const rowFolder = row.getAttribute('data-folder');
        if (folderId === 'all') {
            row.style.display = '';
        } else if (folderId === 'uncategorized' && rowFolder === 'uncategorized') {
            row.style.display = '';
        } else if (rowFolder == folderId) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>
@endpush
