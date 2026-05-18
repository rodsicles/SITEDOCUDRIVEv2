<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Notification;
use App\Services\NotificationService;

trait ManagesUserNotifications
{
    public function notifications()
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->latest()
            ->paginate(20);

        return view($this->notificationsView(), compact('notifications'));
    }

    public function unreadNotificationCount()
    {
        $count = Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    public function markNotificationReadJson($id)
    {
        app(NotificationService::class)->markAsRead($id, auth()->id());

        return response()->json(['success' => true]);
    }

    public function markNotificationRead($id)
    {
        app(NotificationService::class)->markAsRead($id, auth()->id());

        return redirect()->back();
    }

    abstract protected function notificationsView(): string;
}
