<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DeanController;
use App\Http\Controllers\CoordinatorController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\SkillTagController;
use App\Http\Controllers\ProfessionalDevelopmentController;
use App\Http\Controllers\BackupController;

// Authentication Routes
Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login.page');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Global Search (All authenticated users)
Route::get('/search', [SearchController::class, 'search'])->middleware(['auth', 'no.back']);

// Profile Management (All authenticated users)
Route::middleware(['auth', 'no.back'])->prefix('profile')->name('profile.')->group(function () {
    Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
    Route::post('/update', [ProfileController::class, 'update'])->name('update');
    Route::post('/change-password', [ProfileController::class, 'changePassword'])->name('change-password');
});

// Calendar/Events (All authenticated users can view, only Dean/Coordinator can create/edit)
Route::middleware(['auth', 'no.back'])->prefix('calendar')->name('calendar.')->group(function () {
    Route::get('/', [CalendarController::class, 'index'])->name('index');
    Route::get('/events/json', [CalendarController::class, 'getEvents'])->name('events.json');
    
    // Only Dean and Coordinator can create/edit/delete events
    Route::middleware('role:Dean,Program Coordinator')->group(function () {
        Route::get('/create', [CalendarController::class, 'create'])->name('create');
        Route::post('/', [CalendarController::class, 'store'])->name('store');
        Route::put('/{id}', [CalendarController::class, 'update'])->name('update');
        Route::delete('/{id}', [CalendarController::class, 'destroy'])->name('destroy');
    });
    
    // Show and respond routes - MUST come after /create to avoid conflicts
    Route::get('/{id}', [CalendarController::class, 'show'])->name('show');
    Route::post('/{id}/respond', [CalendarController::class, 'respond'])->name('respond');
});

// Announcements (All authenticated users can view, only Dean/Coordinator can create/edit/delete)
Route::middleware(['auth', 'no.back'])->prefix('announcements')->name('announcements.')->group(function () {
    Route::get('/', [AnnouncementController::class, 'index'])->name('index');
    Route::post('/{id}/read', [AnnouncementController::class, 'markAsRead'])->name('read');


    // Only Dean and Coordinator can create/edit/delete announcements
    Route::middleware('role:Dean,Program Coordinator')->group(function () {
        Route::get('/create', [AnnouncementController::class, 'create'])->name('create');
        Route::post('/', [AnnouncementController::class, 'store'])->middleware('throttle:10,60')->name('store');
        Route::get('/{id}/edit', [AnnouncementController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AnnouncementController::class, 'update'])->name('update');
        Route::delete('/{id}', [AnnouncementController::class, 'destroy'])->name('destroy');
    });
});

// Skill Tags (All authenticated users can view, Faculty manages own)
Route::middleware(['auth', 'no.back'])->prefix('skill-tags')->name('skill-tags.')->group(function () {
    Route::get('/', [SkillTagController::class, 'index'])->name('index');
    Route::post('/', [SkillTagController::class, 'store'])->middleware('role:Faculty Employee')->name('store');
    Route::delete('/{id}', [SkillTagController::class, 'destroy'])->middleware('role:Faculty Employee')->name('destroy');
});

// Professional Development (All roles view, Faculty manages own)
Route::middleware(['auth', 'no.back'])->prefix('professional-development')->name('professional-development.')->group(function () {
    Route::get('/', [ProfessionalDevelopmentController::class, 'index'])->name('index');
    Route::post('/', [ProfessionalDevelopmentController::class, 'store'])->middleware('role:Faculty Employee')->name('store');
    Route::put('/{id}', [ProfessionalDevelopmentController::class, 'update'])->middleware('role:Faculty Employee')->name('update');
    Route::delete('/{id}', [ProfessionalDevelopmentController::class, 'destroy'])->middleware('role:Faculty Employee')->name('destroy');
});

// Dean Routes
Route::middleware(['auth', 'no.back', 'role:Dean'])->prefix('dean')->name('dean.')->group(function () {
    Route::get('/dashboard', [DeanController::class, 'dashboard'])->name('dashboard');
    Route::get('/employees', [DeanController::class, 'employees'])->name('employees');
    Route::get('/employees/{id}/profile', [DeanController::class, 'viewEmployeeProfile'])->name('employee-profile');

    // Account Management
    Route::post('/accounts/coordinator', [DeanController::class, 'storeCoordinator'])->name('store-coordinator');
    Route::post('/accounts/faculty', [DeanController::class, 'storeFaculty'])->name('store-faculty');
    Route::get('/accounts/{id}/edit', [DeanController::class, 'editEmployee'])->name('edit-employee');
    Route::patch('/accounts/{id}', [DeanController::class, 'updateEmployee'])->name('update-employee');
    Route::post('/accounts/{id}/reset-password', [DeanController::class, 'resetEmployeePassword'])->middleware('throttle:5,1')->name('reset-password');

    Route::get('/reports', [DeanController::class, 'reports'])->name('reports');
    Route::get('/analytics', [DeanController::class, 'analytics'])->name('analytics');

    // Tasks
    Route::get('/tasks', [DeanController::class, 'tasks'])->name('tasks');
    Route::get('/tasks/create', [DeanController::class, 'createTask'])->name('create-task');
    Route::post('/tasks', [DeanController::class, 'storeTask'])->name('store-task');
    Route::patch('/tasks/{id}', [DeanController::class, 'updateTask'])->name('update-task');

    Route::get('/documents', [DeanController::class, 'documents'])->name('documents');
    Route::post('/documents', [DeanController::class, 'uploadDocument'])->middleware('throttle:6,60')->name('upload-document');
    Route::post('/exam-records', [DeanController::class, 'storeExamRecord'])->middleware('throttle:10,60')->name('store-exam-record');
    Route::get('/documents/{id}/view', [DeanController::class, 'viewDocument'])->name('view-document');
    Route::get('/documents/{id}/download', [DeanController::class, 'downloadDocument'])->name('download-document');
    
    // Folder Management - Rate Limited: 3 folders per hour
    Route::post('/folders', [FolderController::class, 'store'])->middleware('throttle:3,60')->name('folders.store');
    Route::patch('/folders/{folder}', [FolderController::class, 'update'])->middleware('throttle:10,60')->name('folders.update');
    Route::delete('/folders/{folder}', [FolderController::class, 'destroy'])->middleware('throttle:10,60')->name('folders.destroy');
    Route::get('/folders/list', [FolderController::class, 'getUserFolders'])->name('folders.list');
    Route::post('/documents/{document}/move', [FolderController::class, 'moveDocument'])->name('documents.move');

    // Backup & Restore
    Route::get('/backup', [BackupController::class, 'index'])->name('backup');
    Route::post('/backup/create', [BackupController::class, 'create'])->name('backup.create');
    Route::get('/backup/download/{filename}', [BackupController::class, 'download'])->name('backup.download');
    Route::post('/backup/restore', [BackupController::class, 'restore'])->name('backup.restore');
    Route::delete('/backup/{filename}', [BackupController::class, 'destroy'])->name('backup.destroy');
});

// Program Coordinator Routes
Route::middleware(['auth', 'no.back', 'role:Program Coordinator'])->prefix('coordinator')->name('coordinator.')->group(function () {
    Route::post('/documents/{id}/favorite', [CoordinatorController::class, 'toggleFavorite'])->name('toggle-favorite');
    Route::get('/dashboard', [CoordinatorController::class, 'dashboard'])->name('dashboard');
    
    // Tasks (assigned to coordinator)
    Route::get('/tasks', [CoordinatorController::class, 'tasks'])->name('tasks');
    Route::patch('/tasks/{id}', [CoordinatorController::class, 'updateTask'])->name('update-task');
    
    // Faculty Management
    Route::get('/faculty', [CoordinatorController::class, 'faculty'])->name('faculty');
    Route::get('/faculty/create', [CoordinatorController::class, 'createFaculty'])->name('create-faculty');
    Route::post('/faculty', [CoordinatorController::class, 'storeFaculty'])->name('store-faculty');
    Route::get('/faculty/{id}/profile', [CoordinatorController::class, 'viewEmployeeProfile'])->name('faculty-profile');
    Route::get('/faculty/{id}/edit', [CoordinatorController::class, 'editFaculty'])->name('edit-faculty');
    Route::patch('/faculty/{id}', [CoordinatorController::class, 'updateFaculty'])->name('update-faculty');
    Route::post('/faculty/{id}/reset-password', [CoordinatorController::class, 'resetFacultyPassword'])->middleware('throttle:5,1')->name('reset-faculty-password');
    
    // Documents - Rate Limited: 6 uploads per hour
    Route::get('/documents', [CoordinatorController::class, 'documents'])->name('documents');
    Route::post('/documents', [CoordinatorController::class, 'uploadDocument'])->middleware('throttle:6,60')->name('upload-document');
    Route::post('/exam-records', [CoordinatorController::class, 'storeExamRecord'])->middleware('throttle:10,60')->name('store-exam-record');
    Route::get('/documents/{id}/view', [CoordinatorController::class, 'viewDocument'])->name('view-document');
    Route::get('/documents/{id}/download', [CoordinatorController::class, 'downloadDocument'])->name('download-document');
    
    // Folder Management - Rate Limited: 3 folders per hour
    Route::post('/folders', [FolderController::class, 'store'])->middleware('throttle:3,60')->name('folders.store');
    Route::patch('/folders/{folder}', [FolderController::class, 'update'])->middleware('throttle:10,60')->name('folders.update');
    Route::delete('/folders/{folder}', [FolderController::class, 'destroy'])->middleware('throttle:10,60')->name('folders.destroy');
    Route::get('/folders/list', [FolderController::class, 'getUserFolders'])->name('folders.list');
    Route::post('/documents/{document}/move', [FolderController::class, 'moveDocument'])->name('documents.move');
});

// Faculty Employee Routes
Route::middleware(['auth', 'no.back', 'role:Faculty Employee'])->prefix('faculty')->name('faculty.')->group(function () {
    Route::get('/dashboard', [FacultyController::class, 'dashboard'])->name('dashboard');
    Route::get('/tasks', [FacultyController::class, 'tasks'])->name('tasks');
    Route::patch('/tasks/{id}/status', [FacultyController::class, 'updateTaskStatus'])->name('update-task-status');
    Route::get('/notifications', [FacultyController::class, 'notifications'])->name('notifications');
    
    // Folder Management - Rate Limited: 3 folders per hour
    Route::post('/folders', [FolderController::class, 'store'])->middleware('throttle:3,60')->name('folders.store');
    Route::patch('/folders/{folder}', [FolderController::class, 'update'])->middleware('throttle:10,60')->name('folders.update');
    Route::delete('/folders/{folder}', [FolderController::class, 'destroy'])->middleware('throttle:10,60')->name('folders.destroy');
    Route::get('/folders/list', [FolderController::class, 'getUserFolders'])->name('folders.list');
    Route::post('/documents/{document}/move', [FolderController::class, 'moveDocument'])->name('documents.move');
    Route::post('/notifications/{id}/read', [FacultyController::class, 'markNotificationRead'])->name('mark-notification-read');
    
    // Documents - Rate Limited: 6 uploads per hour
    Route::get('/documents', [FacultyController::class, 'documents'])->name('documents');
    Route::post('/documents', [FacultyController::class, 'uploadDocument'])->middleware('throttle:6,60')->name('upload-document');
    Route::post('/exam-records', [FacultyController::class, 'storeExamRecord'])->middleware('throttle:10,60')->name('store-exam-record');
    Route::get('/documents/{id}/view', [FacultyController::class, 'viewDocument'])->name('view-document');
    Route::post('/documents/{id}/favorite', [FacultyController::class, 'toggleFavorite'])->name('toggle-favorite');
    Route::get('/documents/{id}/download', [FacultyController::class, 'downloadDocument'])->name('download-document');
    Route::get('/profile', [FacultyController::class, 'profile'])->name('profile');
});
