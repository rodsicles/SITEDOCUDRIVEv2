<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DeanController;
use App\Http\Controllers\CoordinatorController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\CalendarController;

// Authentication Routes
Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Global Search (All authenticated users)
Route::get('/search', [SearchController::class, 'search'])->middleware('auth');

// Profile Management (All authenticated users)
Route::middleware('auth')->prefix('profile')->name('profile.')->group(function () {
    Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
    Route::post('/update', [ProfileController::class, 'update'])->name('update');
    Route::post('/change-password', [ProfileController::class, 'changePassword'])->name('change-password');
});

// Leave Management (All authenticated users)
Route::middleware('auth')->prefix('leave')->name('leave.')->group(function () {
    Route::get('/', [LeaveController::class, 'index'])->name('index');
    Route::get('/create', [LeaveController::class, 'create'])->name('create');
    Route::post('/', [LeaveController::class, 'store'])->name('store');
    Route::post('/{id}/approve', [LeaveController::class, 'approve'])->name('approve');
    Route::post('/{id}/reject', [LeaveController::class, 'reject'])->name('reject');
    Route::get('/calendar', [LeaveController::class, 'calendar'])->name('calendar');
});

// Calendar/Events (All authenticated users can view, only Dean/Coordinator can create/edit)
Route::middleware('auth')->prefix('calendar')->name('calendar.')->group(function () {
    Route::get('/', [CalendarController::class, 'index'])->name('index');
    Route::get('/{id}', [CalendarController::class, 'show'])->name('show');
    Route::post('/{id}/respond', [CalendarController::class, 'respond'])->name('respond');
    Route::get('/events/json', [CalendarController::class, 'getEvents'])->name('events.json');
    
    // Only Dean and Coordinator can create/edit/delete events
    Route::middleware('role:Dean,Program Coordinator')->group(function () {
        Route::get('/create', [CalendarController::class, 'create'])->name('create');
        Route::post('/', [CalendarController::class, 'store'])->name('store');
        Route::put('/{id}', [CalendarController::class, 'update'])->name('update');
        Route::delete('/{id}', [CalendarController::class, 'destroy'])->name('destroy');
    });
});

// Dean Routes
Route::middleware(['auth', 'role:Dean'])->prefix('dean')->name('dean.')->group(function () {
    Route::get('/dashboard', [DeanController::class, 'dashboard'])->name('dashboard');
    Route::get('/employees', [DeanController::class, 'employees'])->name('employees');
    Route::get('/employees/{id}/profile', [DeanController::class, 'viewEmployeeProfile'])->name('employee-profile');
    Route::get('/reports', [DeanController::class, 'reports'])->name('reports');
    Route::get('/analytics', [DeanController::class, 'analytics'])->name('analytics');
    Route::get('/documents', [DeanController::class, 'documents'])->name('documents');
    Route::get('/documents/{id}/view', [DeanController::class, 'viewDocument'])->name('view-document');
    Route::get('/documents/{id}/download', [DeanController::class, 'downloadDocument'])->name('download-document');
});

// Program Coordinator Routes
Route::middleware(['auth', 'role:Program Coordinator'])->prefix('coordinator')->name('coordinator.')->group(function () {
    Route::post('/documents/{id}/favorite', [CoordinatorController::class, 'toggleFavorite'])->name('toggle-favorite');
    Route::get('/dashboard', [CoordinatorController::class, 'dashboard'])->name('dashboard');
    
    // Tasks
    Route::get('/tasks', [CoordinatorController::class, 'tasks'])->name('tasks');
    Route::get('/tasks/create', [CoordinatorController::class, 'createTask'])->name('create-task');
    Route::post('/tasks', [CoordinatorController::class, 'storeTask'])->name('store-task');
    Route::patch('/tasks/{id}', [CoordinatorController::class, 'updateTask'])->name('update-task');
    
    // Faculty Management
    Route::get('/faculty', [CoordinatorController::class, 'faculty'])->name('faculty');
    Route::get('/faculty/create', [CoordinatorController::class, 'createFaculty'])->name('create-faculty');
    Route::post('/faculty', [CoordinatorController::class, 'storeFaculty'])->name('store-faculty');
    Route::get('/faculty/{id}/profile', [CoordinatorController::class, 'viewEmployeeProfile'])->name('faculty-profile');
    Route::get('/faculty/{id}/edit', [CoordinatorController::class, 'editFaculty'])->name('edit-faculty');
    Route::patch('/faculty/{id}', [CoordinatorController::class, 'updateFaculty'])->name('update-faculty');
    Route::post('/faculty/{id}/reset-password', [CoordinatorController::class, 'resetFacultyPassword'])->name('reset-faculty-password');
    
    // Documents
    Route::get('/documents', [CoordinatorController::class, 'documents'])->name('documents');
    Route::post('/documents', [CoordinatorController::class, 'uploadDocument'])->name('upload-document');
    Route::get('/documents/{id}/view', [CoordinatorController::class, 'viewDocument'])->name('view-document');
    Route::get('/documents/{id}/download', [CoordinatorController::class, 'downloadDocument'])->name('download-document');
});

// Faculty Employee Routes
Route::middleware(['auth', 'role:Faculty Employee'])->prefix('faculty')->name('faculty.')->group(function () {
    Route::get('/dashboard', [FacultyController::class, 'dashboard'])->name('dashboard');
    Route::get('/tasks', [FacultyController::class, 'tasks'])->name('tasks');
    Route::patch('/tasks/{id}/status', [FacultyController::class, 'updateTaskStatus'])->name('update-task-status');
    Route::get('/notifications', [FacultyController::class, 'notifications'])->name('notifications');
    Route::post('/notifications/{id}/read', [FacultyController::class, 'markNotificationRead'])->name('mark-notification-read');
    Route::get('/documents', [FacultyController::class, 'documents'])->name('documents');
    Route::post('/documents', [FacultyController::class, 'uploadDocument'])->name('upload-document');
    Route::get('/documents/{id}/view', [FacultyController::class, 'viewDocument'])->name('view-document');
    Route::post('/documents/{id}/favorite', [FacultyController::class, 'toggleFavorite'])->name('toggle-favorite');
    Route::get('/documents/{id}/download', [FacultyController::class, 'downloadDocument'])->name('download-document');
    Route::get('/profile', [FacultyController::class, 'profile'])->name('profile');
});
