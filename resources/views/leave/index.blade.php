@extends('layouts.dashboard')

@section('title', 'Leave Records')

@section('page-title', 'Leave Documentation')
@section('page-subtitle', 'Log and track leave records')

@section('sidebar')
    @if(auth()->user()->isFaculty())
    @include('partials.faculty-sidebar')
    @elseif(auth()->user()->isProgramCoordinator())
    @include('partials.coordinator-sidebar')
    @else
    @include('partials.dean-sidebar')
    @endif
@endsection

@section('content')
    <!-- Leave Balance Cards -->
    <div class="stats-grid mb-8">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-medkit"></i>
            </div>
            <div class="stat-value">{{ $leaveBalance->getRemainingSickLeave() }}</div>
            <div class="stat-label">Sick Leave Remaining</div>
            <small class="text-gray-500 dark:text-gray-400 text-xs mt-1.5 block">
                Used: {{ $leaveBalance->sick_leave_used }} / {{ $leaveBalance->sick_leave_balance }} days
            </small>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-umbrella-beach"></i>
            </div>
            <div class="stat-value">{{ $leaveBalance->getRemainingVacationLeave() }}</div>
            <div class="stat-label">Vacation Leave Remaining</div>
            <small class="text-gray-500 dark:text-gray-400 text-xs mt-1.5 block">
                Used: {{ $leaveBalance->vacation_leave_used }} / {{ $leaveBalance->vacation_leave_balance }} days
            </small>
        </div>
    </div>

    <!-- Leave Records Table -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">
                @if(auth()->user()->isFaculty())
                    My Leave Records
                @else
                    All Leave Records
                @endif
            </h3>
            <div class="flex gap-2.5">
                <a href="{{ route('leave.calendar') }}" class="btn btn-primary">
                    <i class="fas fa-calendar"></i> Calendar View
                </a>
                <a href="{{ route('leave.create') }}" class="btn btn-success">
                    <i class="fas fa-plus"></i> Log Leave
                </a>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    @if(!auth()->user()->isFaculty())
                    <th>Employee</th>
                    @endif
                    <th>Leave Type</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Days</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leaveRequests as $leave)
                <tr>
                    @if(!auth()->user()->isFaculty())
                    <td>{{ $leave->user->employee->full_name ?? $leave->user->username }}</td>
                    @endif
                    <td>{{ $leave->leave_type }}</td>
                    <td>{{ $leave->start_date->format('M d, Y') }}</td>
                    <td>{{ $leave->end_date->format('M d, Y') }}</td>
                    <td>{{ $leave->days_count }} day(s)</td>
                    <td><small>{{ Str::limit($leave->reason, 50) }}</small></td>
                    <td>
                        <span class="badge badge-success">Logged</span>
                    </td>
                    <td>
                        @if($leave->user_id === auth()->id())
                            <a href="{{ route('leave.edit', $leave->leave_id) }}" class="btn bg-blue-600 text-white py-1.5 px-2.5 text-xs mr-1.5">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <button class="btn btn-danger py-1.5 px-2.5 text-xs"
                                    onclick="deleteLeave({{ $leave->leave_id }})">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        @else
                            <small class="text-gray-500 dark:text-gray-400">—</small>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-gray-500 dark:text-gray-400">
                        No leave records found
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-5">
            {{ $leaveRequests->links() }}
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-card max-w-md">
            <div class="p-6">
                <div class="flex items-start gap-4 mb-6">
                    <div class="flex-shrink-0 w-12 h-12 bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-2xl text-red-600 dark:text-red-400"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Delete Leave Record</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm mb-3">
                            Are you sure you want to delete this leave record? Your leave balance will be restored.
                        </p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-[#1f1f1f] border-t border-gray-200 dark:border-gray-700 flex gap-3 justify-end">
                <button type="button" onclick="closeDeleteModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600">
                    Cancel
                </button>
                <button type="button" onclick="confirmDelete()"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 flex items-center gap-2">
                    <i class="fas fa-trash"></i>
                    Yes, Delete
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    let pendingDeleteId = null;

    function deleteLeave(leaveId) {
        pendingDeleteId = leaveId;
        document.getElementById('deleteModal').classList.add('active');
    }

    function confirmDelete() {
        fetch(`/leave/${pendingDeleteId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            },
        })
        .then(response => {
            if (response.ok) {
                location.reload();
            } else {
                alert('Failed to delete leave record');
                closeDeleteModal();
            }
        })
        .catch(error => {
            alert('An error occurred');
            closeDeleteModal();
        });
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('active');
        pendingDeleteId = null;
    }

    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (document.getElementById('deleteModal').classList.contains('active')) {
                closeDeleteModal();
            }
        }
    });
</script>
@endpush
