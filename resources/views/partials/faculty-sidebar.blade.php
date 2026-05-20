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
<a href="{{ route('faculty.analytics') }}" class="menu-item {{ request()->routeIs('faculty.analytics') ? 'active' : '' }}">
    <i class="fas fa-chart-pie"></i> Performance Analytics
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
<a href="{{ route('faculty.recycle-bin.index') }}" class="menu-item {{ request()->routeIs('faculty.recycle-bin.*') ? 'active' : '' }}">
    <i class="fas fa-recycle"></i> Recycle Bin
</a>
<a href="{{ route('faculty.teaching-guides.index') }}" class="menu-item {{ request()->routeIs('faculty.teaching-guides.*') ? 'active' : '' }}">
    <i class="fas fa-book-open"></i> Teaching Guides
    @if(!empty($pendingTeachingGuidesCount) && $pendingTeachingGuidesCount > 0)
    <span class="badge ml-auto" style="background:#b45309;color:#fff;" title="Pending Dean approval">{{ $pendingTeachingGuidesCount }}</span>
    @endif
</a>
<a href="{{ route('faculty.exam-questionnaires.index') }}" class="menu-item {{ request()->routeIs('faculty.exam-questionnaires.*') ? 'active' : '' }}">
    <i class="fas fa-file-alt"></i> Exam Questionnaires
    @if(!empty($pendingExamQuestionnairesCount) && $pendingExamQuestionnairesCount > 0)
    <span class="badge ml-auto" style="background:#b45309;color:#fff;" title="Pending Dean approval">{{ $pendingExamQuestionnairesCount }}</span>
    @endif
</a>
<a href="{{ route('faculty.archives.list') }}" class="menu-item {{ request()->routeIs('faculty.archives.*') ? 'active' : '' }}">
    <i class="fas fa-box-archive"></i> Archives
</a>
