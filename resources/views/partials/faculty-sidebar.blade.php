{{-- Consistent Faculty Sidebar - Appears on ALL Faculty Pages --}}
<div class="sidebar-section-label">Core Functions</div>
<a href="{{ route('faculty.dashboard') }}" class="menu-item {{ request()->routeIs('faculty.dashboard') ? 'active' : '' }}">
    <i class="fas fa-chart-line"></i> Dashboard
</a>
<a href="{{ route('faculty.tasks') }}" class="menu-item {{ request()->routeIs('faculty.tasks') ? 'active' : '' }}">
    <i class="fas fa-tasks"></i> My Tasks
</a>

<div class="sidebar-section-label">Admin & Leave</div>
<a href="{{ route('leave.index') }}" class="menu-item {{ request()->routeIs('leave.index', 'leave.create', 'leave.edit') ? 'active' : '' }}">
    <i class="fas fa-calendar-alt"></i> Leave Requests
</a>
<a href="{{ route('calendar.index') }}" class="menu-item {{ request()->routeIs('calendar.index') ? 'active' : '' }}">
    <i class="fas fa-calendar"></i> Calendar
</a>

<div class="sidebar-section-label">Communication</div>
<a href="{{ route('announcements.index') }}" class="menu-item {{ request()->routeIs('announcements.index', 'announcements.show') ? 'active' : '' }}">
    <i class="fas fa-bullhorn"></i> Announcements
</a>
<a href="{{ route('faculty.notifications') }}" class="menu-item {{ request()->routeIs('faculty.notifications') ? 'active' : '' }}">
    <i class="fas fa-bell"></i> Notifications
    @if(isset($unreadNotifications) && $unreadNotifications > 0)
    <span class="badge badge-danger ml-auto">{{ $unreadNotifications }}</span>
    @endif
</a>

<div class="sidebar-section-label">Resources & Development</div>
<a href="{{ route('faculty.documents') }}" class="menu-item {{ request()->routeIs('faculty.documents', 'faculty.upload-document') ? 'active' : '' }}">
    <i class="fas fa-folder"></i> Documents
</a>
<a href="{{ route('equipment.index') }}" class="menu-item {{ request()->routeIs('equipment.*') ? 'active' : '' }}">
    <i class="fas fa-tools"></i> Equipment
</a>
<a href="{{ route('professional-development.index') }}" class="menu-item {{ request()->routeIs('professional-development.*') ? 'active' : '' }}">
    <i class="fas fa-graduation-cap"></i> Prof. Development
</a>
<a href="{{ route('skill-tags.index') }}" class="menu-item {{ request()->routeIs('skill-tags.*') ? 'active' : '' }}">
    <i class="fas fa-tags"></i> Skill Tags
</a>
