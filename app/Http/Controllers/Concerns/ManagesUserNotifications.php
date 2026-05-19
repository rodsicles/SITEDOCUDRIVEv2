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
