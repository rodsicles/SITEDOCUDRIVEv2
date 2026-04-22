{{-- Consistent Coordinator Sidebar - Appears on ALL Coordinator Pages --}}
<div class="sidebar-section-label">Core Functions</div>
<a href="{{ route('coordinator.dashboard') }}" class="menu-item {{ request()->routeIs('coordinator.dashboard') ? 'active' : '' }}">
    <i class="fas fa-chart-line"></i> Dashboard
</a>
<a href="{{ route('coordinator.tasks') }}" class="menu-item {{ request()->routeIs('coordinator.tasks') ? 'active' : '' }}">
    <i class="fas fa-tasks"></i> My Tasks
</a>
<a href="{{ route('coordinator.activity-log') }}" class="menu-item {{ request()->routeIs('coordinator.activity-log') ? 'active' : '' }}">
    <i class="fas fa-history"></i> Activity Log
</a>

<div class="sidebar-section-label">Schedule</div>
<a href="{{ route('calendar.index') }}" class="menu-item {{ request()->routeIs('calendar.index') ? 'active' : '' }}">
    <i class="fas fa-calendar"></i> Calendar
</a>

<div class="sidebar-section-label">Communication</div>
<a href="{{ route('announcements.index') }}" class="menu-item {{ request()->routeIs('announcements.index', 'announcements.show') ? 'active' : '' }}">
    <i class="fas fa-bullhorn"></i> Announcements
</a>

<div class="sidebar-section-label">Management</div>
<a href="{{ route('coordinator.faculty') }}" class="menu-item {{ request()->routeIs('coordinator.faculty', 'coordinator.create-faculty', 'coordinator.edit-faculty') ? 'active' : '' }}">
    <i class="fas fa-users"></i> Faculty Members
</a>

<div class="sidebar-section-label">Resources & Development</div>
<a href="{{ route('coordinator.documents') }}" class="menu-item {{ request()->routeIs('coordinator.documents', 'coordinator.upload-document') ? 'active' : '' }}">
    <i class="fas fa-folder"></i> Documents
</a>
<a href="{{ route('coordinator.teaching-guides.index') }}" class="menu-item {{ request()->routeIs('coordinator.teaching-guides.*') ? 'active' : '' }}">
    <i class="fas fa-book-open"></i> Teaching Guides
</a>
<a href="{{ route('coordinator.exam-questionnaires.index') }}" class="menu-item {{ request()->routeIs('coordinator.exam-questionnaires.*') ? 'active' : '' }}">
    <i class="fas fa-file-alt"></i> Exam Questionnaires
</a>
<a href="{{ route('skill-tags.index') }}" class="menu-item {{ request()->routeIs('skill-tags.*') ? 'active' : '' }}">
    <i class="fas fa-tags"></i> Skill Tags
</a>
