# Features Implementation Guide

This document contains all the code needed to implement the requested features for the SITE Employee Dashboard.

## ✅ Completed: Database Migrations

The following migrations have been created and run successfully:
- Department field (Engineering/Information Technology)
- Document categories and tags
- Document comments
- Employee skills & certifications

## 📋 Implementation Steps

### 1. Models Configuration

**app/Models/DocumentCategory.php**
```php
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DocumentCategory extends Model
{
    protected $primaryKey = 'category_id';
    protected $fillable = ['category_name', 'color'];
    
    public function documents()
    {
        return $this->hasMany(Document::class, 'category_id', 'category_id');
    }
}
```

**app/Models/DocumentComment.php**
```php
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DocumentComment extends Model
{
    protected $primaryKey = 'comment_id';
    protected $fillable = ['document_id', 'user_id', 'comment'];
    
    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id', 'document_id');
    }
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
```

**app/Models/EmployeeSkill.php**
```php
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class EmployeeSkill extends Model
{
    protected $primaryKey = 'skill_id';
    protected $fillable = [
        'employee_id', 'skill_name', 'skill_level', 
        'certification_name', 'certification_date', 'expiry_date'
    ];
    
    protected $casts = [
        'certification_date' => 'date',
        'expiry_date' => 'date',
    ];
    
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}
```

**Update app/Models/Document.php** - Add relationships:
```php
public function category()
{
    return $this->belongsTo(DocumentCategory::class, 'category_id', 'category_id');
}

public function comments()
{
    return $this->hasMany(DocumentComment::class, 'document_id', 'document_id');
}

// Update fillable array
protected $fillable = [
    'uploaded_by', 'document_title', 'file_path', 
    'document_type', 'category_id', 'tags'
];
```

**Update app/Models/Employee.php** - Add relationship:
```php
public function skills()
{
    return $this->hasMany(EmployeeSkill::class, 'employee_id', 'employee_id');
}
```

### 2. Seed Document Categories

**database/seeders/DocumentCategorySeeder.php**
```php
<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\DocumentCategory;

class DocumentCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['category_name' => 'Reports', 'color' => '#4CAF50'],
            ['category_name' => 'Assignments', 'color' => '#2196F3'],
            ['category_name' => 'Research', 'color' => '#9C27B0'],
            ['category_name' => 'Presentations', 'color' => '#FF9800'],
            ['category_name' => 'Certificates', 'color' => '#F44336'],
            ['category_name' => 'Forms', 'color' => '#00BCD4'],
            ['category_name' => 'Proposals', 'color' => '#3F51B5'],
            ['category_name' => 'Other', 'color' => '#607D8B'],
        ];

        foreach ($categories as $category) {
            DocumentCategory::create($category);
        }
    }
}
```

Run: `php artisan db:seed --class=DocumentCategorySeeder`

### 3. Toast Notification System

**Add to resources/views/layouts/dashboard.blade.php** (before closing body tag):
```html
<!-- Toast Container -->
<div id="toastContainer" style="position: fixed; top: 80px; right: 20px; z-index: 10000;"></div>

<script>
// Toast Notification System
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.style.cssText = `
        background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
        color: white;
        padding: 16px 24px;
        border-radius: 8px;
        margin-bottom: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 300px;
        animation: slideIn 0.3s ease;
    `;
    
    const icon = type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ';
    toast.innerHTML = `
        <span style="font-size: 20px; font-weight: bold;">${icon}</span>
        <span>${message}</span>
    `;
    
    document.getElementById('toastContainer').appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// Replace old alerts with toasts
@if(session('success'))
    showToast('{{ session("success") }}', 'success');
@endif

@if(session('error'))
    showToast('{{ session("error") }}', 'error');
@endif

// Add CSS animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(400px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(400px); opacity: 0; }
    }
`;
document.head.appendChild(style);
</script>
```

### 4. Document Categories & Tags Implementation

**Update CoordinatorController.php uploadDocument method:**
```php
public function uploadDocument(Request $request)
{
    $validated = $request->validate([
        'document_title' => 'required|string|max:150',
        'document' => 'required|file|max:10240',
        'document_type' => 'nullable|string|max:50',
        'category_id' => 'nullable|exists:document_categories,category_id',
        'tags' => 'nullable|string',
    ]);

    $file = $request->file('document');
    $filename = time() . '_' . $file->getClientOriginalName();
    $file->move(public_path('uploads/documents'), $filename);

    Document::create([
        'uploaded_by' => auth()->id(),
        'document_title' => $validated['document_title'],
        'file_path' => 'uploads/documents/' . $filename,
        'document_type' => $validated['document_type'],
        'category_id' => $validated['category_id'],
        'tags' => $validated['tags'],
    ]);

    return redirect()->back()->with('success', 'Document uploaded successfully');
}
```

**Update resources/views/coordinator/documents.blade.php** form:
```html
<div class="form-group">
    <label class="form-label">Category</label>
    <select name="category_id" class="form-control">
        <option value="">Select Category</option>
        @foreach(\App\Models\DocumentCategory::all() as $cat)
            <option value="{{ $cat->category_id }}">{{ $cat->category_name }}</option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label class="form-label">Tags (comma-separated)</label>
    <input type="text" name="tags" class="form-control" placeholder="e.g. urgent, confidential, review">
</div>
```

### 5. Bulk Document Upload

**Update upload forms** - Change single file input to multiple:
```html
<input type="file" name="documents[]" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png" multiple required>
```

**Update controller method:**
```php
public function uploadDocument(Request $request)
{
    $request->validate([
        'documents' => 'required|array',
        'documents.*' => 'file|max:10240',
        'document_titles' => 'nullable|array',
        'category_id' => 'nullable|exists:document_categories,category_id',
    ]);

    $uploadedCount = 0;
    foreach ($request->file('documents') as $index => $file) {
        $filename = time() . '_' . $file->getClientOriginalName();
        $file->move(public_path('uploads/documents'), $filename);

        Document::create([
            'uploaded_by' => auth()->id(),
            'document_title' => $request->document_titles[$index] ?? pathinfo($filename, PATHINFO_FILENAME),
            'file_path' => 'uploads/documents/' . $filename,
            'category_id' => $request->category_id,
        ]);
        $uploadedCount++;
    }

    return redirect()->back()->with('success', "$uploadedCount documents uploaded successfully");
}
```

### 6. Document Comments (Dean & Coordinator Only)

**Add route:**
```php
Route::post('/documents/{id}/comment', [DeanController::class, 'addDocumentComment'])->name('add-document-comment');
```

**Add to DeanController and CoordinatorController:**
```php
public function addDocumentComment(Request $request, $id)
{
    $validated = $request->validate([
        'comment' => 'required|string|max:1000',
    ]);

    DocumentComment::create([
        'document_id' => $id,
        'user_id' => auth()->id(),
        'comment' => $validated['comment'],
    ]);

    return redirect()->back()->with('success', 'Comment added successfully');
}
```

**Add comments section to document pages:**
```html
<!-- Comments Section -->
<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">Comments</h3>
    </div>
    
    @if(auth()->user()->isDean() || auth()->user()->isProgramCoordinator())
    <form action="{{ route('dean.add-document-comment', $document->document_id) }}" method="POST" style="margin-bottom: 20px;">
        @csrf
        <div class="form-group">
            <textarea name="comment" class="form-control" rows="3" placeholder="Add a comment..." required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-comment"></i> Add Comment
        </button>
    </form>
    @endif

    @foreach($document->comments as $comment)
    <div style="padding: 15px; background: var(--hover-bg); border-radius: 8px; margin-bottom: 10px;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
            <strong>{{ $comment->user->employee->full_name ?? $comment->user->username }}</strong>
            <span style="color: var(--text-light); font-size: 12px;">{{ $comment->created_at->diffForHumans() }}</span>
        </div>
        <p>{{ $comment->comment }}</p>
    </div>
    @endforeach
</div>
```

### 7. Skills & Certifications

**Add routes:**
```php
Route::get('/profile/skills', [ProfileController::class, 'skills'])->name('profile.skills');
Route::post('/profile/skills', [ProfileController::class, 'addSkill'])->name('profile.add-skill');
Route::delete('/profile/skills/{id}', [ProfileController::class, 'deleteSkill'])->name('profile.delete-skill');
```

**Add to ProfileController:**
```php
public function skills()
{
    $employee = auth()->user()->employee;
    $skills = $employee->skills()->orderBy('created_at', 'desc')->get();
    return view('profile.skills', compact('skills'));
}

public function addSkill(Request $request)
{
    $validated = $request->validate([
        'skill_name' => 'required|string|max:100',
        'skill_level' => 'required|in:Beginner,Intermediate,Advanced,Expert',
        'certification_name' => 'nullable|string|max:150',
        'certification_date' => 'nullable|date',
        'expiry_date' => 'nullable|date',
    ]);

    $employee = auth()->user()->employee;
    $employee->skills()->create($validated);

    return redirect()->back()->with('success', 'Skill added successfully');
}
```

**Create resources/views/profile/skills.blade.php:**
```html
@extends('layouts.dashboard')

@section('title', 'Skills & Certifications')
@section('page-title', 'Skills & Certifications')

@section('content')
<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">Add New Skill</h3>
    </div>
    <form action="{{ route('profile.add-skill') }}" method="POST">
        @csrf
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Skill Name *</label>
                <input type="text" name="skill_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Skill Level *</label>
                <select name="skill_level" class="form-control" required>
                    <option value="Beginner">Beginner</option>
                    <option value="Intermediate" selected>Intermediate</option>
                    <option value="Advanced">Advanced</option>
                    <option value="Expert">Expert</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Certification Name</label>
                <input type="text" name="certification_name" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Certification Date</label>
                <input type="date" name="certification_date" class="form-control">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Skill
        </button>
    </form>
</div>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">My Skills</h3>
        <span class="badge badge-info">{{ $skills->count() }} Skills</span>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;">
        @foreach($skills as $skill)
        <div style="padding: 15px; background: var(--hover-bg); border-radius: 8px; border-left: 4px solid var(--primary-color);">
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <strong style="font-size: 16px;">{{ $skill->skill_name }}</strong>
                <span class="badge badge-info">{{ $skill->skill_level }}</span>
            </div>
            @if($skill->certification_name)
                <p style="color: var(--text-light); font-size: 14px; margin: 5px 0;">
                    <i class="fas fa-certificate"></i> {{ $skill->certification_name }}
                </p>
            @endif
            @if($skill->certification_date)
                <p style="color: var(--text-light); font-size: 12px;">
                    Certified: {{ $skill->certification_date->format('M Y') }}
                </p>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endsection
```

### 8. Advanced Filters

**Add filter form to any listing page:**
```html
<div class="content-card" style="margin-bottom: 20px;">
    <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
        <div class="form-group" style="margin: 0;">
            <label class="form-label">Department</label>
            <select name="department" class="form-control">
                <option value="">All Departments</option>
                <option value="Engineering" {{ request('department') == 'Engineering' ? 'selected' : '' }}>Engineering</option>
                <option value="Information Technology" {{ request('department') == 'Information Technology' ? 'selected' : '' }}>Information Technology</option>
            </select>
        </div>
        <div class="form-group" style="margin: 0;">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
                <option value="">All Status</option>
                <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                <option value="Inactive" {{ request('status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <div class="form-group" style="margin: 0;">
            <label class="form-label">From Date</label>
            <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
        </div>
        <div class="form-group" style="margin: 0;">
            <label class="form-label">To Date</label>
            <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
        </div>
        <div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filter
            </button>
            <a href="{{ url()->current() }}" class="btn" style="background: #6c757d; color: white;">
                <i class="fas fa-times"></i> Clear
            </a>
        </div>
    </form>
</div>
```

**Update controller:**
```php
public function employees(Request $request)
{
    $query = Employee::with('user.role');
    
    if ($request->department) {
        $query->where('department', $request->department);
    }
    
    if ($request->status) {
        $query->whereHas('user', function($q) use ($request) {
            $q->where('status', $request->status);
        });
    }
    
    if ($request->from_date) {
        $query->where('hire_date', '>=', $request->from_date);
    }
    
    if ($request->to_date) {
        $query->where('hire_date', '<=', $request->to_date);
    }
    
    $employees = $query->paginate(15);
    return view('dean.employees', compact('employees'));
}
```

### 9. Document Preview Modal

**Add to dashboard layout (before closing body):**
```html
<!-- Document Preview Modal -->
<div id="documentPreviewModal" class="search-modal" style="align-items: center;">
    <div class="search-modal-content" style="max-width: 90%; max-height: 90vh; height: 90vh;">
        <div style="display: flex; justify-content: space-between; padding: 20px; border-bottom: 2px solid var(--border-color);">
            <h3 id="previewTitle" style="margin: 0;">Document Preview</h3>
            <button onclick="closePreview()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-dark);">×</button>
        </div>
        <iframe id="previewFrame" style="width: 100%; height: calc(100% - 80px); border: none;"></iframe>
    </div>
</div>

<script>
function openPreview(url, title) {
    document.getElementById('previewTitle').textContent = title;
    document.getElementById('previewFrame').src = url;
    document.getElementById('documentPreviewModal').classList.add('active');
}

function closePreview() {
    document.getElementById('documentPreviewModal').classList.remove('active');
    document.getElementById('previewFrame').src = '';
}
</script>
```

**Update view buttons:**
```html
<button onclick="openPreview('{{ route('faculty.view-document', $document->document_id) }}', '{{ $document->document_title }}')" class="btn btn-primary">
    <i class="fas fa-eye"></i> Preview
</button>
```

### 10. Activity Timeline

**Add to dashboard pages:**
```html
<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">Recent Activity</h3>
    </div>
    <div class="timeline">
        @foreach($recentActivities as $activity)
        <div class="timeline-item" style="padding-left: 40px; position: relative; padding-bottom: 30px; border-left: 2px solid var(--primary-light);">
            <div style="position: absolute; left: -8px; top: 0; width: 14px; height: 14px; border-radius: 50%; background: var(--primary-color);"></div>
            <div style="font-weight: 600; margin-bottom: 5px;">{{ $activity->user->employee->full_name ?? $activity->user->username }}</div>
            <div style="color: var(--text-dark);">{{ $activity->activity }}</div>
            <div style="color: var(--text-light); font-size: 12px; margin-top: 5px;">{{ $activity->log_date->diffForHumans() }}</div>
        </div>
        @endforeach
    </div>
</div>
```

### 11. PDF Export

Already installed: barryvdh/laravel-dompdf

**Add to config/app.php providers:**
```php
'providers' => [
    // ...
    Barryvdh\DomPDF\ServiceProvider::class,
],

'aliases' => [
    // ...
    'PDF' => Barryvdh\DomPDF\Facade::class,
],
```

**Add export routes:**
```php
Route::get('/employees/export-pdf', [DeanController::class, 'exportEmployeesPDF'])->name('export-employees-pdf');
Route::get('/tasks/export-pdf', [CoordinatorController::class, 'exportTasksPDF'])->name('export-tasks-pdf');
```

**Add export methods:**
```php
use PDF;

public function exportEmployeesPDF()
{
    $employees = Employee::with('user.role')->get();
    $pdf = PDF::loadView('exports.employees', compact('employees'));
    return $pdf->download('employees-' . date('Y-m-d') . '.pdf');
}
```

**Create resources/views/exports/employees.blade.php:**
```html
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #028a0f; color: white; }
        h1 { color: #028a0f; }
    </style>
</head>
<body>
    <h1>Employee List - {{ date('F d, Y') }}</h1>
    <table>
        <thead>
            <tr>
                <th>Employee No</th>
                <th>Name</th>
                <th>Department</th>
                <th>Position</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $employee)
            <tr>
                <td>{{ $employee->employee_no }}</td>
                <td>{{ $employee->full_name }}</td>
                <td>{{ $employee->department }}</td>
                <td>{{ $employee->position }}</td>
                <td>{{ $employee->user->status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
```

### 12. Real-time Updates with Pusher

**Install Pusher:**
```bash
composer require pusher/pusher-php-server
npm install --save-dev laravel-echo pusher-js
```

**Update .env:**
```
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=your_cluster
```

**Update config/broadcasting.php** - already configured

**Create notification event:**
```bash
php artisan make:event NewNotification
```

**app/Events/NewNotification.php:**
```php
<?php
namespace App\Events;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notification;
    public $userId;

    public function __construct($notification, $userId)
    {
        $this->notification = $notification;
        $this->userId = $userId;
    }

    public function broadcastOn()
    {
        return new Channel('notifications.' . $this->userId);
    }
}
```

**Trigger event when creating notifications:**
```php
$notification = Notification::create([...]);
event(new NewNotification($notification, $userId));
```

**Add to dashboard layout:**
```html
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    // Enable pusher logging
    Pusher.logToConsole = false;

    var pusher = new Pusher('{{ env("PUSHER_APP_KEY") }}', {
        cluster: '{{ env("PUSHER_APP_CLUSTER") }}'
    });

    var channel = pusher.subscribe('notifications.{{ auth()->id() }}');
    channel.bind('App\\Events\\NewNotification', function(data) {
        showToast(data.notification.message, 'info');
        // Update notification badge
        let badge = document.querySelector('.notification-badge');
        if (badge) {
            badge.textContent = parseInt(badge.textContent) + 1;
        }
    });
</script>
```

## 🎯 Next Steps

1. Run `php artisan db:seed --class=DocumentCategorySeeder`
2. Update all models with relationships
3. Implement toast notifications (replace old alerts)
4. Add document categories to upload forms
5. Implement bulk upload
6. Add comment functionality
7. Create skills management page
8. Add filters to all listing pages
9. Create document preview modal
10. Add activity timeline to dashboards
11. Implement PDF exports
12. Set up Pusher for real-time updates

## 📦 npm Packages Needed

```bash
npm install chart.js sortablejs
```

This will be needed for dashboard widgets drag-and-drop functionality.

---

**Note:** This is a comprehensive guide. Implement features one at a time and test thoroughly before moving to the next. Start with the simpler features (toast notifications, filters, categories) before tackling complex ones (real-time updates, drag-drop widgets).
