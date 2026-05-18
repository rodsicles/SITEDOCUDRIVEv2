<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Notification;
use App\Models\DashboardLog;
use App\Models\User;

class AnnouncementService
{
    /**
     * Create an announcement, notify target users, and log the activity.
     */
    public function createAnnouncement(array $validated, User $author): Announcement
    {
        $validated['author_id'] = $author->id;

        $announcement = Announcement::create($validated);

        $this->notifyTargetUsers($validated, $author);

        DashboardLog::create([
            'user_id' => $author->id,
            'activity' => 'Posted announcement: "' . $validated['title'] . '"',
            'activity_type' => 'announcement',
            'visibility' => 'all',
        ]);

        return $announcement;
    }

    /**
     * Update an announcement and log the activity.
     */
    public function updateAnnouncement(Announcement $announcement, array $validated, int $userId): Announcement
    {
        $announcement->update($validated);

        DashboardLog::create([
            'user_id' => $userId,
            'activity' => 'Updated announcement: "' . $validated['title'] . '"',
            'activity_type' => 'announcement',
            'visibility' => 'all',
        ]);

        return $announcement;
    }

    /**
     * Delete an announcement and log the activity.
     */
    public function deleteAnnouncement(Announcement $announcement, int $userId): void
    {
        $title = $announcement->title;
        $announcement->delete();

        DashboardLog::create([
            'user_id' => $userId,
            'activity' => 'Deleted announcement: "' . $title . '"',
            'activity_type' => 'announcement',
            'visibility' => 'all',
        ]);
    }

    /**
     * Mark an announcement as read by a user.
     */
    public function markAsRead(int $announcementId, int $userId): void
    {
        AnnouncementRead::firstOrCreate([
            'announcement_id' => $announcementId,
            'user_id' => $userId,
        ]);
    }

    /**
     * Notify target users about a new announcement.
     */
    private function notifyTargetUsers(array $validated, User $author): void
    {
        $targetUsers = User::where('id', '!=', $author->id)
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
    }
}
