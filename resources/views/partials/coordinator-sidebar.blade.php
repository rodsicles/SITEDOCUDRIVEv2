{{-- Consistent Coordinator Sidebar - Appears on ALL Coordinator Pages --}}
<a href="{{ route('coordinator.dashboard') }}" class="menu-item {{ request()->routeIs('coordinator.dashboard') ? 'active' : '' }}">
    <i class="fas fa-chart-line"></i> Dashboard
</a>
<a href="{{ route('coordinator.tasks') }}" class="menu-item {{ request()->routeIs('coordinator.tasks', 'coordinator.create-task') ? 'active' : '' }}">
    <i class="fas fa-tasks"></i> Tasks
</a>
<a href="{{ route('leave.index') }}" class="menu-item {{ request()->routeIs('leave.index', 'leave.create', 'leave.edit') ? 'active' : '' }}">
    <i class="fas fa-calendar-alt"></i> Leave Requests
</a>
<a href="{{ route('calendar.index') }}" class="menu-item {{ request()->routeIs('calendar.index') ? 'active' : '' }}">
    <i class="fas fa-calendar"></i> Calendar
</a>
<a href="{{ route('announcements.index') }}" class="menu-item {{ request()->routeIs('announcements.index', 'announcements.show') ? 'active' : '' }}">
    <i class="fas fa-bullhorn"></i> Announcements
</a>
<a href="{{ route('coordinator.faculty') }}" class="menu-item {{ request()->routeIs('coordinator.faculty', 'coordinator.create-faculty', 'coordinator.edit-faculty') ? 'active' : '' }}">
    <i class="fas fa-users"></i> Faculty Members
</a>
<a href="{{ route('coordinator.documents') }}" class="menu-item {{ request()->routeIs('coordinator.documents', 'coordinator.upload-document') ? 'active' : '' }}">
    <i class="fas fa-folder"></i> Documents
</a>
