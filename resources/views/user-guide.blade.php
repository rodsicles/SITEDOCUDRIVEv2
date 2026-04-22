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

{{-- Welcome Banner --}}
<div class="guide-welcome-banner">
    <div class="guide-welcome-icon"><i class="fas fa-book-open"></i></div>
    <div>
        <div class="guide-welcome-title">Welcome, {{ auth()->user()->role->role_name }}</div>
        <div class="guide-welcome-sub">This guide covers all the features available to your role. Follow the steps to get the most out of the Employee Dashboard.</div>
    </div>
</div>

{{-- Table of Contents --}}
<div class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list mr-2"></i> Table of Contents</h3>
    </div>
    <div class="guide-toc">
        @if(auth()->user()->isFaculty())
            <a href="#guide-dashboard" class="guide-toc-item"><span class="guide-toc-num">1</span> Dashboard</a>
            <a href="#guide-tasks" class="guide-toc-item"><span class="guide-toc-num">2</span> Tasks</a>
            <a href="#guide-calendar" class="guide-toc-item"><span class="guide-toc-num">3</span> Calendar</a>
            <a href="#guide-announcements" class="guide-toc-item"><span class="guide-toc-num">4</span> Announcements</a>
            <a href="#guide-documents" class="guide-toc-item"><span class="guide-toc-num">5</span> Documents</a>
            <a href="#guide-teaching" class="guide-toc-item"><span class="guide-toc-num">6</span> Teaching Guides</a>
            <a href="#guide-exams" class="guide-toc-item"><span class="guide-toc-num">7</span> Exam Questionnaires</a>
            <a href="#guide-skills" class="guide-toc-item"><span class="guide-toc-num">8</span> Skill Tags</a>
            <a href="#guide-profile" class="guide-toc-item"><span class="guide-toc-num">9</span> Profile</a>
        @elseif(auth()->user()->isProgramCoordinator())
            <a href="#guide-dashboard" class="guide-toc-item"><span class="guide-toc-num">1</span> Dashboard</a>
            <a href="#guide-tasks" class="guide-toc-item"><span class="guide-toc-num">2</span> Tasks</a>
            <a href="#guide-calendar" class="guide-toc-item"><span class="guide-toc-num">3</span> Calendar</a>
            <a href="#guide-announcements" class="guide-toc-item"><span class="guide-toc-num">4</span> Announcements</a>
            <a href="#guide-faculty" class="guide-toc-item"><span class="guide-toc-num">5</span> Faculty Members</a>
            <a href="#guide-documents" class="guide-toc-item"><span class="guide-toc-num">6</span> Documents</a>
            <a href="#guide-teaching" class="guide-toc-item"><span class="guide-toc-num">7</span> Teaching Guides</a>
            <a href="#guide-exams" class="guide-toc-item"><span class="guide-toc-num">8</span> Exam Questionnaires</a>
        @else
            <a href="#guide-dashboard" class="guide-toc-item"><span class="guide-toc-num">1</span> Dashboard</a>
            <a href="#guide-tasks" class="guide-toc-item"><span class="guide-toc-num">2</span> Tasks</a>
            <a href="#guide-calendar" class="guide-toc-item"><span class="guide-toc-num">3</span> Calendar</a>
            <a href="#guide-announcements" class="guide-toc-item"><span class="guide-toc-num">4</span> Announcements</a>
            <a href="#guide-faculty" class="guide-toc-item"><span class="guide-toc-num">5</span> Faculty Members</a>
            <a href="#guide-reports" class="guide-toc-item"><span class="guide-toc-num">6</span> Reports</a>
            <a href="#guide-analytics" class="guide-toc-item"><span class="guide-toc-num">7</span> Analytics</a>
            <a href="#guide-documents" class="guide-toc-item"><span class="guide-toc-num">8</span> Documents</a>
            <a href="#guide-teaching" class="guide-toc-item"><span class="guide-toc-num">9</span> Teaching Guides</a>
            <a href="#guide-exams" class="guide-toc-item"><span class="guide-toc-num">10</span> Exam Questionnaires</a>
            <a href="#guide-skills" class="guide-toc-item"><span class="guide-toc-num">11</span> Skill Tags</a>
        @endif
    </div>
</div>

{{-- ===== DASHBOARD ===== --}}
<div id="guide-dashboard" class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i> 1. Dashboard</h3>
    </div>
    <p class="guide-intro">The Dashboard is your home page. It gives you a quick overview of your current status and recent activity.</p>
    <ol class="guide-steps">
        <li>Click <strong>Dashboard</strong> in the left sidebar to go to your home page.</li>
        <li>View your <strong>statistics</strong> at the top — these show counts of your tasks, documents, and other key data.</li>
        <li>Check <strong>Recent Tasks</strong> to see your latest pending and in-progress tasks.</li>
        <li>Check <strong>Recent Activities</strong> to see a log of recent actions done in the system.</li>
        @if(!auth()->user()->isFaculty())
        <li>Use the <strong>toggle buttons</strong> (Show/Hide) on each section to expand or collapse them as needed.</li>
        @endif
    </ol>
    <div class="guide-tip"><i class="fas fa-lightbulb mr-1"></i> <strong>Tip:</strong> The dashboard refreshes each time you navigate to it, so your data is always up to date.</div>
</div>

{{-- ===== TASKS ===== --}}
<div id="guide-tasks" class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-tasks mr-2"></i> 2. Tasks</h3>
    </div>
    <p class="guide-intro">The Tasks page lets you manage your work items. You can view, create, and track the progress of tasks.</p>
    <ol class="guide-steps">
        <li>Click <strong>Tasks</strong> in the sidebar to open the Tasks page.</li>
        <li>Browse the task list — each task shows its <strong>title</strong>, <strong>status</strong>, <strong>priority</strong>, and <strong>due date</strong>.</li>
        @if(auth()->user()->isFaculty())
        <li>Tasks assigned to you will appear here. You can update the <strong>status</strong> of a task (e.g., In Progress → Completed).</li>
        @else
        <li>Click <strong>Create Task</strong> to assign a new task. Fill in the title, description, assignee, priority, and due date.</li>
        <li>To update a task, click the <strong>Edit</strong> button beside it and change the details.</li>
        <li>To remove a task, click <strong>Delete</strong> and confirm.</li>
        @endif
        <li>Use the <strong>filter/search</strong> options to find specific tasks quickly.</li>
    </ol>
    <div class="guide-tip"><i class="fas fa-lightbulb mr-1"></i> <strong>Tip:</strong> Keep task statuses updated so the team always knows the current progress.</div>
</div>

{{-- ===== CALENDAR ===== --}}
<div id="guide-calendar" class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-calendar mr-2"></i> 3. Calendar</h3>
    </div>
    <p class="guide-intro">The Calendar shows all scheduled events. Use it to stay informed about upcoming activities and deadlines.</p>
    <ol class="guide-steps">
        <li>Click <strong>Calendar</strong> in the sidebar to open the Calendar page.</li>
        <li>Navigate between months using the <strong>left and right arrows</strong> at the top.</li>
        <li>Click on any <strong>event</strong> to view its full details (title, description, date, time, location).</li>
        @if(!auth()->user()->isFaculty())
        <li>Click <strong>Create Event</strong> to add a new calendar event. Fill in the event name, date, time, and description.</li>
        <li>You can <strong>edit or delete</strong> events you created using the buttons on the event detail page.</li>
        @endif
        <li>Events are color-coded — check the legend to understand what each color represents.</li>
    </ol>
    <div class="guide-tip"><i class="fas fa-lightbulb mr-1"></i> <strong>Tip:</strong> Check the calendar regularly before the start of each week to stay prepared.</div>
</div>

{{-- ===== ANNOUNCEMENTS ===== --}}
<div id="guide-announcements" class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-bullhorn mr-2"></i> 4. Announcements</h3>
    </div>
    <p class="guide-intro">Announcements keep the whole team informed about important news, updates, and reminders.</p>
    <ol class="guide-steps">
        <li>Click <strong>Announcements</strong> in the sidebar to view all announcements.</li>
        <li>Unread announcements are highlighted with a <strong>blue dot</strong> indicator. They will be automatically marked as read after you view them for 1.5 seconds.</li>
        <li>Click <strong>Mark as read</strong> manually if you want to mark it immediately.</li>
        <li>Pinned announcements (shown with a <strong>pin icon</strong> and green border) are important — read these first.</li>
        @if(!auth()->user()->isFaculty())
        <li>Click <strong>Post Announcement</strong> to create a new one. Fill in the title, body, visibility, and optionally set an expiry date.</li>
        <li>Use the <strong>three-dot menu (⋮)</strong> on your own announcements to <strong>Edit</strong> or <strong>Delete</strong> them.</li>
        <li>Check the <strong>visibility setting</strong> when posting — you can target All roles or a specific role.</li>
        @endif
    </ol>
    <div class="guide-tip"><i class="fas fa-lightbulb mr-1"></i> <strong>Tip:</strong> Pinned announcements stay at the top of the list so they are always visible.</div>
</div>

{{-- ===== FACULTY MEMBERS (Dean / Coordinator only) ===== --}}
@if(!auth()->user()->isFaculty())
<div id="guide-faculty" class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-users mr-2"></i> 5. Faculty Members</h3>
    </div>
    <p class="guide-intro">The Faculty Members page lets you view and manage the employee records in your department.</p>
    <ol class="guide-steps">
        <li>Click <strong>Faculty Members</strong> in the sidebar to open the employee list.</li>
        <li>Browse the list of all faculty employees — each row shows their name, role, department, and status.</li>
        <li>Click <strong>View Profile</strong> to see a faculty member's full profile including documents, skills, and performance data.</li>
        @if(auth()->user()->isDean())
        <li>Click <strong>Edit</strong> to update an employee's information.</li>
        <li>Use the <strong>search/filter</strong> at the top to quickly find a specific faculty member by name or department.</li>
        @endif
    </ol>
    <div class="guide-tip"><i class="fas fa-lightbulb mr-1"></i> <strong>Tip:</strong> Keep employee profiles updated for accurate reporting and analytics.</div>
</div>
@endif

{{-- ===== REPORTS (Dean only) ===== --}}
@if(auth()->user()->isDean() || auth()->user()->isSecretary())
<div id="guide-reports" class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-alt mr-2"></i> 6. Reports</h3>
    </div>
    <p class="guide-intro">The Reports page provides performance data summaries for your department's faculty.</p>
    <ol class="guide-steps">
        <li>Click <strong>Reports</strong> in the sidebar to open the performance reports page.</li>
        <li>Browse the report summaries for each faculty member in the list.</li>
        <li>Use the <strong>filter options</strong> to narrow down by department or date range.</li>
        <li>Click on a specific report entry to view its full details.</li>
    </ol>
    <div class="guide-tip"><i class="fas fa-lightbulb mr-1"></i> <strong>Tip:</strong> Use reports during evaluation periods to support performance reviews.</div>
</div>
@endif

{{-- ===== ANALYTICS (Dean only) ===== --}}
@if(auth()->user()->isDean() || auth()->user()->isSecretary())
<div id="guide-analytics" class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i> 7. Analytics</h3>
    </div>
    <p class="guide-intro">Analytics gives you a visual overview of department-wide data through charts and graphs.</p>
    <ol class="guide-steps">
        <li>Click <strong>Analytics</strong> in the sidebar to open the analytics dashboard.</li>
        <li>View charts for task completion rates, faculty activity, and document uploads.</li>
        <li>Hover over chart elements to see detailed data values.</li>
        <li>Use the date or department filters (if available) to narrow the data shown.</li>
    </ol>
    <div class="guide-tip"><i class="fas fa-lightbulb mr-1"></i> <strong>Tip:</strong> Analytics is best used at the end of the month to review overall department performance.</div>
</div>
@endif

{{-- ===== DOCUMENTS ===== --}}
<div id="guide-documents" class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-folder mr-2"></i>
            @if(auth()->user()->isFaculty()) 5. @elseif(auth()->user()->isProgramCoordinator()) 6. @else 8. @endif
            Documents
        </h3>
    </div>
    <p class="guide-intro">The Documents section is where you store, organize, and access files. Files are grouped into folders for easy navigation.</p>
    <ol class="guide-steps">
        <li>Click <strong>Documents</strong> in the sidebar to open your document library.</li>
        <li>Files are organized into <strong>folders</strong>. Click a folder to open it and see its files.</li>
        <li>To <strong>upload a file</strong>, open a folder then click the <strong>Upload</strong> button. Select your file and confirm.</li>
        <li>To <strong>create a new folder</strong>, click the <strong>New Folder</strong> button, enter a folder name, and save.</li>
        <li>To <strong>delete a file</strong>, find the file and click its <strong>Delete</strong> button, then confirm.</li>
        <li>Use the <strong>search bar</strong> at the top to find a specific document by name.</li>
        <li>The <strong>Uncategorized</strong> folder holds files that have not been placed in a specific folder.</li>
    </ol>
    <div class="guide-tip"><i class="fas fa-lightbulb mr-1"></i> <strong>Tip:</strong> Use descriptive folder names to keep your documents organized and easy to find.</div>
</div>

{{-- ===== TEACHING GUIDES ===== --}}
<div id="guide-teaching" class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-book-open mr-2"></i>
            @if(auth()->user()->isFaculty()) 6. @elseif(auth()->user()->isProgramCoordinator()) 7. @else 9. @endif
            Teaching Guides
        </h3>
    </div>
    <p class="guide-intro">Teaching Guides is a dedicated library for instructional materials and lesson resources.</p>
    <ol class="guide-steps">
        <li>Click <strong>Teaching Guides</strong> in the sidebar to open the library.</li>
        <li>Browse uploaded guides by subject or category.</li>
        <li>Click <strong>View</strong> or the file name to open and read a guide.</li>
        <li>To upload a new guide, click <strong>Upload Guide</strong>, select your file, fill in the title and subject, then save.</li>
        <li>To remove a guide, click the <strong>Delete</strong> button on its row and confirm.</li>
    </ol>
    <div class="guide-tip"><i class="fas fa-lightbulb mr-1"></i> <strong>Tip:</strong> Keep guides organized by subject so colleagues can find them easily.</div>
</div>

{{-- ===== EXAM QUESTIONNAIRES ===== --}}
<div id="guide-exams" class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-alt mr-2"></i>
            @if(auth()->user()->isFaculty()) 7. @elseif(auth()->user()->isProgramCoordinator()) 8. @else 10. @endif
            Exam Questionnaires
        </h3>
    </div>
    <p class="guide-intro">Exam Questionnaires is where exam files and question sets are stored and managed.</p>
    <ol class="guide-steps">
        <li>Click <strong>Exam Questionnaires</strong> in the sidebar to open the section.</li>
        <li>Browse the list of uploaded exam files.</li>
        <li>Click <strong>View</strong> to open and review a questionnaire.</li>
        <li>To upload a new questionnaire, click <strong>Upload</strong>, select the file, provide a title and subject, then save.</li>
        <li>To delete a questionnaire, click <strong>Delete</strong> on its row and confirm the action.</li>
    </ol>
    <div class="guide-tip"><i class="fas fa-lightbulb mr-1"></i> <strong>Tip:</strong> Label each questionnaire with the course code and term for quick identification.</div>
</div>

{{-- ===== SKILL TAGS (Faculty + Dean) ===== --}}
@if(auth()->user()->isFaculty() || auth()->user()->isDean() || auth()->user()->isSecretary())
<div id="guide-skills" class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-tags mr-2"></i>
            @if(auth()->user()->isFaculty()) 8. @else 11. @endif
            Skill Tags
        </h3>
    </div>
    <p class="guide-intro">Skill Tags allow faculty members to highlight their competencies and areas of expertise on their profile.</p>
    <ol class="guide-steps">
        <li>Click <strong>Skill Tags</strong> in the sidebar to open the Skill Tags page.</li>
        @if(auth()->user()->isFaculty())
        <li>Your existing skill tags are listed. Each tag represents a skill or competency.</li>
        <li>To <strong>add a new skill</strong>, type the skill name in the input field and click <strong>Add</strong>.</li>
        <li>To <strong>remove a skill</strong>, click the <strong>×</strong> button beside the tag.</li>
        <li>Your skill tags will appear on your employee profile for administrators to view.</li>
        @else
        <li>View the skill tags of all faculty members in the department.</li>
        <li>Use this page to understand the competency distribution across faculty.</li>
        @endif
    </ol>
    <div class="guide-tip"><i class="fas fa-lightbulb mr-1"></i> <strong>Tip:</strong> Keep your skill tags updated so your profile accurately reflects your expertise.</div>
</div>
@endif

{{-- ===== PROFILE (Faculty only) ===== --}}
@if(auth()->user()->isFaculty())
<div id="guide-profile" class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user mr-2"></i> 9. Profile</h3>
    </div>
    <p class="guide-intro">Your Profile page contains your personal and professional information visible to administrators.</p>
    <ol class="guide-steps">
        <li>Click your <strong>name or avatar</strong> at the top-right of the screen to access your profile.</li>
        <li>View your personal details: name, department, position, and contact info.</li>
        <li>Click <strong>Edit Profile</strong> to update your information — name, email, or contact number.</li>
        <li>To change your <strong>password</strong>, scroll to the Change Password section, enter your current password, then your new password, and save.</li>
        <li>Your <strong>Skill Tags</strong> are also visible on your profile page.</li>
    </ol>
    <div class="guide-tip"><i class="fas fa-lightbulb mr-1"></i> <strong>Tip:</strong> Keep your contact information up to date so administrators can reach you easily.</div>
</div>
@endif

{{-- Back to Top --}}
<div class="guide-back-top">
    <a href="#" class="btn btn-secondary"><i class="fas fa-arrow-up mr-1"></i> Back to Top</a>
</div>

@endsection
