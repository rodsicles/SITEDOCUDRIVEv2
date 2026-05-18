@extends('layouts.dashboard')

@section('title', 'Notifications - Faculty')

@section('page-title', 'Notifications')
@section('page-subtitle', 'View all your notifications')

@section('sidebar')
    @include('partials.faculty-sidebar')
@endsection

@section('content')
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">All Notifications</h3>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500 dark:text-gray-400 hidden md:inline">Tip: click an unread row to mark it as read</span>
                <span class="badge badge-info">{{ $notifications->total() }} Total</span>
            </div>
        </div>
        <table class="data-table" id="notifications-table">
            <thead>
                <tr>
                    <th>Message</th>
                    <th>Date & Time</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($notifications as $notification)
                <tr class="notification-row {{ !$notification->is_read ? 'bg-[#028a0f]/5 dark:bg-[#028a0f]/10 cursor-pointer' : '' }}"
                    @if(!$notification->is_read)
                        data-notification-id="{{ $notification->notification_id }}"
                        data-mark-url="{{ route('faculty.notifications.read-json', $notification->notification_id) }}"
                        title="Click to mark as read"
                    @endif>
                    <td class="{{ !$notification->is_read ? 'font-semibold' : '' }}">
                        @if(!$notification->is_read)
                            <span class="inline-block w-2 h-2 bg-[#028a0f] mr-2 align-middle"></span>
                        @endif
                        {{ $notification->message }}
                    </td>
                    <td>{{ $notification->created_at->format('M d, Y h:i A') }}</td>
                    <td>
                        @if($notification->is_read)
                            <span class="badge badge-success">Read</span>
                        @else
                            <span class="badge badge-warning">Unread</span>
                        @endif
                    </td>
                    <td>
                        @if(!$notification->is_read)
                        <form action="{{ route('faculty.mark-notification-read', $notification->notification_id) }}" method="POST" onclick="event.stopPropagation();">
                            @csrf
                            <button type="submit" class="btn btn-primary py-1.5 px-4 text-xs border-0">
                                Mark as Read
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-gray-500 dark:text-gray-400">
                        No notifications
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-5">
            {{ $notifications->links() }}
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Click a notification row -> mark as read via JSON, then update UI in place.
    (function() {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        document.querySelectorAll('.notification-row[data-mark-url]').forEach(function(row) {
            row.addEventListener('click', function(e) {
                if (e.target.closest('form, button, a')) return;
                const url = row.getAttribute('data-mark-url');
                if (!url || row.dataset.busy === '1') return;
                row.dataset.busy = '1';
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(function(res) { return res.ok ? res.json() : Promise.reject(); })
                .then(function() {
                    // Visually mark as read
                    row.classList.remove('bg-[#028a0f]/5', 'dark:bg-[#028a0f]/10', 'cursor-pointer');
                    row.removeAttribute('data-mark-url');
                    const msgCell = row.querySelector('td:first-child');
                    if (msgCell) {
                        msgCell.classList.remove('font-semibold');
                        const dot = msgCell.querySelector('span.inline-block.w-2.h-2');
                        if (dot) dot.remove();
                    }
                    const statusBadge = row.querySelector('td:nth-child(3) .badge');
                    if (statusBadge) {
                        statusBadge.classList.remove('badge-warning');
                        statusBadge.classList.add('badge-success');
                        statusBadge.textContent = 'Read';
                    }
                    const actionCell = row.querySelector('td:nth-child(4)');
                    if (actionCell) actionCell.innerHTML = '';
                    // Trigger badge refresh if available
                    if (typeof window.refreshNotificationBadge === 'function') {
                        window.refreshNotificationBadge();
                    }
                })
                .catch(function() { row.dataset.busy = ''; });
            });
        });
    })();
</script>
@endpush
