{{-- Consistent Dean Sidebar - Appears on ALL Dean Pages --}}
<div class="sidebar-section-label">Core Functions</div>
<a href="{{ route('dean.dashboard') }}" class="menu-item {{ request()->routeIs('dean.dashboard') ? 'active' : '' }}">
    <i class="fas fa-chart-line"></i> Dashboard
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

<div class="sidebar-section-label">Management</div>
<a href="{{ route('dean.employees') }}" class="menu-item {{ request()->routeIs('dean.employees', 'dean.employee-profile', 'dean.edit-employee') ? 'active' : '' }}">
    <i class="fas fa-users"></i> Faculty Members
</a>
<a href="{{ route('dean.reports') }}" class="menu-item {{ request()->routeIs('dean.reports') ? 'active' : '' }}">
    <i class="fas fa-file-alt"></i> Performance Reports
</a>
<a href="{{ route('dean.analytics') }}" class="menu-item {{ request()->routeIs('dean.analytics') ? 'active' : '' }}">
    <i class="fas fa-chart-pie"></i> Analytics
</a>

<div class="sidebar-section-label">Resources & Development</div>
<a href="{{ route('dean.documents') }}" class="menu-item {{ request()->routeIs('dean.documents') ? 'active' : '' }}">
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

<div class="sidebar-section-label">System</div>
<a href="{{ route('dean.backup') }}" class="menu-item {{ request()->routeIs('dean.backup*') ? 'active' : '' }}">
    <i class="fas fa-database"></i> Backup & Restore
</a>
