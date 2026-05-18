{{-- Consistent Faculty Sidebar - Appears on ALL Faculty Pages --}}
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
<a href="{{ route('faculty.teaching-guides.index') }}" class="menu-item {{ request()->routeIs('faculty.teaching-guides.*') ? 'active' : '' }}">
    <i class="fas fa-book-open"></i> Teaching Guides
</a>
<a href="{{ route('faculty.exam-questionnaires.index') }}" class="menu-item {{ request()->routeIs('faculty.exam-questionnaires.*') ? 'active' : '' }}">
    <i class="fas fa-file-alt"></i> Exam Questionnaires
</a>
