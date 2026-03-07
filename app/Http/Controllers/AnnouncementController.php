<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Notification;
use App\Services\AnnouncementService;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function __construct(
        protected AnnouncementService $announcementService
    ) {}

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

        $validated['is_pinned'] = $request->boolean('is_pinned');

        $this->announcementService->createAnnouncement($validated, auth()->user());

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

        $this->announcementService->updateAnnouncement($announcement, $validated, auth()->id());

        return redirect()->route('announcements.index')
            ->with('success', 'Announcement updated successfully');
    }

    public function destroy($id)
    {
        $user = auth()->user();
        $announcement = Announcement::where('announcement_id', $id)->firstOrFail();

        if ($announcement->author_id !== $user->id && !$user->isDean()) {
            abort(403);
        }

        $this->announcementService->deleteAnnouncement($announcement, auth()->id());

        return redirect()->route('announcements.index')
            ->with('success', 'Announcement deleted successfully');
    }

    public function markAsRead($id)
    {
        $this->announcementService->markAsRead($id, auth()->id());

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
