@extends('layouts.dashboard')

@section('title', 'Calendar & Events')

@section('page-title', 'Calendar & Events')
@section('page-subtitle', 'View and manage events, meetings, and deadlines')

@section('sidebar')
    @if(auth()->user()->isFaculty())
    @include('partials.faculty-sidebar')
    @elseif(auth()->user()->isProgramCoordinator())
    @include('partials.coordinator-sidebar')
    @else
    @include('partials.dean-sidebar')
    @endif
@endsection

@push('styles')
<style>
    :root {
        --fc-button-bg-color: #028a0f;
        --fc-button-border-color: #028a0f;
        --fc-button-text-color: #fff;
        --fc-button-hover-bg-color: #026a0c;
        --fc-button-hover-border-color: #026a0c;
        --fc-button-active-bg-color: #025509;
        --fc-button-active-border-color: #025509;
    }

    [data-theme="dark"] {
        --fc-button-bg-color: #02b815;
        --fc-button-border-color: #02b815;
        --fc-button-hover-bg-color: #028a0f;
        --fc-button-hover-border-color: #028a0f;
        --fc-button-active-bg-color: #026a0c;
        --fc-button-active-border-color: #026a0c;
    }

    .fc .fc-button-primary {
        background-color: #028a0f !important;
        border-color: #028a0f !important;
        color: #ffffff !important;
        font-weight: 600 !important;
        border-radius: 0.5rem !important;
        box-shadow: 0 2px 4px rgba(2, 138, 15, 0.3) !important;
    }

    .fc .fc-button-primary:hover:not(:disabled) {
        background-color: #026a0c !important;
        border-color: #026a0c !important;
    }

    .fc .fc-button-primary:not(:disabled).fc-button-active,
    .fc .fc-button-primary:not(:disabled):active {
        background-color: #025509 !important;
        border-color: #025509 !important;
    }

    .fc .fc-button-primary:disabled {
        background-color: #9ca3af !important;
        border-color: #9ca3af !important;
        opacity: 0.6 !important;
    }

    [data-theme="dark"] .fc .fc-button-primary {
        background-color: #02b815 !important;
        border-color: #02b815 !important;
        color: #ffffff !important;
    }

    [data-theme="dark"] .fc .fc-button-primary:hover:not(:disabled) {
        background-color: #028a0f !important;
        border-color: #028a0f !important;
    }

    .fc .fc-toolbar-title {
        font-weight: 700 !important;
        color: #1f2937 !important;
    }

    [data-theme="dark"] .fc .fc-toolbar-title {
        color: #f3f4f6 !important;
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
        <div class="calendar-legend">
            <div class="calendar-legend-title">Event Types</div>
            <div class="calendar-legend-item">
                <span class="calendar-legend-dot" style="background:#2563eb"></span> Meeting
            </div>
            <div class="calendar-legend-item">
                <span class="calendar-legend-dot" style="background:#dc2626"></span> Deadline
            </div>
            <div class="calendar-legend-item">
                <span class="calendar-legend-dot" style="background:#16a34a"></span> Training
            </div>
            <div class="calendar-legend-item">
                <span class="calendar-legend-dot" style="background:#9333ea"></span> Conference
            </div>
            <div class="calendar-legend-item">
                <span class="calendar-legend-dot" style="background:#eab308"></span> Holiday
            </div>
            <div class="calendar-legend-item">
                <span class="calendar-legend-dot" style="background:#0891b2"></span> Seminar
            </div>
            <div class="calendar-legend-item">
                <span class="calendar-legend-dot" style="background:#4b5563"></span> Other
            </div>
        </div>

        <div id="calendar"></div>
    </div>
@endsection

@push('scripts')
<script>
    function initEventCalendar() {
        if (typeof window.Calendar === 'undefined' || typeof window.dayGridPlugin === 'undefined') {
            setTimeout(initEventCalendar, 100);
            return;
        }

        const calendarEl = document.getElementById('calendar');
        if (!calendarEl) return;

        const events = @json($formattedEvents);

        const calendar = new window.Calendar(calendarEl, {
            plugins: [window.dayGridPlugin],
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title'
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
    }

    document.addEventListener('DOMContentLoaded', initEventCalendar);
</script>
@endpush
