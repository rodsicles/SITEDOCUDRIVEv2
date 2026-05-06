<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementReaction;
use App\Models\Notification;
use App\Services\AnnouncementService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AnnouncementController extends Controller
{
    public function __construct(
        protected AnnouncementService $announcementService
    ) {}

    /** Allowed reaction emojis (server-side whitelist). */
    public const ALLOWED_REACTIONS = ['👍', '❤️', '✅', '🎉'];

    public function index()
    {
        $user = auth()->user();

        $announcements = Announcement::with(['author.employee', 'reads', 'reactions'])
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

    /**
     * Return read/unread breakdown of the announcement's target audience.
     * Restricted to the author or a Dean for accountability/follow-up.
     */
    public function receipts($id)
    {
        $user = auth()->user();
        $announcement = Announcement::where('announcement_id', $id)->firstOrFail();

        if ($announcement->author_id !== $user->id && !$user->isDean()) {
            abort(403);
        }

        $audience = $announcement->targetAudienceQuery()->get();
        $reads = $announcement->reads()->get()->keyBy('user_id');

        $read = [];
        $unread = [];
        foreach ($audience as $member) {
            $row = [
                'id'         => $member->id,
                'name'       => $member->employee->full_name ?? $member->name,
                'role'       => $member->role->role_name ?? '',
                'department' => $member->employee->department ?? '',
            ];
            if (isset($reads[$member->id])) {
                $row['read_at'] = optional($reads[$member->id]->read_at)->toIso8601String();
                $read[] = $row;
            } else {
                $unread[] = $row;
            }
        }

        return response()->json([
            'announcement_id' => $announcement->announcement_id,
            'title'           => $announcement->title,
            'total_audience'  => count($audience),
            'total_read'      => count($read),
            'total_unread'    => count($unread),
            'read'            => $read,
            'unread'          => $unread,
        ]);
    }

    /**
     * Toggle a reaction emoji on an announcement for the authenticated user.
     */
    public function toggleReaction(Request $request, $id)
    {
        $validated = $request->validate([
            'emoji' => ['required', 'string', Rule::in(self::ALLOWED_REACTIONS)],
        ]);

        $user = auth()->user();

        // Ensure the announcement exists and is visible to the user.
        $announcement = Announcement::visibleTo($user)
            ->where('announcement_id', $id)
            ->firstOrFail();

        $existing = AnnouncementReaction::where('announcement_id', $announcement->announcement_id)
            ->where('user_id', $user->id)
            ->where('emoji', $validated['emoji'])
            ->first();

        if ($existing) {
            $existing->delete();
            $reacted = false;
        } else {
            AnnouncementReaction::create([
                'announcement_id' => $announcement->announcement_id,
                'user_id'         => $user->id,
                'emoji'           => $validated['emoji'],
            ]);
            $reacted = true;
        }

        // Build counts for the whitelisted set so the UI gets a stable shape.
        $countsRaw = AnnouncementReaction::where('announcement_id', $announcement->announcement_id)
            ->selectRaw('emoji, COUNT(*) as total')
            ->groupBy('emoji')
            ->pluck('total', 'emoji');

        $counts = [];
        foreach (self::ALLOWED_REACTIONS as $emoji) {
            $counts[$emoji] = (int) ($countsRaw[$emoji] ?? 0);
        }

        $userReactions = AnnouncementReaction::where('announcement_id', $announcement->announcement_id)
            ->where('user_id', $user->id)
            ->pluck('emoji')
            ->all();

        return response()->json([
            'success'        => true,
            'emoji'          => $validated['emoji'],
            'reacted'        => $reacted,
            'counts'         => $counts,
            'user_reactions' => $userReactions,
        ]);
    }

    private function getRolePrefix(): string
    {
        $user = auth()->user();
        if ($user->isDean() || $user->isSecretary()) return 'dean';
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
