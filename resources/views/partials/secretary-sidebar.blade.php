{{-- Secretary Sidebar - Same access as Dean --}}
<div class="sidebar-section-label">Core Functions</div>
<a href="{{ route('dean.dashboard') }}" class="menu-item {{ request()->routeIs('dean.dashboard') ? 'active' : '' }}">
    <i class="fas fa-chart-line"></i> Dashboard
</a>
<a href="{{ route('dean.tasks') }}" class="menu-item {{ request()->routeIs('dean.tasks', 'dean.create-task') ? 'active' : '' }}">
    <i class="fas fa-tasks"></i> Tasks
</a>
<a href="{{ route('dean.activity-log') }}" class="menu-item {{ request()->routeIs('dean.activity-log') ? 'active' : '' }}">
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
<a href="{{ route('dean.employees') }}" class="menu-item {{ request()->routeIs('dean.employees', 'dean.employee-profile', 'dean.edit-employee') ? 'active' : '' }}">
    <i class="fas fa-users"></i> Faculty Members
</a>
<a href="{{ route('dean.reports') }}" class="menu-item {{ request()->routeIs('dean.reports') ? 'active' : '' }}">
    <i class="fas fa-file-alt"></i> Reports
</a>
<a href="{{ route('dean.analytics') }}" class="menu-item {{ request()->routeIs('dean.analytics') ? 'active' : '' }}">
    <i class="fas fa-chart-pie"></i> Analytics
</a>

<div class="sidebar-section-label">Resources & Development</div>
<a href="{{ route('dean.documents') }}" class="menu-item {{ request()->routeIs('dean.documents') ? 'active' : '' }}">
    <i class="fas fa-folder"></i> Documents
</a>
<a href="{{ route('dean.teaching-guides.index') }}" class="menu-item {{ request()->routeIs('dean.teaching-guides.*') ? 'active' : '' }}">
    <i class="fas fa-book-open"></i> Teaching Guides
</a>
<a href="{{ route('dean.exam-questionnaires.index') }}" class="menu-item {{ request()->routeIs('dean.exam-questionnaires.*') ? 'active' : '' }}">
    <i class="fas fa-file-alt"></i> Exam Questionnaires
</a>
