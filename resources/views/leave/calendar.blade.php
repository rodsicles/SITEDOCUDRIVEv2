@extends('layouts.dashboard')

@section('title', 'Leave Calendar')

@section('page-title', 'Leave Calendar')
@section('page-subtitle', 'View all approved leaves in calendar format')

@section('sidebar')
    @if(auth()->user()->isFaculty())
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
    @elseif(auth()->user()->isProgramCoordinator())
    <a href="{{ route('coordinator.dashboard') }}" class="menu-item">
        <i class="fas fa-chart-line"></i> Dashboard
    </a>
    <a href="{{ route('coordinator.tasks') }}" class="menu-item">
        <i class="fas fa-tasks"></i> Tasks
    </a>
    <a href="{{ route('leave.index') }}" class="menu-item active">
        <i class="fas fa-calendar-alt"></i> Leave Requests
    </a>
    <a href="{{ route('calendar.index') }}" class="menu-item">
        <i class="fas fa-calendar"></i> Calendar
    </a>
    @else
    <a href="{{ route('dean.dashboard') }}" class="menu-item">
        <i class="fas fa-chart-line"></i> Dashboard
    </a>
    <a href="{{ route('leave.index') }}" class="menu-item active">
        <i class="fas fa-calendar-alt"></i> Leave Requests
    </a>
    <a href="{{ route('calendar.index') }}" class="menu-item">
        <i class="fas fa-calendar"></i> Calendar
    </a>
    @endif
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
<style>
    #calendar {
        background: var(--white);
        padding: 20px;
        border-radius: 12px;
        box-shadow: var(--shadow);
    }
    .fc {
        color: var(--text-dark);
    }
    .fc .fc-button {
        background: var(--primary-color);
        border-color: var(--primary-color);
    }
    .fc .fc-button:hover {
        background: var(--primary-dark);
        border-color: var(--primary-dark);
    }
    .fc .fc-button-primary:not(:disabled).fc-button-active {
        background: var(--primary-dark);
        border-color: var(--primary-dark);
    }
    
    /* Weekend highlighting with Tailwind-like values */
    .weekend-cell {
        background-color: rgba(0, 0, 0, 0.05) !important;
    }
    
    /* Leave event styling */
    .leave-event {
        border-radius: 0.375rem !important; /* rounded-md */
        border: none !important;
        font-weight: 600 !important;
        font-size: 0.875rem !important;
    }
</style>
@endpush

@section('content')
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Approved Leaves Calendar</h3>
            <div class="flex gap-2.5">
                <a href="{{ route('leave.index') }}" class="btn btn-primary">
                    <i class="fas fa-list"></i> List View
                </a>
                @if(auth()->user()->isFaculty())
                <a href="{{ route('leave.create') }}" class="btn btn-success">
                    <i class="fas fa-plus"></i> File Leave Request
                </a>
                @endif
            </div>
        </div>

        <!-- Leave Types Legend -->
        <div class="px-5 pb-4">
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Leave Types Legend:</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-2 text-xs">
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded-sm bg-red-500"></div>
                        <span class="text-gray-600 dark:text-gray-400">Sick Leave</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded-sm bg-blue-500"></div>
                        <span class="text-gray-600 dark:text-gray-400">Vacation</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded-sm bg-orange-500"></div>
                        <span class="text-gray-600 dark:text-gray-400">Emergency</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded-sm bg-violet-500"></div>
                        <span class="text-gray-600 dark:text-gray-400">Personal</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded-sm bg-amber-500"></div>
                        <span class="text-gray-600 dark:text-gray-400">Study</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded-sm bg-pink-500"></div>
                        <span class="text-gray-600 dark:text-gray-400">Maternity</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded-sm bg-cyan-500"></div>
                        <span class="text-gray-600 dark:text-gray-400">Paternity</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded-sm bg-gray-500"></div>
                        <span class="text-gray-600 dark:text-gray-400">Other</span>
                    </div>
                </div>
            </div>
        </div>

        <div id="calendar"></div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar');
        const events = @json($events);

        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listMonth'
            },
            // Allow navigation to future months for advance leave planning
            validRange: {
                start: '2024-01-01', // Allow some past navigation
                end: '2027-12-31'   // Allow far future navigation
            },
            events: events,
            eventClick: function(info) {
                const startDate = info.event.start.toLocaleDateString();
                const endDate = info.event.end ? info.event.end.toLocaleDateString() : startDate;
                const employeeName = info.event.extendedProps.employeeName || info.event.title;
                const leaveType = info.event.extendedProps.leaveType || 'Leave';
                const days = info.event.extendedProps.days || 'N/A';
                
                const eventDetails = `Employee: ${employeeName}\nLeave Type: ${leaveType}\nDates: ${startDate} - ${endDate}\nDuration: ${days} day(s)\nReason: ${info.event.extendedProps.description || 'N/A'}`;
                
                alert(eventDetails);
            },
            // Highlight weekends differently  
            dayCellClassNames: function(arg) {
                if (arg.date.getDay() === 0 || arg.date.getDay() === 6) {
                    return ['weekend-cell'];
                }
                return [];
            },
            // Add month navigation tracking
            datesSet: function(info) {
                console.log('Calendar showing:', info.startStr, 'to', info.endStr);
            },
            height: 'auto',
            contentHeight: 600,
            // Improve event display
            eventDisplay: 'block',
            displayEventTime: false,
        });

        calendar.render();
        
        // Add navigation hint for users with Tailwind classes
        const navHint = document.createElement('div');
        navHint.innerHTML = '<small class="text-gray-500 dark:text-gray-400"><i class="fas fa-info-circle mr-2"></i>Use ← → arrows to navigate months for advance leave planning - Plan leaves up to 2027!</small>';
        navHint.classList.add('text-center', 'mt-3', 'p-2', 'bg-blue-50', 'dark:bg-blue-900/20', 'rounded-lg', 'border', 'border-blue-200', 'dark:border-blue-700');
        calendarEl.parentNode.appendChild(navHint);
    });
</script>
@endpush
