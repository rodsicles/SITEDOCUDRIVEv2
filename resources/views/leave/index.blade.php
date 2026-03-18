@extends('layouts.dashboard')

@section('title', 'Leave Requests')

@section('page-title', 'Leave Management')
@section('page-subtitle', 'Manage leave requests and view leave balance')

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
    <!-- Leave Balance Card (Faculty Only) -->
    @if(auth()->user()->isFaculty())
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
    @endif

    <!-- Leave Requests Table -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">
                @if(auth()->user()->isFaculty())
                    My Leave Requests
                @else
                    All Leave Requests
                @endif
            </h3>
            <div class="flex gap-2.5">
                <a href="{{ route('leave.calendar') }}" class="btn btn-primary">
                    <i class="fas fa-calendar"></i> Calendar View
                </a>
                @if(auth()->user()->isFaculty())
                <a href="{{ route('leave.create') }}" class="btn btn-success">
                    <i class="fas fa-plus"></i> File Leave Request
                </a>
                @endif
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
                        @if($leave->status === 'Pending')
                            <span class="badge badge-warning">Pending</span>
                        @elseif($leave->status === 'Approved')
                            <span class="badge badge-success">Approved</span>
                        @elseif($leave->status === 'Cancelled')
                            <span class="badge badge-secondary">Cancelled</span>
                        @else
                            <span class="badge badge-danger">Rejected</span>
                        @endif
                    </td>
                    <td>
                        @if($leave->isPending() && auth()->user()->isFaculty() && $leave->user_id === auth()->id())
                            <!-- Faculty can edit/cancel their own pending requests -->
                            <a href="{{ route('leave.edit', $leave->leave_id) }}" class="btn bg-blue-600 hover:bg-blue-700 text-white py-1.5 px-2.5 text-xs mr-1.5">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <button class="btn btn-danger py-1.5 px-2.5 text-xs" 
                                    onclick="cancelLeave({{ $leave->leave_id }})">
                                <i class="fas fa-times-circle"></i> Cancel
                            </button>
                        @elseif($leave->isPending() && (auth()->user()->isProgramCoordinator() || auth()->user()->isDean()))
                            <!-- Coordinators/Dean approve or reject -->
                            <button class="btn btn-success py-1.5 px-2.5 text-xs mr-1.5" 
                                    onclick="approveLeave({{ $leave->leave_id }})">
                                <i class="fas fa-check"></i> Approve
                            </button>
                            <button class="btn btn-danger py-1.5 px-2.5 text-xs" 
                                    onclick="openRejectModal({{ $leave->leave_id }})">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        @elseif($leave->isRejected())
                            <small class="text-gray-500 dark:text-gray-400">{{ $leave->review_notes }}</small>
                        @elseif($leave->isApproved())
                            <small class="text-gray-500 dark:text-gray-400">
                                Approved by {{ $leave->reviewer->username ?? 'N/A' }}
                            </small>
                        @elseif($leave->isCancelled())
                            <small class="text-gray-500 dark:text-gray-400">Cancelled by user</small>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-gray-500 dark:text-gray-400">
                        No leave requests found
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-5">
            {{ $leaveRequests->links() }}
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="search-modal" id="rejectModal" style="align-items: center;">
        <div class="search-modal-content max-w-md">
            <div class="p-5 border-b-2 border-gray-200 dark:border-gray-700">
                <h3 class="m-0">Reject Leave Request</h3>
            </div>
            <form id="rejectForm" method="POST" class="p-5">
                @csrf
                <div class="form-group">
                    <label class="form-label">Reason for Rejection *</label>
                    <textarea name="review_notes" class="form-control" rows="4" required 
                              placeholder="Please provide a reason for rejecting this leave request..."></textarea>
                </div>
                <div class="flex justify-end gap-2.5">
                    <button type="button" class="btn bg-gray-200 dark:bg-gray-700" onclick="closeRejectModal()">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">Reject Leave</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Approve Modal -->
    <div class="modal-overlay" id="approveModal">
        <div class="modal-card max-w-md">
            <div class="p-6">
                <div class="flex items-start gap-4 mb-6">
                    <div class="flex-shrink-0 w-12 h-12 bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                        <i class="fas fa-check-circle text-2xl text-green-600 dark:text-green-400"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Approve Leave Request</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">
                            Are you sure you want to approve this leave request? The employee will be notified of the approval.
                        </p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-[#1f1f1f] border-t border-gray-200 dark:border-gray-700 flex gap-3 justify-end">
                <button type="button" onclick="closeApproveModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                    Cancel
                </button>
                <button type="button" onclick="confirmApprove()"
                        class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-800 flex items-center gap-2">
                    <i class="fas fa-check"></i>
                    Yes, Approve
                </button>
            </div>
        </div>
    </div>

    <!-- Cancel Modal -->
    <div class="modal-overlay" id="cancelModal">
        <div class="modal-card max-w-md">
            <div class="p-6">
                <div class="flex items-start gap-4 mb-6">
                    <div class="flex-shrink-0 w-12 h-12 bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-2xl text-red-600 dark:text-red-400"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Cancel Leave Request</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm mb-3">
                            Are you sure you want to cancel this leave request?
                        </p>
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3">
                            <p class="text-red-700 dark:text-red-300 text-xs font-medium flex items-center gap-2">
                                <i class="fas fa-info-circle"></i>
                                This action cannot be undone.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-[#1f1f1f] border-t border-gray-200 dark:border-gray-700 flex gap-3 justify-end">
                <button type="button" onclick="closeCancelModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                    Keep Request
                </button>
                <button type="button" onclick="confirmCancel()"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-800 flex items-center gap-2">
                    <i class="fas fa-times"></i>
                    Yes, Cancel Request
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    let pendingLeaveId = null;

    function approveLeave(leaveId) {
        pendingLeaveId = leaveId;
        document.getElementById('approveModal').classList.add('active');
    }

    function confirmApprove() {
        fetch(`/leave/${pendingLeaveId}/approve`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            },
        })
        .then(response => {
            if (response.ok) {
                location.reload();
            } else {
                alert('Failed to approve leave request');
                closeApproveModal();
            }
        })
        .catch(error => {
            alert('An error occurred while approving the leave request');
            closeApproveModal();
        });
    }

    function closeApproveModal() {
        document.getElementById('approveModal').classList.remove('active');
        pendingLeaveId = null;
    }

    function cancelLeave(leaveId) {
        pendingLeaveId = leaveId;
        document.getElementById('cancelModal').classList.add('active');
    }

    function confirmCancel() {
        fetch(`/leave/${pendingLeaveId}/cancel`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            },
        })
        .then(response => {
            if (response.ok) {
                location.reload();
            } else {
                alert('Failed to cancel leave request');
                closeCancelModal();
            }
        })
        .catch(error => {
            alert('An error occurred while canceling the leave request');
            closeCancelModal();
        });
    }

    function closeCancelModal() {
        document.getElementById('cancelModal').classList.remove('active');
        pendingLeaveId = null;
    }

    function openRejectModal(leaveId) {
        document.getElementById('rejectForm').action = `/leave/${leaveId}/reject`;
        document.getElementById('rejectModal').classList.add('active');
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').classList.remove('active');
    }

    // Close modal on outside click
    document.getElementById('rejectModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeRejectModal();
        }
    });

    document.getElementById('approveModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeApproveModal();
        }
    });

    document.getElementById('cancelModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeCancelModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (document.getElementById('rejectModal').classList.contains('active')) {
                closeRejectModal();
            }
            if (document.getElementById('approveModal').classList.contains('active')) {
                closeApproveModal();
            }
            if (document.getElementById('cancelModal').classList.contains('active')) {
                closeCancelModal();
            }
        }
    });
</script>
@endpush
