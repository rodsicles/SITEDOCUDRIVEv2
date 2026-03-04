<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Notification;
use App\Models\DashboardLog;
use App\Models\User;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $announcements = Announcement::with(['author.employee', 'reads'])
            ->active()
            ->visibleTo($user)
            ->ordered()
            ->paginate(10);

        $rolePrefix = $this->getRolePrefix();
        $sidebar = $this->getSidebarData();

        return view('announcements.index', compact('announcements', 'rolePrefix', 'sidebar'));
    }

    public function create()
    {
        $rolePrefix = $this->getRolePrefix();
        $sidebar = $this->getSidebarData();

        return view('announcements.create', compact('rolePrefix', 'sidebar'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
            'is_pinned' => 'boolean',
            'visibility' => 'required|in:All,Dean,Program Coordinator,Faculty Employee',
            'department' => 'required|in:All,Engineering,Information Technology',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $validated['author_id'] = auth()->id();
        $validated['is_pinned'] = $request->boolean('is_pinned');

        $announcement = Announcement::create($validated);

        // Notify target users
        $targetUsers = User::where('id', '!=', auth()->id())
            ->where('status', 'Active')
            ->when($validated['visibility'] !== 'All', function ($q) use ($validated) {
                $q->whereHas('role', function ($r) use ($validated) {
                    $r->where('role_name', $validated['visibility']);
                });
            })
            ->get();

        foreach ($targetUsers as $targetUser) {
            Notification::create([
                'user_id' => $targetUser->id,
                'message' => 'New announcement: ' . $validated['title'],
            ]);
        }

        DashboardLog::create([
            'user_id' => auth()->id(),
            'activity' => 'Posted announcement: "' . $validated['title'] . '"',
            'activity_type' => 'announcement',
            'visibility' => 'all',
        ]);

        return redirect()->route('announcements.index')
            ->with('success', 'Announcement posted successfully');
    }

    public function edit($id)
    {
        $announcement = Announcement::where('announcement_id', $id)
            ->where('author_id', auth()->id())
            ->firstOrFail();

        $rolePrefix = $this->getRolePrefix();
        $sidebar = $this->getSidebarData();

        return view('announcements.edit', compact('announcement', 'rolePrefix', 'sidebar'));
    }

    public function update(Request $request, $id)
    {
        $announcement = Announcement::where('announcement_id', $id)
            ->where('author_id', auth()->id())
            ->firstOrFail();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
            'is_pinned' => 'boolean',
            'visibility' => 'required|in:All,Dean,Program Coordinator,Faculty Employee',
            'department' => 'required|in:All,Engineering,Information Technology',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $validated['is_pinned'] = $request->boolean('is_pinned');
        $announcement->update($validated);

        DashboardLog::create([
            'user_id' => auth()->id(),
            'activity' => 'Updated announcement: "' . $validated['title'] . '"',
            'activity_type' => 'announcement',
            'visibility' => 'all',
        ]);

        return redirect()->route('announcements.index')
            ->with('success', 'Announcement updated successfully');
    }

    public function destroy($id)
    {
        $user = auth()->user();
        $announcement = Announcement::where('announcement_id', $id)->firstOrFail();

        // Only author or Dean can delete
        if ($announcement->author_id !== $user->id && !$user->isDean()) {
            abort(403);
        }

        $title = $announcement->title;
        $announcement->delete();

        DashboardLog::create([
            'user_id' => auth()->id(),
            'activity' => 'Deleted announcement: "' . $title . '"',
            'activity_type' => 'announcement',
            'visibility' => 'all',
        ]);

        return redirect()->route('announcements.index')
            ->with('success', 'Announcement deleted successfully');
    }

    public function markAsRead($id)
    {
        AnnouncementRead::firstOrCreate([
            'announcement_id' => $id,
            'user_id' => auth()->id(),
        ]);

        return response()->json(['success' => true]);
    }

    private function getRolePrefix(): string
    {
        $user = auth()->user();
        if ($user->isDean()) return 'dean';
        if ($user->isProgramCoordinator()) return 'coordinator';
        return 'faculty';
    }

    private function getSidebarData(): array
    {
        $user = auth()->user();
        $unreadNotifications = 0;

        if ($user->isFaculty()) {
            $unreadNotifications = Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->count();
        }

        return [
            'rolePrefix' => $this->getRolePrefix(),
            'unreadNotifications' => $unreadNotifications,
        ];
    }
}
