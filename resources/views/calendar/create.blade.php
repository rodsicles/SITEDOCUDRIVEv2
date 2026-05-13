@extends('layouts.dashboard')

@section('title', 'Create Event')

@section('page-title', 'Create New Event')
@section('page-subtitle', 'Schedule meetings, deadlines, and other events')

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
    <div class="max-w-3xl mx-auto">
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-calendar-plus mr-2 text-[#028a0f] dark:text-[#02b815]"></i>
                    Event Details
                </h3>
                <a href="{{ route('calendar.index') }}" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs">
                    <i class="fas fa-arrow-left mr-1"></i> Back
                </a>
            </div>

            <form action="{{ route('calendar.store') }}" method="POST" class="p-5 space-y-5">
                @csrf

                {{-- Section: Basic Info --}}
                <div>
                    <div class="text-[0.7rem] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3 pb-1.5 border-b border-gray-200 dark:border-gray-700">
                        Basic Information
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="form-group mb-0 md:col-span-2">
                            <label class="form-label">Event Title <span class="text-red-500">*</span></label>
                            <input type="text" name="title" class="form-control" required
                                   placeholder="Enter event title" maxlength="30">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Event Type <span class="text-red-500">*</span></label>
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
                    </div>
                    <div class="form-group mb-0 mt-4">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"
                                  placeholder="Event description, agenda, or notes..." maxlength="150"></textarea>
                    </div>
                </div>

                {{-- Section: Schedule --}}
                <div>
                    <div class="text-[0.7rem] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3 pb-1.5 border-b border-gray-200 dark:border-gray-700">
                        Schedule
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group mb-0">
                            <label class="form-label">Start Date &amp; Time <span class="text-red-500">*</span></label>
                            <input type="datetime-local" name="start_datetime" class="form-control" required min="{{ (date('Y') - 1) . '-01-01T00:00' }}" max="{{ date('Y') . '-12-31T23:59' }}">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">End Date &amp; Time <span class="text-red-500">*</span></label>
                            <input type="datetime-local" name="end_datetime" class="form-control" required min="{{ (date('Y') - 1) . '-01-01T00:00' }}" max="{{ date('Y') . '-12-31T23:59' }}">
                        </div>
                    </div>
                    <label class="inline-flex items-center gap-2 mt-3 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                        <input type="checkbox" name="all_day" value="1" onchange="toggleAllDay(this)" class="rounded">
                        All Day Event
                    </label>
                </div>

                {{-- Section: Details --}}
                <div>
                    <div class="text-[0.7rem] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3 pb-1.5 border-b border-gray-200 dark:border-gray-700">
                        Details
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group mb-0">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control"
                                   placeholder="e.g., Room A, Zoom link" maxlength="50">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Visibility <span class="text-red-500">*</span></label>
                            <select name="visibility" class="form-control" required>
                                <option value="Public" selected>Public (visible to all)</option>
                                <option value="Department">Department Only</option>
                                <option value="Private">Private (only me and invitees)</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Section: Attendees & Reminder --}}
                <div>
                    <div class="text-[0.7rem] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3 pb-1.5 border-b border-gray-200 dark:border-gray-700">
                        Attendees &amp; Reminder
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">Invite Attendees</label>

                        {{-- Search box --}}
                        <div class="relative mb-1.5">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                            <input type="text" id="attendeeSearch" placeholder="Search by name or role…"
                                   class="form-control text-sm pl-8"
                                   oninput="filterAttendees(this.value)">
                        </div>

                        {{-- Scrollable checkbox list --}}
                        <div id="attendeeList"
                             class="border border-gray-300 dark:border-gray-600 rounded overflow-y-auto bg-white dark:bg-[#1e1e1e]"
                             style="max-height:200px;">
                            @foreach($users as $user)
                                @php $label = ($user->employee->full_name ?? $user->username) . ' (' . $user->role->role_name . ')'; @endphp
                                <label class="attendee-row flex items-center gap-2.5 px-3 py-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-[#2a2a2a] border-b border-gray-100 dark:border-gray-700 last:border-0"
                                       data-label="{{ strtolower($label) }}">
                                    <input type="checkbox" name="attendees[]" value="{{ $user->id }}"
                                           class="rounded accent-[#028a0f]">
                                    <span class="text-sm text-gray-800 dark:text-gray-200">{{ $label }}</span>
                                </label>
                            @endforeach
                            <div id="attendeeNoResults" class="hidden px-3 py-3 text-xs text-gray-400 text-center">
                                No users match your search.
                            </div>
                        </div>

                        <div class="flex items-center justify-between mt-1.5">
                            <small class="text-gray-500 dark:text-gray-400 text-xs">
                                <span id="attendeeSelectedCount">0</span> selected
                            </small>
                            <button type="button" onclick="clearAttendees()"
                                    class="text-xs text-gray-400 hover:text-red-500 bg-transparent border-0 cursor-pointer">
                                Clear all
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 items-end">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer pb-2">
                            <input type="checkbox" name="send_reminder" value="1" checked class="rounded">
                            Send Reminder Notification
                        </label>
                        <div class="form-group mb-0" id="reminderMinutes">
                            <label class="form-label">Reminder Time</label>
                            <select name="reminder_minutes" class="form-control">
                                <option value="5">5 minutes before</option>
                                <option value="15">15 minutes before</option>
                                <option value="30" selected>30 minutes before</option>
                                <option value="60">1 hour before</option>
                                <option value="1440">1 day before</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2.5 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('calendar.index') }}" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check mr-1"></i> Create Event
                    </button>
                </div>
            </form>
        </div>
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

    // Attendee search filter
    function filterAttendees(query) {
        const q = query.toLowerCase().trim();
        const rows = document.querySelectorAll('.attendee-row');
        let visible = 0;
        rows.forEach(row => {
            const match = !q || row.dataset.label.includes(q);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        document.getElementById('attendeeNoResults').classList.toggle('hidden', visible > 0);
    }

    // Update selected count
    document.getElementById('attendeeList').addEventListener('change', function() {
        const count = this.querySelectorAll('input[type="checkbox"]:checked').length;
        document.getElementById('attendeeSelectedCount').textContent = count;
    });

    // Clear all checkboxes
    function clearAttendees() {
        document.querySelectorAll('#attendeeList input[type="checkbox"]').forEach(cb => cb.checked = false);
        document.getElementById('attendeeSelectedCount').textContent = '0';
        document.getElementById('attendeeSearch').value = '';
        filterAttendees('');
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
