@extends('layouts.dashboard')

@section('title', 'Create Event')

@section('page-title', 'Create New Event')
@section('page-subtitle', 'Schedule meetings, deadlines, and other events')

@section('sidebar')
    @if(auth()->user()->isFaculty())
    <a href="{{ route('faculty.dashboard') }}" class="menu-item">
        <i class="fas fa-chart-line"></i> Dashboard
    </a>
    <a href="{{ route('leave.index') }}" class="menu-item">
        <i class="fas fa-calendar-alt"></i> Leave Requests
    </a>
    <a href="{{ route('calendar.index') }}" class="menu-item active">
        <i class="fas fa-calendar"></i> Calendar
    </a>
    @elseif(auth()->user()->isProgramCoordinator())
    <a href="{{ route('coordinator.dashboard') }}" class="menu-item">
        <i class="fas fa-chart-line"></i> Dashboard
    </a>
    <a href="{{ route('leave.index') }}" class="menu-item">
        <i class="fas fa-calendar-alt"></i> Leave Requests
    </a>
    <a href="{{ route('calendar.index') }}" class="menu-item active">
        <i class="fas fa-calendar"></i> Calendar
    </a>
    @else
    <a href="{{ route('dean.dashboard') }}" class="menu-item">
        <i class="fas fa-chart-line"></i> Dashboard
    </a>
    <a href="{{ route('calendar.index') }}" class="menu-item active">
        <i class="fas fa-calendar"></i> Calendar
    </a>
    @endif
@endsection

@section('content')
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Event Details</h3>
            <a href="{{ route('calendar.index') }}" class="btn bg-gray-200 dark:bg-gray-700">
                <i class="fas fa-arrow-left"></i> Back to Calendar
            </a>
        </div>

        <form action="{{ route('calendar.store') }}" method="POST" class="p-5">
            @csrf

            <div class="form-group">
                <label class="form-label">Event Title *</label>
                <input type="text" name="title" class="form-control" required 
                       placeholder="Enter event title" maxlength="200">
            </div>

            <div class="form-group">
                <label class="form-label">Event Type *</label>
                <select name="event_type" class="form-control" required>
                    <option value="">Select Type</option>
                    <option value="Meeting">Meeting</option>
                    <option value="Deadline">Deadline</option>
                    <option value="Training">Training</option>
                    <option value="Conference">Conference</option>
                    <option value="Holiday">Holiday</option>
                    <option value="Seminar">Seminar</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4" 
                          placeholder="Event description, agenda, or notes..."></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="form-group">
                    <label class="form-label">Start Date & Time *</label>
                    <input type="datetime-local" name="start_datetime" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">End Date & Time *</label>
                    <input type="datetime-local" name="end_datetime" class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <input type="checkbox" name="all_day" value="1" onchange="toggleAllDay(this)">
                    All Day Event
                </label>
            </div>

            <div class="form-group">
                <label class="form-label">Location</label>
                <input type="text" name="location" class="form-control" 
                       placeholder="e.g., Conference Room A, Zoom Link, etc.">
            </div>

            <div class="form-group">
                <label class="form-label">Visibility *</label>
                <select name="visibility" class="form-control" required>
                    <option value="Public" selected>Public (visible to all)</option>
                    <option value="Department">Department Only</option>
                    <option value="Private">Private (only me and invitees)</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Invite Attendees</label>
                <select name="attendees[]" class="form-control h-auto" multiple size="8">
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">
                            {{ $user->employee->full_name ?? $user->username }} 
                            ({{ $user->role->role_name }})
                        </option>
                    @endforeach
                </select>
                <small class="text-gray-500 dark:text-gray-400">
                    Hold Ctrl (Windows) or Cmd (Mac) to select multiple attendees
                </small>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <input type="checkbox" name="send_reminder" value="1" checked>
                    Send Reminder Notification
                </label>
            </div>

            <div class="form-group" id="reminderMinutes">
                <label class="form-label">Reminder Time</label>
                <select name="reminder_minutes" class="form-control">
                    <option value="5">5 minutes before</option>
                    <option value="15">15 minutes before</option>
                    <option value="30" selected>30 minutes before</option>
                    <option value="60">1 hour before</option>
                    <option value="1440">1 day before</option>
                </select>
            </div>

            <div class="flex justify-end gap-2.5 mt-8">
                <a href="{{ route('calendar.index') }}" class="btn bg-gray-200 dark:bg-gray-700">
                    Cancel
                </a>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check"></i> Create Event
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    function toggleAllDay(checkbox) {
        const startInput = document.querySelector('[name="start_datetime"]');
        const endInput = document.querySelector('[name="end_datetime"]');
        
        if (checkbox.checked) {
            startInput.type = 'date';
            endInput.type = 'date';
        } else {
            startInput.type = 'datetime-local';
            endInput.type = 'datetime-local';
        }
    }

    // Validate end time is after start time
    document.querySelector('[name="end_datetime"]').addEventListener('change', function() {
        const start = document.querySelector('[name="start_datetime"]').value;
        const end = this.value;
        
        if (start && end && end <= start) {
            alert('End date/time must be after start date/time');
            this.value = '';
        }
    });
</script>
@endpush
