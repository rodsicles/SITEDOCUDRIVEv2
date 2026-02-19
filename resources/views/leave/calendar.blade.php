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
            events: events,
            eventClick: function(info) {
                alert('Employee: ' + info.event.title + '\nReason: ' + (info.event.extendedProps.description || 'N/A'));
            },
            height: 'auto',
            contentHeight: 600,
        });

        calendar.render();
    });
</script>
@endpush
