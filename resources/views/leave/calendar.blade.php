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
        padding: 0.5rem 1rem !important;
        border-radius: 0.5rem !important;
        box-shadow: 0 2px 4px rgba(2, 138, 15, 0.3) !important;
    }

    .fc .fc-button-primary:hover:not(:disabled) {
        background-color: #026a0c !important;
        border-color: #026a0c !important;
        transform: translateY(-1px) !important;
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

    [data-theme="dark"] .fc .fc-daygrid-day {
        background-color: #1f2937 !important;
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
        <div class="calendar-legend">
            <div class="calendar-legend-title">Leave Types</div>
            <div class="calendar-legend-item">
                <span class="calendar-legend-dot" style="background:#ef4444"></span> Sick Leave
            </div>
            <div class="calendar-legend-item">
                <span class="calendar-legend-dot" style="background:#3b82f6"></span> Vacation
            </div>
            <div class="calendar-legend-item">
                <span class="calendar-legend-dot" style="background:#f97316"></span> Emergency
            </div>
            <div class="calendar-legend-item">
                <span class="calendar-legend-dot" style="background:#8b5cf6"></span> Personal
            </div>
            <div class="calendar-legend-item">
                <span class="calendar-legend-dot" style="background:#f59e0b"></span> Study
            </div>
            <div class="calendar-legend-item">
                <span class="calendar-legend-dot" style="background:#ec4899"></span> Maternity
            </div>
            <div class="calendar-legend-item">
                <span class="calendar-legend-dot" style="background:#06b6d4"></span> Paternity
            </div>
            <div class="calendar-legend-item">
                <span class="calendar-legend-dot" style="background:#6b7280"></span> Other
            </div>
        </div>

        <div id="calendar"></div>

        <!-- Navigation Hint -->
        <div class="calendar-nav-hint">
            <i class="fas fa-info-circle mr-1"></i>
            Use ← → arrows to navigate months for advance leave planning — Plan leaves up to 2027!
        </div>
    </div>

    <!-- Leave Detail Modal -->
    <div id="leaveModal" class="calendar-modal-overlay" onclick="if(event.target===this)closeLeaveModal()">
        <div class="calendar-modal-card">
            <button class="calendar-modal-close" onclick="closeLeaveModal()" title="Close (Esc)">&times;</button>
            
            <div class="calendar-modal-header">
                <span id="modalBadge" class="calendar-modal-type-badge"></span>
                <div id="modalTitle" class="calendar-modal-title"></div>
            </div>
            
            <div class="calendar-modal-row">
                <span class="calendar-modal-label"><i class="fas fa-user text-[0.65rem]"></i> Employee</span>
                <span id="modalEmployee" class="calendar-modal-value"></span>
            </div>
            
            <div class="calendar-modal-row">
                <span class="calendar-modal-label"><i class="fas fa-calendar-alt text-[0.65rem]"></i> Dates</span>
                <span id="modalDates" class="calendar-modal-value"></span>
            </div>
            
            <div class="calendar-modal-row">
                <span class="calendar-modal-label"><i class="fas fa-clock text-[0.65rem]"></i> Duration</span>
                <span id="modalDuration" class="calendar-modal-value"></span>
            </div>
            
            <div class="calendar-modal-row">
                <span class="calendar-modal-label"><i class="fas fa-comment-alt text-[0.65rem]"></i> Reason</span>
                <span id="modalReason" class="calendar-modal-value"></span>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function initLeaveCalendar() {
        if (typeof window.Calendar === 'undefined' || typeof window.dayGridPlugin === 'undefined') {
            setTimeout(initLeaveCalendar, 100);
            return;
        }

        const calendarEl = document.getElementById('calendar');
        if (!calendarEl) return;

        const events = @json($events);
        const leaveColors = {
            'Sick Leave': '#ef4444',
            'Vacation Leave': '#3b82f6',
            'Emergency Leave': '#f97316',
            'Personal Leave': '#8b5cf6',
            'Study Leave': '#f59e0b',
            'Maternity Leave': '#ec4899',
            'Paternity Leave': '#06b6d4',
            'Other': '#6b7280'
        };

        const calendar = new window.Calendar(calendarEl, {
            plugins: [window.dayGridPlugin],
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listMonth'
            },
            buttonText: {
                today: 'Today',
                month: 'Month',
                week: 'Week',
                list: 'List'
            },
            validRange: {
                start: '2024-01-01',
                end: '2027-12-31'
            },
            events: events,
            eventClick: function(info) {
                info.jsEvent.preventDefault();
                const props = info.event.extendedProps;
                const startDate = info.event.start.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                const endDate = info.event.end
                    ? new Date(info.event.end.getTime() - 86400000).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                    : startDate;

                const leaveType = props.leaveType || 'Leave';
                document.getElementById('modalBadge').textContent = leaveType;
                document.getElementById('modalBadge').style.background = leaveColors[leaveType] || '#6b7280';
                document.getElementById('modalTitle').textContent = props.employeeName || info.event.title;
                document.getElementById('modalEmployee').textContent = props.employeeName || info.event.title;
                document.getElementById('modalDates').textContent = startDate === endDate ? startDate : startDate + ' — ' + endDate;
                document.getElementById('modalDuration').textContent = (props.days || 'N/A') + ' day(s)';
                document.getElementById('modalReason').textContent = props.description || 'No reason provided';
                document.getElementById('leaveModal').classList.add('active');
            },
            dayCellClassNames: function(arg) {
                if (arg.date.getDay() === 0 || arg.date.getDay() === 6) {
                    return ['fc-weekend-cell'];
                }
                return [];
            },
            height: 'auto',
            contentHeight: 600,
            eventDisplay: 'block',
            displayEventTime: false,
        });

        calendar.render();
    }

    document.addEventListener('DOMContentLoaded', initLeaveCalendar);

    function closeLeaveModal() {
        document.getElementById('leaveModal').classList.remove('active');
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeLeaveModal();
    });
</script>
@endpush
