{{-- Consistent Faculty Sidebar - Appears on ALL Faculty Pages --}}
<div class="sidebar-section-label">Resources & Development</div>
<a href="{{ route('faculty.documents') }}" class="menu-item {{ request()->routeIs('faculty.documents', 'faculty.upload-document') ? 'active' : '' }}">
    <i class="fas fa-folder"></i> Documents
</a>
<a href="{{ route('faculty.recycle-bin.index') }}" class="menu-item {{ request()->routeIs('faculty.recycle-bin.*') ? 'active' : '' }}">
    <i class="fas fa-recycle"></i> Recycle Bin
</a>
<a href="{{ route('faculty.archives.list') }}" class="menu-item {{ request()->routeIs('faculty.archives.*') ? 'active' : '' }}">
    <i class="fas fa-box-archive"></i> Archives
</a>

<div class="sidebar-section-label">Communication</div>
<a href="{{ route('announcements.index') }}" class="menu-item {{ request()->routeIs('announcements.index', 'announcements.show') ? 'active' : '' }}">
    <i class="fas fa-bullhorn"></i> Announcements
</a>

<div class="sidebar-section-label">Core Functions</div>
<a href="{{ route('faculty.dashboard') }}" class="menu-item {{ request()->routeIs('faculty.dashboard') ? 'active' : '' }}">
    <i class="fas fa-chart-line"></i> Dashboard
</a>
<a href="{{ route('faculty.tasks') }}" class="menu-item {{ request()->routeIs('faculty.tasks') ? 'active' : '' }}">
    <i class="fas fa-tasks"></i> My Tasks
</a>
<a href="{{ route('faculty.activity-log') }}" class="menu-item {{ request()->routeIs('faculty.activity-log') ? 'active' : '' }}">
    <i class="fas fa-history"></i> Activity Log
</a>
<a href="{{ route('faculty.analytics') }}" class="menu-item {{ request()->routeIs('faculty.analytics') ? 'active' : '' }}">
    <i class="fas fa-chart-pie"></i> Performance Analytics
</a>
