<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;

trait ManagesUserNotifications
{
    public function notifications(Request $request)
    {
        $query = Notification::where('user_id', auth()->id());

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where('message', 'like', '%'.$search.'%');
        }

        $status = $request->query('status');
        if ($status === 'read') {
            $query->where('is_read', true);
        } elseif ($status === 'unread') {
            $query->where('is_read', false);
        }

        $tone = $request->query('tone');
        if ($tone === 'neutral') {
            $query->where(function ($q) {
                $q->whereNull('tone')
                    ->orWhereNotIn('tone', [Notification::TONE_SUCCESS, Notification::TONE_DANGER]);
            });
        } elseif (in_array($tone, [Notification::TONE_SUCCESS, Notification::TONE_DANGER], true)) {
            $query->where('tone', $tone);
        }

        $notifications = $query->latest()->paginate(20)->withQueryString();

        $unreadNotifications = Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();

        return view($this->notificationsView(), compact('notifications', 'unreadNotifications'));
    }

    public function unreadNotificationCount()
    {
        $count = Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    public function recentNotificationsJson()
    {
        $userId = auth()->id();
        $notifier = app(NotificationService::class);

        $notifications = Notification::where('user_id', $userId)
            ->latest()
            ->limit(8)
            ->get(['notification_id', 'message', 'tone', 'action_url', 'is_read', 'created_at', 'user_id']);

        $unreadCount = Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();

        $user = auth()->user();

        return response()->json([
            'notifications' => $notifications->map(function (Notification $n) use ($notifier, $user) {
                $actionUrl = $notifier->resolvedActionUrl($n, $user);

                return [
                    'id' => $n->notification_id,
                    'message' => $n->message,
                    'tone' => $n->tone,
                    'action_url' => ($actionUrl && str_starts_with($actionUrl, '/')) ? $actionUrl : null,
                    'is_read' => $n->is_read,
                    'time_ago' => $n->created_at->diffForHumans(),
                ];
            })->values(),
            'unread_count' => $unreadCount,
        ]);
    }

    public function markAllNotificationsReadJson()
    {
        $count = app(NotificationService::class)->markAllAsRead(auth()->id());

        return response()->json(['success' => true, 'count' => $count]);
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

    public function markAllNotificationsRead()
    {
        $count = app(NotificationService::class)->markAllAsRead(auth()->id());

        return redirect()->back()->with('success', $count > 0
            ? "{$count} notification(s) marked as read."
            : 'No unread notifications.');
    }

    abstract protected function notificationsView(): string;
}
