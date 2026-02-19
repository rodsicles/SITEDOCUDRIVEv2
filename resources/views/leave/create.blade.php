@extends('layouts.dashboard')

@section('title', 'File Leave Request')

@section('page-title', 'File Leave Request')
@section('page-subtitle', 'Submit a new leave request for approval')

@section('sidebar')
    <a href="{{ route('faculty.dashboard') }}" class="menu-item">
        <i class="fas fa-chart-line"></i> Dashboard
    </a>
    <a href="{{ route('faculty.tasks') }}" class="menu-item">
        <i class="fas fa-tasks"></i> My Tasks
    </a>
    <a href="{{ route('leave.index') }}" class="menu-item active">
        <i class="fas fa-calendar-alt"></i> Leave Requests
    </a>
    <a href="{{ route('calendar.index') }}" class="menu-item">
        <i class="fas fa-calendar"></i> Calendar
    </a>
    <a href="{{ route('faculty.documents') }}" class="menu-item">
        <i class="fas fa-folder"></i> Documents
    </a>
@endsection

@section('content')
    <!-- Leave Balance Info -->
    <div class="stats-grid mb-8">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-medkit"></i>
            </div>
            <div class="stat-value">{{ $leaveBalance->getRemainingSickLeave() }}</div>
            <div class="stat-label">Sick Leave Available</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-umbrella-beach"></i>
            </div>
            <div class="stat-value">{{ $leaveBalance->getRemainingVacationLeave() }}</div>
            <div class="stat-label">Vacation Leave Available</div>
        </div>
    </div>

    <!-- Leave Request Form -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">New Leave Request</h3>
            <a href="{{ route('leave.index') }}" class="btn bg-gray-200 dark:bg-gray-700">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <form action="{{ route('leave.store') }}" method="POST" class="p-5">
            @csrf

            <div class="form-group">
                <label class="form-label">Leave Type *</label>
                <select name="leave_type" class="form-control" required>
                    <option value="">Select Leave Type</option>
                    <option value="Sick Leave">Sick Leave</option>
                    <option value="Vacation Leave">Vacation Leave</option>
                    <option value="Emergency Leave">Emergency Leave</option>
                    <option value="Personal Leave">Personal Leave</option>
                    <option value="Study Leave">Study Leave</option>
                    <option value="Maternity Leave">Maternity Leave</option>
                    <option value="Paternity Leave">Paternity Leave</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="form-group">
                    <label class="form-label">Start Date *</label>
                    <input type="date" name="start_date" class="form-control" 
                           min="{{ date('Y-m-d') }}" required 
                           onchange="calculateDays()">
                </div>

                <div class="form-group">
                    <label class="form-label">End Date *</label>
                    <input type="date" name="end_date" class="form-control" 
                           min="{{ date('Y-m-d') }}" required 
                           onchange="calculateDays()">
                </div>
            </div>

            <div class="alert alert-info hidden" id="dayCount">
                <i class="fas fa-info-circle"></i> <span id="dayCountText"></span>
            </div>

            <div class="form-group">
                <label class="form-label">Reason for Leave *</label>
                <textarea name="reason" class="form-control" rows="5" required 
                          placeholder="Please provide a detailed reason for your leave request..." 
                          minlength="10"></textarea>
                <small class="text-gray-500 dark:text-gray-400">Minimum 10 characters</small>
            </div>

            <div class="flex justify-end gap-2.5 mt-8">
                <a href="{{ route('leave.index') }}" class="btn bg-gray-200 dark:bg-gray-700">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Submit Leave Request
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    function calculateDays() {
        const startDate = document.querySelector('[name="start_date"]').value;
        const endDate = document.querySelector('[name="end_date"]').value;
        
        if (startDate && endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            
            if (end >= start) {
                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                
                document.getElementById('dayCount').classList.remove('hidden');
                document.getElementById('dayCountText').textContent = 
                    `This leave request is for ${diffDays} day(s)`;
            } else {
                document.getElementById('dayCount').classList.add('hidden');
            }
        }
    }
    
    // Update end date minimum when start date changes
    document.querySelector('[name="start_date"]').addEventListener('change', function() {
        document.querySelector('[name="end_date"]').min = this.value;
    });
</script>
@endpush
