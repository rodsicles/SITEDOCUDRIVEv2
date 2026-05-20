{{-- Consistent Dean Sidebar - Appears on ALL Dean Pages --}}
<div class="sidebar-section-label">Core Functions</div>
<a href="{{ route('dean.dashboard') }}" class="menu-item {{ request()->routeIs('dean.dashboard') ? 'active' : '' }}">
    <i class="fas fa-chart-line"></i> Dashboard
</a>
<a href="{{ route('dean.tasks') }}" class="menu-item {{ request()->routeIs('dean.tasks', 'dean.create-task') ? 'active' : '' }}">
    <i class="fas fa-tasks"></i> Tasks
</a>
<a href="{{ route('dean.audit-trail') }}" class="menu-item {{ request()->routeIs('dean.audit-trail', 'dean.activity-log') ? 'active' : '' }}">
    <i class="fas fa-history"></i> Activity Log
</a>

<div class="sidebar-section-label">Communication</div>
<a href="{{ route('dean.notifications') }}" class="menu-item {{ request()->routeIs('dean.notifications') ? 'active' : '' }}">
    <i class="fas fa-bell"></i> Notifications
    @if(isset($unreadNotifications) && $unreadNotifications > 0)
    <span class="badge badge-danger ml-auto">{{ $unreadNotifications }}</span>
    @endif
</a>
<a href="{{ route('announcements.index') }}" class="menu-item {{ request()->routeIs('announcements.index', 'announcements.show') ? 'active' : '' }}">
    <i class="fas fa-bullhorn"></i> Announcements
</a>

<div class="sidebar-section-label">Management</div>
<a href="{{ route('dean.employees') }}" class="menu-item {{ request()->routeIs('dean.employees', 'dean.employee-profile', 'dean.edit-employee') ? 'active' : '' }}">
    <i class="fas fa-users"></i> Faculty Members
</a>
<a href="{{ route('dean.reports') }}" class="menu-item {{ request()->routeIs('dean.reports') ? 'active' : '' }}">
    <i class="fas fa-file-alt"></i> Reports
</a>
<a href="{{ route('dean.courses') }}" class="menu-item {{ request()->routeIs('dean.courses*') ? 'active' : '' }}">
    <i class="fas fa-book"></i> Course Catalog
</a>
<a href="{{ route('dean.analytics') }}" class="menu-item {{ request()->routeIs('dean.analytics') ? 'active' : '' }}">
    <i class="fas fa-chart-pie"></i> Analytics
</a>

<div class="sidebar-section-label">Resources & Development</div>
<a href="{{ route('dean.documents') }}" class="menu-item {{ request()->routeIs('dean.documents') ? 'active' : '' }}">
    <i class="fas fa-folder"></i> Documents
</a>
<a href="{{ route('dean.recycle-bin.index') }}" class="menu-item {{ request()->routeIs('dean.recycle-bin.*') ? 'active' : '' }}">
    <i class="fas fa-recycle"></i> Recycle Bin
</a>
<a href="{{ route('dean.teaching-guides.index') }}" class="menu-item {{ request()->routeIs('dean.teaching-guides.*') ? 'active' : '' }}">
    <i class="fas fa-book-open"></i> Pending Teaching Guides
</a>
<a href="{{ route('dean.exam-questionnaires.index') }}" class="menu-item {{ request()->routeIs('dean.exam-questionnaires.*') ? 'active' : '' }}">
    <i class="fas fa-file-alt"></i> Pending Exam Questionnaires
</a>
<a href="{{ route('dean.archives.index') }}" class="menu-item {{ request()->routeIs('dean.archives.*') ? 'active' : '' }}">
    <i class="fas fa-box-archive"></i> Archives
</a>
