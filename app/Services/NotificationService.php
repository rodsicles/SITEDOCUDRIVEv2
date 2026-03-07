<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Create a notification for a single user.
     */
    public function notify(int $userId, string $message): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'message' => $message,
        ]);
    }

    /**
     * Notify multiple users with the same message.
     */
    public function notifyMany(array $userIds, string $message): void
    {
        foreach ($userIds as $userId) {
            Notification::create([
                'user_id' => $userId,
                'message' => $message,
            ]);
        }
    }

    /**
     * Notify all supervisors (Dean + Coordinator).
     */
    public function notifySupervisors(string $message): void
    {
        $supervisorIds = User::whereIn('role_id', [1, 2])->pluck('id')->toArray();
        $this->notifyMany($supervisorIds, $message);
    }

    /**
     * Mark a notification as read (owned by user).
     */
    public function markAsRead(int $notificationId, int $userId): void
    {
        $notification = Notification::where('notification_id', $notificationId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $notification->update(['is_read' => true]);
    }
}
