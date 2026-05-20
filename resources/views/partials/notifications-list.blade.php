@php
    use App\Services\DashboardService;

    $routePrefix = $routePrefix ?? match (true) {
        auth()->user()->isFaculty() => 'faculty',
        auth()->user()->isDeanOrSecretary() => 'dean',
        default => 'coordinator',
    };
    $unreadCount = (int) ($unreadNotifications ?? app(DashboardService::class)->getUnreadNotificationCount(auth()->id()));
    $showActionColumn = $unreadCount > 0 && $notifications->contains(fn ($n) => ! $n->is_read);
@endphp

<div class="content-card">
    <div class="card-header flex-col items-stretch gap-3 sm:flex-row sm:items-center">
        <h3 class="card-title mb-0">All Notifications</h3>
        <div class="flex flex-wrap items-center gap-2 self-start sm:ml-auto">
            @if($unreadCount > 0)
            <form action="{{ route($routePrefix . '.notifications.mark-all-read') }}" method="POST" class="m-0 notifications-mark-all-form" data-request-guard>
                @csrf
                <button type="submit" class="btn btn-primary text-sm border-0 whitespace-nowrap">
                    <i class="fas fa-check-double"></i> Mark all as read
                </button>
            </form>
            @endif
            <span class="badge badge-info">{{ $notifications->total() }} Total</span>
            @if($unreadCount > 0)
            <span class="badge badge-warning">{{ $unreadCount }} Unread</span>
            @endif
        </div>
    </div>

    <form action="{{ route($routePrefix . '.notifications') }}" method="GET" class="notifications-toolbar px-4 mb-3">
        <div class="notifications-toolbar-search">
            <i class="fas fa-search notifications-toolbar-icon" aria-hidden="true"></i>
            <input type="search" name="q" value="{{ request('q') }}" class="form-control text-sm" placeholder="Search...">
        </div>
        <select name="status" class="form-control text-sm notifications-toolbar-select" aria-label="Status">
            <option value="">All status</option>
            <option value="unread" @selected(request('status') === 'unread')>Unread</option>
            <option value="read" @selected(request('status') === 'read')>Read</option>
        </select>
        <select name="tone" class="form-control text-sm notifications-toolbar-select" aria-label="Type">
            <option value="">All types</option>
            <option value="success" @selected(request('tone') === 'success')>Approvals</option>
            <option value="danger" @selected(request('tone') === 'danger')>Alerts</option>
            <option value="neutral" @selected(request('tone') === 'neutral')>General</option>
        </select>
        <button type="submit" class="btn btn-primary text-sm">
            <i class="fas fa-filter"></i> Apply
        </button>
        @if(request()->hasAny(['q', 'status', 'tone']))
        <a href="{{ route($routePrefix . '.notifications') }}" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm">Reset</a>
        @endif
    </form>

    @if($showActionColumn)
    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3 px-4 hidden md:block notifications-table-tip">Tip: click an unread row to mark it as read</p>
    @endif

    <table class="data-table notifications-table {{ $showActionColumn ? '' : 'notifications-table--no-actions' }}" id="notifications-table">
        <thead>
            <tr>
                <th>Message</th>
                <th>Date & Time</th>
                <th>Status</th>
                @if($showActionColumn)
                <th class="notifications-table__action-col">Action</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($notifications as $notification)
            @php
                $tone = $notification->tone ?? null;
                $isDanger = $tone === \App\Models\Notification::TONE_DANGER;
                $isSuccess = $tone === \App\Models\Notification::TONE_SUCCESS;
                $rowClasses = 'notification-row';
                if (!$notification->is_read) {
                    $rowClasses .= ' cursor-pointer';
                    $rowClasses .= $isDanger ? ' notification-row--danger' : ($isSuccess ? ' notification-row--success' : ' bg-[#028a0f]/5 dark:bg-[#028a0f]/10');
                } elseif ($isDanger) {
                    $rowClasses .= ' notification-row--danger';
                }
            @endphp
            <tr class="{{ $rowClasses }}"
                @if(!$notification->is_read)
                    data-notification-id="{{ $notification->notification_id }}"
                    data-mark-url="{{ route($routePrefix . '.notifications.read-json', $notification->notification_id) }}"
                    data-tone="{{ $tone }}"
                    title="Click to mark as read"
                @endif>
                <td class="{{ !$notification->is_read ? 'font-semibold' : '' }}">
                    @if(!$notification->is_read)
                        <span class="inline-block w-2 h-2 mr-2 align-middle notification-dot {{ $isDanger ? '' : 'bg-[#028a0f]' }}"></span>
                    @endif
                    <span class="notification-message">{{ $notification->message }}</span>
                </td>
                <td>{{ $notification->created_at->format('M d, Y h:i A') }}</td>
                <td>
                    @if($notification->is_read)
                        <span class="badge badge-success">Read</span>
                    @else
                        <span class="badge badge-warning">Unread</span>
                    @endif
                </td>
                @if($showActionColumn)
                <td class="notifications-table__action-col">
                    @if(!$notification->is_read)
                    <form action="{{ route($routePrefix . '.mark-notification-read', $notification->notification_id) }}" method="POST" onclick="event.stopPropagation();">
                        @csrf
                        <button type="submit" class="btn btn-primary py-1.5 px-4 text-xs border-0">
                            Mark as Read
                        </button>
                    </form>
                    @endif
                </td>
                @endif
            </tr>
            @empty
            <tr>
                <td colspan="{{ $showActionColumn ? 4 : 3 }}" class="text-center text-gray-500 dark:text-gray-400">
                    No notifications match your filters.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-5 px-4">
        {{ $notifications->links('partials.pagination') }}
    </div>
</div>

@once
@push('scripts')
<script>
(function() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    function syncNotificationActionColumn() {
        const table = document.getElementById('notifications-table');
        if (!table) return;
        const hasUnread = table.querySelector('.notification-row[data-mark-url]');
        if (!hasUnread) {
            table.classList.add('notifications-table--no-actions');
            table.querySelectorAll('.notifications-table__action-col').forEach(function(el) {
                el.remove();
            });
            const tip = document.querySelector('.notifications-table-tip');
            if (tip) tip.remove();
        }
    }

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
                row.classList.remove('bg-[#028a0f]/5', 'dark:bg-[#028a0f]/10', 'cursor-pointer', 'notification-row--success', 'notification-row--danger');
                row.removeAttribute('data-mark-url');
                row.removeAttribute('data-tone');
                row.removeAttribute('title');
                const msgCell = row.querySelector('td:first-child');
                if (msgCell) {
                    msgCell.classList.remove('font-semibold');
                    const dot = msgCell.querySelector('.notification-dot, span.inline-block.w-2.h-2');
                    if (dot) dot.remove();
                }
                const statusBadge = row.querySelector('td .badge');
                if (statusBadge) {
                    statusBadge.classList.remove('badge-warning');
                    statusBadge.classList.add('badge-success');
                    statusBadge.textContent = 'Read';
                }
                const actionCell = row.querySelector('.notifications-table__action-col');
                if (actionCell) actionCell.remove();
                syncNotificationActionColumn();
                if (typeof window.refreshNotificationBadge === 'function') {
                    window.refreshNotificationBadge();
                }
            })
            .catch(function() { row.dataset.busy = ''; });
        });
    });

    syncNotificationActionColumn();
})();
</script>
@endpush
@endonce
