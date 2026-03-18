{{-- Consistent Faculty Sidebar - Appears on ALL Faculty Pages --}}
<a href="{{ route('faculty.dashboard') }}" class="menu-item {{ request()->routeIs('faculty.dashboard') ? 'active' : '' }}">
    <i class="fas fa-chart-line"></i> Dashboard
</a>
<a href="{{ route('faculty.tasks') }}" class="menu-item {{ request()->routeIs('faculty.tasks') ? 'active' : '' }}">
    <i class="fas fa-tasks"></i> My Tasks
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
<a href="{{ route('faculty.notifications') }}" class="menu-item {{ request()->routeIs('faculty.notifications') ? 'active' : '' }}">
    <i class="fas fa-bell"></i> Notifications
    @if(isset($unreadNotifications) && $unreadNotifications > 0)
    <span class="badge badge-danger ml-auto">{{ $unreadNotifications }}</span>
    @endif
</a>
<a href="{{ route('faculty.documents') }}" class="menu-item {{ request()->routeIs('faculty.documents', 'faculty.upload-document') ? 'active' : '' }}">
    <i class="fas fa-folder"></i> Documents
</a>
