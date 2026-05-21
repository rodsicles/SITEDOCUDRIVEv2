@extends('layouts.dashboard')

@section('title', 'User Guide')
@section('page-title', 'User Guide')
@section('page-subtitle', 'Step-by-step guide for using the Employee Dashboard')

@section('sidebar')
    @if(auth()->user()->isFaculty())
        @include('partials.faculty-sidebar')
    @elseif(auth()->user()->isProgramCoordinator())
        @include('partials.coordinator-sidebar')
    @elseif(auth()->user()->isSecretary())
        @include('partials.secretary-sidebar')
    @else
        @include('partials.dean-sidebar')
    @endif
@endsection

@section('content')
@php
    $user = auth()->user();
    $isFaculty = $user->isFaculty();
    $isCoordinator = $user->isProgramCoordinator();
    $isDean = $user->isDean();
    $isSecretary = $user->isSecretary();
    $isDeanOrSecretary = $user->isDeanOrSecretary();
@endphp

{{-- Welcome Banner --}}
<div class="guide-welcome-banner">
    <div class="guide-welcome-icon"><i class="fas fa-book-open"></i></div>
    <div>
        <div class="guide-welcome-title">Welcome, {{ $user->role->role_name }}</div>
        <div class="guide-welcome-sub">This guide covers the features available to your role. Follow the steps below to use the Employee Dashboard effectively.</div>
    </div>
</div>

{{-- Table of Contents --}}
<div class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list mr-2"></i> Table of Contents</h3>
    </div>
    <div class="guide-toc">
        @if($isFaculty)
            <a href="#guide-dashboard" class="guide-toc-item"><span class="guide-toc-num">1</span> Dashboard</a>
            <a href="#guide-tasks" class="guide-toc-item"><span class="guide-toc-num">2</span> My Tasks</a>
            <a href="#guide-activity-log" class="guide-toc-item"><span class="guide-toc-num">3</span> Activity Log</a>
            <a href="#guide-analytics" class="guide-toc-item"><span class="guide-toc-num">4</span> Performance Analytics</a>
            <a href="#guide-announcements" class="guide-toc-item"><span class="guide-toc-num">5</span> Announcements</a>
            <a href="#guide-notifications" class="guide-toc-item"><span class="guide-toc-num">6</span> Notifications</a>
            <a href="#guide-documents" class="guide-toc-item"><span class="guide-toc-num">7</span> Documents</a>
            <a href="#guide-recycle-bin" class="guide-toc-item"><span class="guide-toc-num">8</span> Recycle Bin</a>
            <a href="#guide-teaching" class="guide-toc-item"><span class="guide-toc-num">9</span> Teaching Guides</a>
            <a href="#guide-exams" class="guide-toc-item"><span class="guide-toc-num">10</span> Exam Questionnaires</a>
            <a href="#guide-archives" class="guide-toc-item"><span class="guide-toc-num">11</span> Archives</a>
            <a href="#guide-profile" class="guide-toc-item"><span class="guide-toc-num">12</span> Profile</a>
        @elseif($isCoordinator)
            <a href="#guide-dashboard" class="guide-toc-item"><span class="guide-toc-num">1</span> Dashboard</a>
            <a href="#guide-tasks" class="guide-toc-item"><span class="guide-toc-num">2</span> My Tasks</a>
            <a href="#guide-activity-log" class="guide-toc-item"><span class="guide-toc-num">3</span> Activity Log</a>
            <a href="#guide-announcements" class="guide-toc-item"><span class="guide-toc-num">4</span> Announcements</a>
            <a href="#guide-notifications" class="guide-toc-item"><span class="guide-toc-num">5</span> Notifications</a>
            <a href="#guide-faculty" class="guide-toc-item"><span class="guide-toc-num">6</span> Faculty Members</a>
            <a href="#guide-courses" class="guide-toc-item"><span class="guide-toc-num">7</span> Course Catalog</a>
            <a href="#guide-analytics" class="guide-toc-item"><span class="guide-toc-num">8</span> Analytics</a>
            <a href="#guide-documents" class="guide-toc-item"><span class="guide-toc-num">9</span> Documents</a>
            <a href="#guide-recycle-bin" class="guide-toc-item"><span class="guide-toc-num">10</span> Recycle Bin</a>
            <a href="#guide-teaching" class="guide-toc-item"><span class="guide-toc-num">11</span> Teaching Guides</a>
            <a href="#guide-exams" class="guide-toc-item"><span class="guide-toc-num">12</span> Exam Questionnaires</a>
            <a href="#guide-archives" class="guide-toc-item"><span class="guide-toc-num">13</span> Archives</a>
        @elseif($isSecretary)
            <a href="#guide-dashboard" class="guide-toc-item"><span class="guide-toc-num">1</span> Dashboard</a>
            <a href="#guide-tasks" class="guide-toc-item"><span class="guide-toc-num">2</span> Tasks</a>
            <a href="#guide-activity-log" class="guide-toc-item"><span class="guide-toc-num">3</span> Activity Log</a>
            <a href="#guide-notifications" class="guide-toc-item"><span class="guide-toc-num">4</span> Notifications</a>
            <a href="#guide-announcements" class="guide-toc-item"><span class="guide-toc-num">5</span> Announcements</a>
            <a href="#guide-faculty" class="guide-toc-item"><span class="guide-toc-num">6</span> Faculty Members</a>
            <a href="#guide-courses" class="guide-toc-item"><span class="guide-toc-num">7</span> Course Catalog</a>
            <a href="#guide-analytics" class="guide-toc-item"><span class="guide-toc-num">8</span> Analytics</a>
            <a href="#guide-documents" class="guide-toc-item"><span class="guide-toc-num">9</span> Documents</a>
            <a href="#guide-teaching" class="guide-toc-item"><span class="guide-toc-num">10</span> Pending Teaching Guides</a>
            <a href="#guide-exams" class="guide-toc-item"><span class="guide-toc-num">11</span> Pending Exam Questionnaires</a>
        @else
            <a href="#guide-dashboard" class="guide-toc-item"><span class="guide-toc-num">1</span> Dashboard</a>
            <a href="#guide-tasks" class="guide-toc-item"><span class="guide-toc-num">2</span> Tasks</a>
            <a href="#guide-activity-log" class="guide-toc-item"><span class="guide-toc-num">3</span> Activity Log</a>
            <a href="#guide-notifications" class="guide-toc-item"><span class="guide-toc-num">4</span> Notifications</a>
            <a href="#guide-announcements" class="guide-toc-item"><span class="guide-toc-num">5</span> Announcements</a>
            <a href="#guide-faculty" class="guide-toc-item"><span class="guide-toc-num">6</span> Faculty Members</a>
            <a href="#guide-courses" class="guide-toc-item"><span class="guide-toc-num">7</span> Course Catalog</a>
            <a href="#guide-analytics" class="guide-toc-item"><span class="guide-toc-num">8</span> Analytics</a>
            <a href="#guide-documents" class="guide-toc-item"><span class="guide-toc-num">9</span> Documents</a>
            <a href="#guide-recycle-bin" class="guide-toc-item"><span class="guide-toc-num">10</span> Recycle Bin</a>
            <a href="#guide-teaching" class="guide-toc-item"><span class="guide-toc-num">11</span> Pending Teaching Guides</a>
            <a href="#guide-exams" class="guide-toc-item"><span class="guide-toc-num">12</span> Pending Exam Questionnaires</a>
            <a href="#guide-archives" class="guide-toc-item"><span class="guide-toc-num">13</span> Archives</a>
        @endif
    </div>
</div>

{{-- ===== DASHBOARD ===== --}}
<div id="guide-dashboard" class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i> 1. Dashboard</h3>
    </div>
    <p class="guide-intro">The Dashboard is your home page. It summarizes your workload, recent activity, and quick actions.</p>
    <ol class="guide-steps">
        <li>Click <strong>Dashboard</strong> in the left sidebar.</li>
        <li>Review the <strong>statistics cards</strong> at the top for tasks, documents, and other counts relevant to your role.</li>
        @if($isFaculty)
        <li>Check <strong>My Tasks</strong> and recent updates assigned to you.</li>
        <li>Open shortcuts from the dashboard when you need to upload files or view pending submissions.</li>
        @elseif($isCoordinator)
        <li>Review department overview widgets and use quick links such as <strong>Post Announcement</strong> when needed.</li>
        @else
        <li>Use <strong>Show/Hide</strong> toggles on dashboard sections to expand or collapse details.</li>
        <li>From the Dean dashboard, use <strong>Create New Task</strong> to assign work across roles.</li>
        @endif
    </ol>
    <div class="guide-tip"><i class="fas fa-lightbulb mr-1"></i> <strong>Tip:</strong> The dashboard reloads each time you open it, so counts and lists stay current.</div>
</div>

{{-- ===== TASKS ===== --}}
<div id="guide-tasks" class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-tasks mr-2"></i> 2. {{ $isFaculty || $isCoordinator ? 'My Tasks' : 'Tasks' }}</h3>
    </div>
    <p class="guide-intro">Tasks help you track assignments, deadlines, and completion status.</p>
    <ol class="guide-steps">
        <li>Click <strong>{{ $isFaculty || $isCoordinator ? 'My Tasks' : 'Tasks' }}</strong> in the sidebar.</li>
        <li>Each task shows its <strong>title</strong>, <strong>status</strong>, <strong>priority</strong>, and <strong>due date</strong>.</li>
        @if($isFaculty || $isCoordinator)
        <li>Update the <strong>status</strong> of tasks assigned to you (for example, Pending → In Progress → Completed).</li>
        <li>Open a task to view details, comments, or attachments when provided.</li>
        @else
        <li>Click <strong>Create New Task</strong> to assign work. Set the title, description, assignee, priority, and due date.</li>
        <li>Edit or delete tasks you manage using the actions on the task list or detail page.</li>
        @endif
        <li>Use <strong>search and filters</strong> to find tasks quickly.</li>
    </ol>
    <div class="guide-tip"><i class="fas fa-lightbulb mr-1"></i> <strong>Tip:</strong> Update task status promptly so coordinators and the Dean see accurate progress.</div>
</div>

{{-- ===== ACTIVITY LOG ===== --}}
<div id="guide-activity-log" class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-history mr-2"></i> 3. Activity Log</h3>
    </div>
    <p class="guide-intro">The Activity Log records actions performed in the system, such as uploads, approvals, and task updates.</p>
    <ol class="guide-steps">
        <li>Click <strong>Activity Log</strong> in the sidebar.</li>
        <li>Browse entries to see <strong>who</strong> performed an action, <strong>what</strong> changed, and <strong>when</strong> it happened.</li>
        @if($isDeanOrSecretary)
        <li>Use this page to review department-wide activity and audit important changes.</li>
        @else
        <li>Use this page to review your own recent actions and track your work history.</li>
        @endif
        <li>Use available <strong>filters or search</strong> (if shown) to narrow the list.</li>
    </ol>
    <div class="guide-tip"><i class="fas fa-lightbulb mr-1"></i> <strong>Tip:</strong> Check the Activity Log when you need to confirm whether a file was uploaded or a submission was processed.</div>
</div>

{{-- ===== PERFORMANCE ANALYTICS (Faculty) ===== --}}
@if($isFaculty)
<div id="guide-analytics" class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i> 4. Performance Analytics</h3>
    </div>
    <p class="guide-intro">Performance Analytics shows charts and summaries related to your own work and submissions.</p>
    <ol class="guide-steps">
        <li>Click <strong>Performance Analytics</strong> in the sidebar.</li>
        <li>Review charts for task progress, document activity, and related metrics.</li>
        <li>Hover over chart elements to see exact values.</li>
    </ol>
    <div class="guide-tip"><i class="fas fa-lightbulb mr-1"></i> <strong>Tip:</strong> Use this page to monitor your productivity over the term.</div>
</div>
@endif

{{-- ===== ANNOUNCEMENTS ===== --}}
<div id="guide-announcements" class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-bullhorn mr-2"></i>
            @if($isFaculty) 5. @elseif($isCoordinator) 4. @elseif($isSecretary) 5. @else 5. @endif
            Announcements
        </h3>
    </div>
    <p class="guide-intro">Announcements share important news, reminders, and updates with selected roles.</p>
    <ol class="guide-steps">
        <li>Click <strong>Announcements</strong> in the sidebar.</li>
        <li>Unread items are marked with a <strong>blue dot</strong> and are auto-marked read after you view them for about 1.5 seconds.</li>
        <li>Click <strong>Mark as read</strong> to clear an item immediately.</li>
        <li>Read <strong>pinned</strong> announcements first — they stay at the top with a pin icon and highlighted border.</li>
        @if($isDean || $isCoordinator)
        <li>Click <strong>Post Announcement</strong> to publish a new item. Set the title, body, visibility (all roles or a specific role), and optional expiry.</li>
        <li>Use the <strong>three-dot menu (⋮)</strong> on your own posts to edit or delete them.</li>
        @endif
    </ol>
    <div class="guide-tip"><i class="fas fa-lightbulb mr-1"></i> <strong>Tip:</strong> Faculty and Secretary users can read announcements but cannot post them unless your role includes that button.</div>
</div>

{{-- ===== NOTIFICATIONS ===== --}}
@if($isFaculty || $isCoordinator || $isDeanOrSecretary)
<div id="guide-notifications" class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-bell mr-2"></i>
            @if($isFaculty) 6. @elseif($isCoordinator) 5. @elseif($isSecretary) 4. @else 4. @endif
            Notifications
        </h3>
    </div>
    <p class="guide-intro">Notifications alert you to task assignments, approvals, rejections, and other system events.</p>
    <ol class="guide-steps">
        <li>Click <strong>Notifications</strong> in the sidebar. A red badge shows your unread count.</li>
        <li>Open a notification to see details and follow any linked action.</li>
        <li>Mark individual notifications as read, or use <strong>Mark all as read</strong> to clear the list.</li>
        @if($isFaculty)
        <li>You receive notifications when teaching guides or exam questionnaires are <strong>approved</strong> or <strong>rejected</strong> by the Dean.</li>
        @endif
    </ol>
    <div class="guide-tip"><i class="fas fa-lightbulb mr-1"></i> <strong>Tip:</strong> Check notifications after uploading submissions so you catch approval results quickly.</div>
</div>
@endif

{{-- ===== FACULTY MEMBERS ===== --}}
@if(!$isFaculty)
<div id="guide-faculty" class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-users mr-2"></i> 6. Faculty Members</h3>
    </div>
    <p class="guide-intro">Faculty Members lists employee accounts in your department.</p>
    <ol class="guide-steps">
        <li>Click <strong>Faculty Members</strong> in the sidebar.</li>
        <li>Browse the directory for employee number, name, email, department, and status.</li>
        <li>Click <strong>View Profile</strong> to open a faculty member's full record.</li>
        @if($isDean)
        <li>Click <strong>Edit</strong> on a profile to update employee information.</li>
        <li>Use <strong>search or filters</strong> to find someone by name or department.</li>
        @elseif($isCoordinator)
        <li>Coordinators can view profiles but manage accounts through the Dean when changes are required.</li>
        @else
        <li>Secretary users can view profiles to support office operations; account edits are handled by the Dean.</li>
        @endif
    </ol>
    <div class="guide-tip"><i class="fas fa-lightbulb mr-1"></i> <strong>Tip:</strong> Accurate faculty records help with analytics and document ownership.</div>
</div>
@endif

{{-- ===== COURSE CATALOG (Coordinator / Dean / Secretary) ===== --}}
@if($isCoordinator || $isDeanOrSecretary)
<div id="guide-courses" class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-book mr-2"></i>
            @if($isCoordinator) 7. @else 7. @endif
            Course Catalog
        </h3>
    </div>
    @if($isCoordinator)
    <p class="guide-intro">The Course Catalog lists courses for your department only (Information Technology or Engineering). You have the same add, rename, remove, and restore controls as the Dean, scoped to your program.</p>
    <ol class="guide-steps">
        <li>Click <strong>Course Catalog</strong> in the sidebar under Management.</li>
        <li>Add a course with <strong>course code</strong> and <strong>title</strong>. Your department is fixed automatically.</li>
        <li>Use tabs: <strong>All courses</strong>, your <strong>department name</strong>, or <strong>Inactive</strong> for removed courses.</li>
        <li>Search by code or title, then use the <strong>⋮</strong> menu to <strong>Rename</strong>, <strong>Remove</strong>, or <strong>Restore</strong> a course.</li>
    </ol>
    @else
    <p class="guide-intro">The Course Catalog defines ITE and Engineering courses used when faculty and coordinators upload teaching guides and exam questionnaires.</p>
    <ol class="guide-steps">
        <li>Click <strong>Course Catalog</strong> in the sidebar.</li>
        <li>Add a course with <strong>course code</strong>, <strong>title</strong>, and <strong>department</strong>, then click <strong>Add Course</strong>.</li>
        <li>Filter by department or search by code/title to find existing entries.</li>
        <li>Edit or deactivate courses from the list when offerings change.</li>
    </ol>
    @endif
    <div class="guide-tip"><i class="fas fa-lightbulb mr-1"></i> <strong>Tip:</strong> Keep the catalog updated before each term so upload pickers show the correct subjects.</div>
</div>
@endif

{{-- ===== ANALYTICS (Coordinator / Dean / Secretary) ===== --}}
@if($isCoordinator || $isDeanOrSecretary)
<div id="guide-analytics" class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i>
            @if($isCoordinator) 8. @elseif($isSecretary) 8. @else 8. @endif
            Analytics
        </h3>
    </div>
    <p class="guide-intro">Analytics provides charts and summaries for department activity, tasks, and document trends.</p>
    <ol class="guide-steps">
        <li>Click <strong>Analytics</strong> in the sidebar.</li>
        <li>Review charts for task completion, uploads, and faculty activity.</li>
        <li>Hover over chart segments for detailed values.</li>
        <li>Apply any available <strong>date or department filters</strong> to focus the view.</li>
    </ol>
    <div class="guide-tip"><i class="fas fa-lightbulb mr-1"></i> <strong>Tip:</strong> Use Analytics at month-end to review overall department performance.</div>
</div>
@endif

{{-- ===== DOCUMENTS ===== --}}
<div id="guide-documents" class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-folder mr-2"></i>
            @if($isFaculty) 7.
            @elseif($isCoordinator) 9.
            @elseif($isSecretary) 9.
            @else 9.
            @endif
            Documents
        </h3>
    </div>
    <p class="guide-intro">Documents is the central file library. Files are grouped by category tabs and an academic folder hierarchy (school year, semester, subject, and type).</p>
    <ol class="guide-steps">
        <li>Click <strong>Documents</strong> in the sidebar.</li>
        <li>Switch between top-level <strong>category tabs</strong> (for example Academics, Accreditation, Custom Folders).</li>
        <li>Open folders using the cards or breadcrumb trail until you reach the correct upload location.</li>
        @if($isFaculty)
        <li>For <strong>Teaching Guides</strong> and <strong>Exam Questionnaires</strong>, navigate to the correct school year → semester → subject (and assessment type for exams), then upload. Submissions stay <strong>pending</strong> until the Dean approves them.</li>
        <li>For personal or department files, use <strong>Academics</strong> or <strong>Custom Folders</strong> as appropriate.</li>
        @else
        <li>Coordinators and Dean office roles can upload shared documents in Teaching Guides and Exam Questionnaires folders and assign recipients when prompted.</li>
        <li>Use <strong>Custom Folders</strong> to create your own subfolders for general files.</li>
        @endif
        <li>Click <strong>Upload</strong> at a leaf folder, choose your file(s), complete required fields (title, subject, type), and submit.</li>
        <li>Click a file name to <strong>view</strong> or use <strong>Download</strong>. Delete moves the file to the Recycle Bin (not permanent until removed from there).</li>
        <li>Use the <strong>filter panel and search</strong> below the tabs to find files by name, category, or uploader.</li>
    </ol>
    <div class="guide-tip"><i class="fas fa-lightbulb mr-1"></i> <strong>Tip:</strong> Follow the folder path shown on Teaching Guides and Exam Questionnaires pages to jump back to the upload location.</div>
</div>

{{-- ===== RECYCLE BIN ===== --}}
@if($isFaculty || $isCoordinator || $isDean)
<div id="guide-recycle-bin" class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-recycle mr-2"></i>
            @if($isFaculty) 8. @elseif($isCoordinator) 10. @else 10. @endif
            Recycle Bin
        </h3>
    </div>
    <p class="guide-intro">Deleted files from Documents are moved here first. You can restore them or remove them permanently.</p>
    <ol class="guide-steps">
        <li>Click <strong>Recycle Bin</strong> in the sidebar.</li>
        <li>Review deleted files with their <strong>original folder</strong> and <strong>deleted date</strong>.</li>
        <li>Click <strong>Restore</strong> to return a file to its folder (or to <strong>Uncategorized</strong> if that folder no longer exists).</li>
        @if($isDean)
        <li>As Dean, you may <strong>delete permanently</strong> items in the Recycle Bin. This cannot be undone.</li>
        @else
        <li>Permanent deletion from the Recycle Bin is limited to the Dean role.</li>
        @endif
    </ol>
    <div class="guide-tip"><i class="fas fa-lightbulb mr-1"></i> <strong>Tip:</strong> Restore files promptly if they were removed by mistake.</div>
</div>
@endif

{{-- ===== TEACHING GUIDES ===== --}}
<div id="guide-teaching" class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-book-open mr-2"></i>
            @if($isFaculty) 9.
            @elseif($isCoordinator) 11.
            @elseif($isSecretary) 10.
            @else 11.
            @endif
            @if($isDeanOrSecretary) Pending Teaching Guides @else Teaching Guides @endif
        </h3>
    </div>
    @if($isDeanOrSecretary)
    <p class="guide-intro">Review faculty teaching guide submissions, approve valid files, or reject submissions that need revision.</p>
    <ol class="guide-steps">
        <li>Click <strong>Pending Teaching Guides</strong> in the sidebar. The badge shows how many items await review.</li>
        <li>Filter by <strong>Status</strong> (pending, approved, rejected) and <strong>Semester</strong>, or search by title, subject, or faculty name.</li>
        <li><strong>View</strong> or <strong>Download</strong> a submission before deciding.</li>
        <li>Click <strong>Approve</strong> to publish the guide to the shared library and notify the uploader.</li>
        <li>Click <strong>Reject</strong>, enter a required reason, and submit. The faculty member is notified to revise and re-upload.</li>
    </ol>
    @elseif($isCoordinator)
    <p class="guide-intro">Upload and manage teaching guides for your department. You can also browse approved guides shared in your program.</p>
    <ol class="guide-steps">
        <li>Open <strong>Teaching Guides</strong> in the sidebar.</li>
        <li>Use the upload form: enter a <strong>title</strong>, pick the <strong>school year, semester, and subject</strong>, select <strong>recipients</strong>, and attach PDF or Word files.</li>
        <li>Browse the list of approved guides. Use <strong>View</strong> and <strong>Download</strong> to access files.</li>
        <li>You may also upload through <strong>Documents → Teaching Guides</strong> using the same folder hierarchy.</li>
    </ol>
    @else
    <p class="guide-intro">View approved teaching guides and track your own submissions through the Dean approval workflow.</p>
    <ol class="guide-steps">
        <li>Upload files from <strong>Documents → Teaching Guides</strong> (school year → semester → subject → TG/LB folder).</li>
        <li>After upload, the file is <strong>Pending</strong> until the Dean approves it. A badge on the sidebar shows your pending count.</li>
        <li>Open <strong>Teaching Guides</strong> to see pending items at the top and approved guides in the main list.</li>
        <li>While pending, you can <strong>rename</strong> the submission title. If rejected, read the remarks, fix the file, and upload again (or delete rejected items).</li>
        <li>Approved guides appear in the list with <strong>View</strong> and <strong>Download</strong>. The folder path under each title links back to Documents.</li>
    </ol>
    @endif
    <div class="guide-tip"><i class="fas fa-lightbulb mr-1"></i> <strong>Tip:</strong> Only approved teaching guides are visible to the wider department library.</div>
</div>

{{-- ===== EXAM QUESTIONNAIRES ===== --}}
<div id="guide-exams" class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-alt mr-2"></i>
            @if($isFaculty) 10.
            @elseif($isCoordinator) 12.
            @elseif($isSecretary) 11.
            @else 12.
            @endif
            @if($isDeanOrSecretary) Pending Exam Questionnaires @else Exam Questionnaires @endif
        </h3>
    </div>
    @if($isDeanOrSecretary)
    <p class="guide-intro">Review exam questionnaire submissions (TOQ, MCQ, and related types) before they are published.</p>
    <ol class="guide-steps">
        <li>Click <strong>Pending Exam Questionnaires</strong> in the sidebar. The badge shows pending items.</li>
        <li>Filter by status, semester, or search by subject, type, or faculty.</li>
        <li><strong>View</strong> or <strong>Download</strong> each file, then <strong>Approve</strong> or <strong>Reject</strong> with remarks.</li>
        <li>Approved questionnaires become available in the shared library; rejected ones return to the uploader for correction.</li>
    </ol>
    @elseif($isCoordinator)
    <p class="guide-intro">Upload and manage exam questionnaires for your department using the academic hierarchy.</p>
    <ol class="guide-steps">
        <li>Open <strong>Exam Questionnaires</strong> in the sidebar.</li>
        <li>Complete the upload form with title, school year, semester, subject, exam type, recipients, and files.</li>
        <li>Browse approved questionnaires with search and sort tools.</li>
        <li>Alternatively, upload through <strong>Documents → Exam Questionnaires</strong> down to the correct assessment folder.</li>
    </ol>
    @else
    <p class="guide-intro">View approved exam questionnaires and track your submissions through Dean approval.</p>
    <ol class="guide-steps">
        <li>Upload from <strong>Documents → Exam Questionnaires</strong> (school year → semester → subject → assessment type such as TOQ/MCQ).</li>
        <li>New uploads are <strong>Pending</strong> until approved. Check the sidebar badge and the pending section on this page.</li>
        <li>Rename pending submissions if needed. If rejected, review remarks and re-upload.</li>
        <li>Approved items appear in the list with <strong>View</strong>, <strong>Download</strong>, and a link to their folder in Documents.</li>
    </ol>
    @endif
    <div class="guide-tip"><i class="fas fa-lightbulb mr-1"></i> <strong>Tip:</strong> Label uploads with the correct course and term so reviewers can approve them faster.</div>
</div>

{{-- ===== ARCHIVES ===== --}}
@if($isFaculty || $isCoordinator || $isDean)
<div id="guide-archives" class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-box-archive mr-2"></i>
            @if($isFaculty) 11. @elseif($isCoordinator) 13. @else 13. @endif
            Archives
        </h3>
    </div>
    @if($isDean)
    <p class="guide-intro">Archives let you close out a school year and browse past terms. Only the Dean can archive the active school year.</p>
    <ol class="guide-steps">
        <li>Click <strong>Archives</strong> in the sidebar to open School Year Archives.</li>
        <li>Review the <strong>current school year</strong> counts for documents, teaching guides, and exam questionnaires.</li>
        <li>When a term ends, click <strong>Archive This School Year</strong>, confirm the archive name, and submit. This moves current data into an archive and starts a clean active year.</li>
        <li>Browse archived years below and click <strong>Browse</strong> to view read-only files from past terms.</li>
    </ol>
    @else
    <p class="guide-intro">Archives store completed school years. You can browse past documents, teaching guides, and exam questionnaires in read-only mode.</p>
    <ol class="guide-steps">
        <li>Click <strong>Archives</strong> in the sidebar.</li>
        <li>Select an archived school year and click <strong>Browse</strong>.</li>
        <li>View or download files from that year. You cannot upload into archived years.</li>
        <li>Use search on the archive detail page to find specific files.</li>
    </ol>
    @endif
    <div class="guide-tip"><i class="fas fa-lightbulb mr-1"></i> <strong>Tip:</strong> Coordinators and faculty should finish uploading for the term before the Dean archives the school year.</div>
</div>
@endif

{{-- ===== PROFILE (Faculty only) ===== --}}
@if($isFaculty)
<div id="guide-profile" class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user mr-2"></i> 12. Profile</h3>
    </div>
    <p class="guide-intro">Your Profile contains personal and professional information visible to administrators.</p>
    <ol class="guide-steps">
        <li>Click your <strong>name or avatar</strong> at the top-right of the screen.</li>
        <li>View your name, department, position, and contact details.</li>
        <li>Click <strong>Edit Profile</strong> to update your email or contact number.</li>
        <li>To change your <strong>password</strong>, use the Change Password section: enter your current password, then your new password, and save.</li>
        <li>Performance review entries appear on your profile when recorded by administrators.</li>
    </ol>
    <div class="guide-tip"><i class="fas fa-lightbulb mr-1"></i> <strong>Tip:</strong> Keep your contact information current so the Dean and coordinators can reach you.</div>
</div>
@endif

{{-- Back to Top --}}
<div class="guide-back-top">
    <a href="#" onclick="event.preventDefault(); window.scrollTo({top:0, behavior:'smooth'});" class="btn btn-secondary border-0"><i class="fas fa-arrow-up mr-1"></i> Back to Top</a>
</div>

@endsection
