@extends('layouts.dashboard')

@section('title', 'Calendar & Events')

@section('page-title', 'Calendar & Events')
@section('page-subtitle', 'View and manage events, meetings, and deadlines')

@section('sidebar')
    @if(auth()->user()->isFaculty())
    <a href="{{ route('faculty.dashboard') }}" class="menu-item">
        <i class="fas fa-chart-line"></i> Dashboard
    </a>
    <a href="{{ route('faculty.tasks') }}" class="menu-item">
        <i class="fas fa-tasks"></i> My Tasks
    </a>
    <a href="{{ route('leave.index') }}" class="menu-item">
        <i class="fas fa-calendar-alt"></i> Leave Requests
    </a>
    <a href="{{ route('calendar.index') }}" class="menu-item active">
        <i class="fas fa-calendar"></i> Calendar
    </a>
    <a href="{{ route('faculty.documents') }}" class="menu-item">
        <i class="fas fa-folder"></i> Documents
    </a>
    @elseif(auth()->user()->isProgramCoordinator())
    <a href="{{ route('coordinator.dashboard') }}" class="menu-item">
        <i class="fas fa-chart-line"></i> Dashboard
    </a>
    <a href="{{ route('coordinator.tasks') }}" class="menu-item">
        <i class="fas fa-tasks"></i> Tasks
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
    <a href="{{ route('leave.index') }}" class="menu-item">
        <i class="fas fa-calendar-alt"></i> Leave Requests
    </a>
    <a href="{{ route('calendar.index') }}" class="menu-item active">
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
    .legend {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        margin-bottom: 20px;
        padding: 15px;
        background: rgb(243 244 246 / var(--tw-bg-opacity, 1));
        border-radius: 8px;
    }
    [data-theme="dark"] .legend {
        background: rgb(55 65 81 / var(--tw-bg-opacity, 1));
    }
    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
    }
    .legend-color {
        width: 20px;
        height: 20px;
        border-radius: 4px;
    }
</style>
@endpush

@section('content')
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Event Calendar</h3>
            @if(auth()->user()->isProgramCoordinator() || auth()->user()->isDean())
            <a href="{{ route('calendar.create') }}" class="btn btn-success">
                <i class="fas fa-plus"></i> Create Event
            </a>
            @endif
        </div>

        <!-- Legend -->
        <div class="legend">
            <div class="legend-item">
                <div class="legend-color bg-blue-600"></div>
                <span>Meeting</span>
            </div>
            <div class="legend-item">
                <div class="legend-color bg-red-600"></div>
                <span>Deadline</span>
            </div>
            <div class="legend-item">
                <div class="legend-color bg-green-600"></div>
                <span>Training</span>
            </div>
            <div class="legend-item">
                <div class="legend-color bg-purple-600"></div>
                <span>Conference</span>
            </div>
            <div class="legend-item">
                <div class="legend-color bg-yellow-500"></div>
                <span>Holiday</span>
            </div>
            <div class="legend-item">
                <div class="legend-color bg-cyan-600"></div>
                <span>Seminar</span>
            </div>
            <div class="legend-item">
                <div class="legend-color bg-gray-600"></div>
                <span>Other</span>
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
        const events = @json($formattedEvents);

        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
            },
            events: events,
            eventClick: function(info) {
                window.location.href = '/calendar/' + info.event.id;
            },
            height: 'auto',
            contentHeight: 700,
            eventTimeFormat: {
                hour: 'numeric',
                minute: '2-digit',
                meridiem: 'short'
            }
        });

        calendar.render();
    });
</script>
@endpush
