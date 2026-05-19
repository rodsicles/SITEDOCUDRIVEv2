<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DeanController;
use App\Http\Controllers\CoordinatorController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ProfessionalDevelopmentController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\TeachingGuideController;
use App\Http\Controllers\ExamQuestionnaireController;
use App\Http\Controllers\DocumentFilterController;
use App\Http\Controllers\DocumentRecipientController;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Controllers\SchoolYearController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\DeanCourseController;
use App\Http\Controllers\TgSubjectFolderController;
use App\Http\Controllers\EqSubjectFolderController;
use App\Http\Controllers\RecycleBinController;

// Authentication Routes
Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login.page');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1')->name('login.post');
// Public registration disabled – accounts are created by Dean/Secretary via admin panel.
// Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
// Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Mandatory password-change gate (locked overlay) for accounts created by an
// admin with a temporary password. Throttled to mitigate temp-password brute force.
Route::middleware('auth')->group(function () {
    Route::get('/password/force-change', [AuthController::class, 'showForceChange'])
        ->name('password.force-change.show');
    Route::post('/password/force-change', [AuthController::class, 'forceChange'])
        ->middleware('throttle:5,1')
        ->name('password.force-change.update');
});

// Global Search (All authenticated users)
Route::get('/search', [SearchController::class, 'search'])->middleware(['auth', 'no.back', 'throttle:20,1'])->name('search');

// Teaching Guides: open or create subject folder (TG/LB) from semester picker
Route::post('/documents/open-tg-subject', [TgSubjectFolderController::class, 'store'])
    ->middleware(['auth', 'no.back', 'throttle:30,1'])
    ->name('documents.open-tg-subject');

Route::post('/documents/open-eq-subject', [EqSubjectFolderController::class, 'store'])
    ->middleware(['auth', 'no.back', 'throttle:30,1'])
    ->name('documents.open-eq-subject');

// Profile Management (All authenticated users)
Route::middleware(['auth', 'no.back'])->prefix('profile')->name('profile.')->group(function () {
    Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
    Route::post('/update', [ProfileController::class, 'update'])->middleware('throttle:10,1')->name('update');
    Route::post('/change-password', [ProfileController::class, 'changePassword'])->middleware('throttle:10,1')->name('change-password');
});

Route::middleware(['auth', 'no.back'])->prefix('document-filters')->name('document-filters.')->group(function () {
    Route::post('/', [DocumentFilterController::class, 'store'])->middleware('throttle:10,60')->name('store');
    Route::delete('/{id}', [DocumentFilterController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth', 'no.back'])->prefix('task-attachments')->name('task-attachments.')->group(function () {
    Route::get('/{id}/download', [TaskAttachmentController::class, 'download'])->middleware('throttle:30,1')->name('download');
});

// Announcements (All authenticated users can view, only Dean/Coordinator can create/edit/delete)
Route::middleware(['auth', 'no.back'])->prefix('announcements')->name('announcements.')->group(function () {
    Route::get('/', [AnnouncementController::class, 'index'])->name('index');
    Route::post('/{id}/read', [AnnouncementController::class, 'markAsRead'])->middleware('throttle:60,1')->name('read');
    Route::post('/{id}/react', [AnnouncementController::class, 'toggleReaction'])
        ->middleware('throttle:60,1')
        ->name('react');
    Route::get('/{id}/receipts', [AnnouncementController::class, 'receipts'])
        ->middleware('throttle:60,1')
        ->name('receipts');


    // Only Dean and Coordinator can create/edit/delete announcements
    Route::middleware('role:Dean,Program Coordinator')->group(function () {
        Route::get('/create', [AnnouncementController::class, 'create'])->name('create');
        Route::post('/', [AnnouncementController::class, 'store'])->middleware('throttle:10,60')->name('store');
        Route::get('/{id}/edit', [AnnouncementController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AnnouncementController::class, 'update'])->name('update');
        Route::delete('/{id}', [AnnouncementController::class, 'destroy'])->name('destroy');
    });
});

// Professional Development (All roles view, Faculty manages own)
Route::middleware(['auth', 'no.back'])->prefix('professional-development')->name('professional-development.')->group(function () {
    Route::get('/', [ProfessionalDevelopmentController::class, 'index'])->name('index');
    Route::post('/', [ProfessionalDevelopmentController::class, 'store'])->middleware('role:Faculty Employee')->name('store');
    Route::put('/{id}', [ProfessionalDevelopmentController::class, 'update'])->middleware('role:Faculty Employee')->name('update');
    Route::delete('/{id}', [ProfessionalDevelopmentController::class, 'destroy'])->middleware('role:Faculty Employee')->name('destroy');
    Route::get('/{id}/certificate', [ProfessionalDevelopmentController::class, 'certificate'])->name('certificate');
});

// User Guide (All authenticated users)
Route::middleware(['auth', 'no.back'])->get('/user-guide', function () {
    return view('user-guide');
})->name('user-guide');

// Dean + Secretary Routes (Secretary has same access as Dean)
Route::middleware(['auth', 'no.back', 'role:Dean,Secretary'])->prefix('dean')->name('dean.')->group(function () {
    Route::get('/dashboard', [DeanController::class, 'dashboard'])->name('dashboard');
    Route::get('/employees', [DeanController::class, 'employees'])->name('employees');
    Route::get('/employees/{id}/profile', [DeanController::class, 'viewEmployeeProfile'])->name('employee-profile');

    // Account Management
    Route::post('/accounts/coordinator', [DeanController::class, 'storeCoordinator'])->middleware('throttle:10,60')->name('store-coordinator');
    Route::post('/accounts/faculty', [DeanController::class, 'storeFaculty'])->middleware('throttle:10,60')->name('store-faculty');
    Route::get('/accounts/{id}/edit', [DeanController::class, 'editEmployee'])->name('edit-employee');
    Route::patch('/accounts/{id}', [DeanController::class, 'updateEmployee'])->middleware('throttle:10,60')->name('update-employee');
    Route::post('/accounts/{id}/reset-password', [DeanController::class, 'resetEmployeePassword'])->middleware('throttle:5,1')->name('reset-password');

    Route::get('/courses', [DeanCourseController::class, 'index'])->name('courses');
    Route::post('/courses', [DeanCourseController::class, 'store'])->name('courses.store');
    Route::delete('/courses/{course}', [DeanCourseController::class, 'destroy'])->name('courses.destroy');
    Route::post('/courses/{course}/restore', [DeanCourseController::class, 'restore'])->name('courses.restore');

    Route::get('/reports', [DeanController::class, 'reports'])->name('reports');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
    Route::get('/activity-log', [DeanController::class, 'activityLog'])->name('activity-log');
    Route::get('/audit-trail', [DeanController::class, 'auditTrail'])->name('audit-trail');
    Route::post('/insight/refresh', [DeanController::class, 'refreshInsight'])
        ->middleware('throttle:6,1')
        ->name('insight.refresh');

    // Tasks
    Route::get('/tasks', [DeanController::class, 'tasks'])->name('tasks');
    Route::get('/tasks/create', [DeanController::class, 'createTask'])->name('create-task');
    Route::post('/tasks', [DeanController::class, 'storeTask'])->name('store-task');
    Route::patch('/tasks/{id}', [DeanController::class, 'updateTask'])->middleware('throttle:30,1')->name('update-task');
    // NOTE: Dean attachment-upload UI was removed; this route is intentionally not exposed.

    Route::get('/documents', [DeanController::class, 'documents'])->name('documents');
    Route::get('/documents/recipient-search', [DocumentRecipientController::class, 'search'])->name('documents.recipient-search');
    Route::post('/documents', [DeanController::class, 'uploadDocument'])->middleware('throttle:6,60')->name('upload-document');
    Route::post('/exam-records', [DeanController::class, 'storeExamRecord'])->middleware('throttle:10,60')->name('store-exam-record');
    Route::get('/documents/{id}/view', [DeanController::class, 'viewDocument'])->name('view-document');
    Route::get('/documents/{id}/download', [DeanController::class, 'downloadDocument'])->name('download-document');
    Route::delete('/documents/{id}', [DeanController::class, 'deleteDocument'])->name('delete-document');
    Route::patch('/documents/{id}/rename', [DeanController::class, 'renameDocument'])->middleware('throttle:60,1')->name('rename-document');

    Route::get('/recycle-bin', [RecycleBinController::class, 'index'])->name('recycle-bin.index');
    Route::post('/recycle-bin/{id}/restore', [RecycleBinController::class, 'restore'])->middleware('throttle:30,1')->name('recycle-bin.restore');
    Route::delete('/recycle-bin/{id}', [RecycleBinController::class, 'forceDelete'])->middleware('throttle:10,1')->name('recycle-bin.force-delete');

    // Folder Management - Rate Limited: 3 folders per hour
    Route::post('/folders', [FolderController::class, 'store'])->middleware('throttle:30,60')->name('folders.store');
    Route::patch('/folders/{folder}', [FolderController::class, 'update'])->middleware('throttle:10,60')->name('folders.update');
    Route::delete('/folders/{folder}', [FolderController::class, 'destroy'])->middleware('throttle:10,60')->name('folders.destroy');
    Route::get('/folders/list', [FolderController::class, 'getUserFolders'])->name('folders.list');
    Route::post('/documents/{document}/move', [FolderController::class, 'moveDocument'])->name('documents.move');

    // Backup & Restore - rate limited because operations are destructive / heavy
    Route::get('/backup', [BackupController::class, 'index'])->name('backup');
    Route::post('/backup/create', [BackupController::class, 'create'])->middleware('throttle:3,60')->name('backup.create');
    Route::get('/backup/download/{filename}', [BackupController::class, 'download'])->middleware('throttle:20,60')->name('backup.download');
    Route::post('/backup/restore', [BackupController::class, 'restore'])->middleware('throttle:2,60')->name('backup.restore');
    Route::delete('/backup/{filename}', [BackupController::class, 'destroy'])->middleware('throttle:10,60')->name('backup.destroy');

    // Teaching Guides (Dean + Secretary can upload, approve, reject, delete)
    Route::get('/teaching-guides', [TeachingGuideController::class, 'index'])->name('teaching-guides.index');
    Route::post('/teaching-guides', [TeachingGuideController::class, 'store'])->middleware('throttle:6,60')->name('teaching-guides.store');
    Route::get('/teaching-guides/{id}/download', [TeachingGuideController::class, 'download'])->name('teaching-guides.download');
    Route::post('/teaching-guides/{id}/approve', [TeachingGuideController::class, 'approve'])->middleware('throttle:30,1')->name('teaching-guides.approve');
    Route::post('/teaching-guides/{id}/reject', [TeachingGuideController::class, 'reject'])->middleware('throttle:30,1')->name('teaching-guides.reject');
    Route::delete('/teaching-guides/{id}', [TeachingGuideController::class, 'destroy'])->name('teaching-guides.destroy');

    // Exam Questionnaires
    Route::get('/exam-questionnaires', [ExamQuestionnaireController::class, 'index'])->name('exam-questionnaires.index');
    Route::get('/exam-questionnaires/{id}/view', [ExamQuestionnaireController::class, 'view'])->name('exam-questionnaires.view');
    Route::get('/exam-questionnaires/{id}/download', [ExamQuestionnaireController::class, 'download'])->name('exam-questionnaires.download');
    Route::post('/exam-questionnaires/{id}/approve', [ExamQuestionnaireController::class, 'approve'])->middleware('throttle:30,1')->name('exam-questionnaires.approve');
    Route::post('/exam-questionnaires/{id}/reject', [ExamQuestionnaireController::class, 'reject'])->middleware('throttle:30,1')->name('exam-questionnaires.reject');

    // Archives (Dean manages + browses)
    Route::get('/archives', [SchoolYearController::class, 'index'])->name('archives.index');
    Route::post('/archives', [SchoolYearController::class, 'archive'])->middleware('throttle:2,60')->name('archives.archive');
    Route::get('/archives/list', [SchoolYearController::class, 'list'])->name('archives.list');
    Route::get('/archives/{id}', [SchoolYearController::class, 'show'])->name('archives.show');
});

// Program Coordinator Routes
Route::middleware(['auth', 'no.back', 'role:Program Coordinator'])->prefix('coordinator')->name('coordinator.')->group(function () {
    Route::post('/documents/{id}/favorite', [CoordinatorController::class, 'toggleFavorite'])->middleware('throttle:60,1')->name('toggle-favorite');
    Route::get('/dashboard', [CoordinatorController::class, 'dashboard'])->name('dashboard');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
    
    // Tasks (assigned to coordinator)
    Route::get('/tasks', [CoordinatorController::class, 'tasks'])->name('tasks');
    Route::patch('/tasks/{id}', [CoordinatorController::class, 'updateTask'])->middleware('throttle:30,1')->name('update-task');
    Route::post('/tasks/{id}/attachments', [TaskAttachmentController::class, 'store'])->middleware('throttle:30,1')->name('tasks.attachments.store');

    Route::get('/notifications', [CoordinatorController::class, 'notifications'])->name('notifications');
    Route::get('/notifications/unread-count', [CoordinatorController::class, 'unreadNotificationCount'])->name('notifications.unread-count');
    Route::post('/notifications/{id}/read-json', [CoordinatorController::class, 'markNotificationReadJson'])->middleware('throttle:120,1')->name('notifications.read-json');
    Route::post('/notifications/{id}/read', [CoordinatorController::class, 'markNotificationRead'])->middleware('throttle:120,1')->name('mark-notification-read');
    
    // Faculty Management
    Route::get('/faculty', [CoordinatorController::class, 'faculty'])->name('faculty');
    Route::get('/faculty/{id}/profile', [CoordinatorController::class, 'viewEmployeeProfile'])->name('faculty-profile');
    Route::get('/faculty/{id}/edit', [CoordinatorController::class, 'editFaculty'])->name('edit-faculty');
    Route::patch('/faculty/{id}', [CoordinatorController::class, 'updateFaculty'])->middleware('throttle:10,60')->name('update-faculty');
    
    // Documents - Rate Limited: 6 uploads per hour
    Route::get('/documents', [CoordinatorController::class, 'documents'])->name('documents');
    Route::get('/documents/recipient-search', [DocumentRecipientController::class, 'search'])->name('documents.recipient-search');
    Route::post('/documents', [CoordinatorController::class, 'uploadDocument'])->middleware('throttle:6,60')->name('upload-document');
    Route::post('/exam-records', [CoordinatorController::class, 'storeExamRecord'])->middleware('throttle:10,60')->name('store-exam-record');
    Route::get('/documents/{id}/view', [CoordinatorController::class, 'viewDocument'])->name('view-document');
    Route::get('/documents/{id}/download', [CoordinatorController::class, 'downloadDocument'])->name('download-document');
    Route::delete('/documents/{id}', [CoordinatorController::class, 'deleteDocument'])->name('delete-document');
    Route::patch('/documents/{id}/rename', [CoordinatorController::class, 'renameDocument'])->middleware('throttle:60,1')->name('rename-document');

    Route::get('/recycle-bin', [RecycleBinController::class, 'index'])->name('recycle-bin.index');
    Route::post('/recycle-bin/{id}/restore', [RecycleBinController::class, 'restore'])->middleware('throttle:30,1')->name('recycle-bin.restore');

    // Folder Management - Rate Limited: 3 folders per hour
    Route::post('/folders', [FolderController::class, 'store'])->middleware('throttle:30,60')->name('folders.store');
    Route::patch('/folders/{folder}', [FolderController::class, 'update'])->middleware('throttle:10,60')->name('folders.update');
    Route::delete('/folders/{folder}', [FolderController::class, 'destroy'])->middleware('throttle:10,60')->name('folders.destroy');
    Route::get('/folders/list', [FolderController::class, 'getUserFolders'])->name('folders.list');
    Route::post('/documents/{document}/move', [FolderController::class, 'moveDocument'])->name('documents.move');

    // Teaching Guides (Coordinator can also upload)
    Route::get('/teaching-guides', [TeachingGuideController::class, 'index'])->name('teaching-guides.index');
    Route::post('/teaching-guides', [TeachingGuideController::class, 'store'])->middleware('throttle:6,60')->name('teaching-guides.store');
    Route::get('/teaching-guides/{id}/download', [TeachingGuideController::class, 'download'])->name('teaching-guides.download');
    Route::delete('/teaching-guides/{id}', [TeachingGuideController::class, 'destroy'])->name('teaching-guides.destroy');

    // Exam Questionnaires (coordinator may submit; Dean approves)
    Route::get('/exam-questionnaires', [ExamQuestionnaireController::class, 'index'])->name('exam-questionnaires.index');
    Route::post('/exam-questionnaires', [ExamQuestionnaireController::class, 'store'])->middleware('throttle:6,60')->name('exam-questionnaires.store');
    Route::get('/exam-questionnaires/{id}/view', [ExamQuestionnaireController::class, 'view'])->name('exam-questionnaires.view');
    Route::get('/exam-questionnaires/{id}/download', [ExamQuestionnaireController::class, 'download'])->name('exam-questionnaires.download');
    Route::get('/activity-log', [CoordinatorController::class, 'activityLog'])->name('activity-log');

    // Archives (browse only)
    Route::get('/archives', [SchoolYearController::class, 'list'])->name('archives.list');
    Route::get('/archives/{id}', [SchoolYearController::class, 'show'])->name('archives.show');
});

// Faculty Employee Routes
Route::middleware(['auth', 'no.back', 'role:Faculty Employee'])->prefix('faculty')->name('faculty.')->group(function () {
    Route::get('/dashboard', [FacultyController::class, 'dashboard'])->name('dashboard');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
    Route::get('/tasks', [FacultyController::class, 'tasks'])->name('tasks');
    Route::patch('/tasks/{id}/status', [FacultyController::class, 'updateTaskStatus'])->middleware('throttle:30,1')->name('update-task-status');
    Route::post('/tasks/{id}/attachments', [TaskAttachmentController::class, 'store'])->middleware('throttle:30,1')->name('tasks.attachments.store');
    Route::get('/notifications', [FacultyController::class, 'notifications'])->name('notifications');
    Route::get('/notifications/unread-count', [FacultyController::class, 'unreadNotificationCount'])->name('notifications.unread-count');
    Route::post('/notifications/{id}/read-json', [FacultyController::class, 'markNotificationReadJson'])->middleware('throttle:120,1')->name('notifications.read-json');
    
    // Folder Management - Rate Limited: 3 folders per hour
    Route::post('/folders', [FolderController::class, 'store'])->middleware('throttle:30,60')->name('folders.store');
    Route::patch('/folders/{folder}', [FolderController::class, 'update'])->middleware('throttle:10,60')->name('folders.update');
    Route::delete('/folders/{folder}', [FolderController::class, 'destroy'])->middleware('throttle:10,60')->name('folders.destroy');
    Route::get('/folders/list', [FolderController::class, 'getUserFolders'])->name('folders.list');
    Route::post('/documents/{document}/move', [FolderController::class, 'moveDocument'])->name('documents.move');
    Route::post('/notifications/{id}/read', [FacultyController::class, 'markNotificationRead'])->middleware('throttle:120,1')->name('mark-notification-read');
    
    // Documents - Rate Limited: 6 uploads per hour
    Route::get('/documents', [FacultyController::class, 'documents'])->name('documents');
    Route::post('/documents', [FacultyController::class, 'uploadDocument'])->middleware('throttle:6,60')->name('upload-document');
    Route::post('/exam-records', [FacultyController::class, 'storeExamRecord'])->middleware('throttle:10,60')->name('store-exam-record');
    Route::get('/documents/{id}/view', [FacultyController::class, 'viewDocument'])->name('view-document');
    Route::post('/documents/{id}/favorite', [FacultyController::class, 'toggleFavorite'])->middleware('throttle:60,1')->name('toggle-favorite');
    Route::get('/documents/{id}/download', [FacultyController::class, 'downloadDocument'])->name('download-document');
    Route::delete('/documents/{id}', [FacultyController::class, 'deleteDocument'])->name('delete-document');
    Route::patch('/documents/{id}/rename', [FacultyController::class, 'renameDocument'])->middleware('throttle:60,1')->name('rename-document');

    Route::get('/recycle-bin', [RecycleBinController::class, 'index'])->name('recycle-bin.index');
    Route::post('/recycle-bin/{id}/restore', [RecycleBinController::class, 'restore'])->middleware('throttle:30,1')->name('recycle-bin.restore');

    Route::get('/profile', [FacultyController::class, 'profile'])->name('profile');

    // Teaching Guides (Faculty: read-only, download only)
    Route::get('/teaching-guides', [TeachingGuideController::class, 'index'])->name('teaching-guides.index');
    Route::get('/teaching-guides/{id}/download', [TeachingGuideController::class, 'download'])->name('teaching-guides.download');

    // Exam Questionnaires (Faculty: submit and view own only)
    Route::get('/exam-questionnaires', [ExamQuestionnaireController::class, 'index'])->name('exam-questionnaires.index');
    Route::post('/exam-questionnaires', [ExamQuestionnaireController::class, 'store'])->middleware('throttle:6,60')->name('exam-questionnaires.store');
    Route::get('/exam-questionnaires/{id}/view', [ExamQuestionnaireController::class, 'view'])->name('exam-questionnaires.view');
    Route::get('/exam-questionnaires/{id}/download', [ExamQuestionnaireController::class, 'download'])->name('exam-questionnaires.download');
    Route::delete('/exam-questionnaires/{id}', [ExamQuestionnaireController::class, 'destroy'])->name('exam-questionnaires.destroy');
    Route::get('/activity-log', [FacultyController::class, 'activityLog'])->name('activity-log');

    // Archives (browse only)
    Route::get('/archives', [SchoolYearController::class, 'list'])->name('archives.list');
    Route::get('/archives/{id}', [SchoolYearController::class, 'show'])->name('archives.show');
});
