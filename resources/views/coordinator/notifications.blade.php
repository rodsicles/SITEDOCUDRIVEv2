@extends('layouts.dashboard')

@section('title', 'Notifications - Program Coordinator')

@section('page-title', 'Notifications')
@section('page-subtitle', 'View all your notifications')

@section('sidebar')
    @include('partials.coordinator-sidebar')
@endsection

@section('content')
    @include('partials.notifications-list', ['routePrefix' => 'coordinator'])
@endsection

@push('scripts')
<script>
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
