@extends('layouts.dashboard')

@section('title', 'Password Reset Requests')

@section('page-title', 'Password Reset Requests')
@section('page-subtitle', 'Review and act on password reset requests from employees')

@section('sidebar')
    @if(auth()->user()->isSecretary())
        @include('partials.secretary-sidebar')
    @else
        @include('partials.dean-sidebar')
    @endif
@endsection

@section('content')

{{-- One-time temporary password reveal banner --}}
@if($tempPassword)
<div class="content-card" style="border-left-color: #92400e;">
    <div class="card-header">
        <h3 class="card-title" style="color: #92400e;">
            <i class="fas fa-key"></i> Temporary Password (Show Once)
        </h3>
        <span class="badge badge-warning">Visible only on this page load</span>
    </div>
    <p class="text-sm text-gray-700 dark:text-gray-200 mb-3">
        Hand this temporary password to <strong>{{ $tempPasswordForUser }}</strong> in person or via a secure channel.
        It will <strong>not</strong> be shown again. The user will be forced to change it on first login.
    </p>
    <div class="flex items-center gap-3 flex-wrap">
        <code id="tempPasswordValue"
              style="font-family: monospace; font-size: 1.25rem; padding: 0.75rem 1rem; background: #fef3c7; color: #92400e; letter-spacing: 0.15em; user-select: all;">{{ $tempPassword }}</code>
        <button type="button" class="btn btn-sm btn-primary" onclick="copyTempPassword()">
            <i class="fas fa-copy"></i> Copy
        </button>
        <span id="tempCopiedNotice" class="text-sm text-green-700 dark:text-green-300" style="display:none;">
            <i class="fas fa-check"></i> Copied
        </span>
    </div>
    <p class="text-xs text-gray-600 dark:text-gray-400 mt-3">
        <i class="fas fa-info-circle"></i> Refreshing or navigating away will clear this value permanently.
    </p>
</div>
@endif

{{-- Flash notices --}}
@if(session('success') && !$tempPassword)
<div class="content-card" style="border-left-color: #047857;">
    <i class="fas fa-check-circle" style="color: #047857;"></i> {{ session('success') }}
</div>
@endif

@if($errors->any())
<div class="content-card" style="border-left-color: #b91c1c;">
    <i class="fas fa-exclamation-circle" style="color: #b91c1c;"></i> {{ $errors->first() }}
</div>
@endif

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">Requests</h3>
        <div class="flex items-center gap-2">
            <a href="{{ route('password-reset-requests.index', ['tab' => 'pending']) }}"
               class="btn btn-sm {{ $tab === 'pending' ? 'btn-primary' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                Pending
                @if($pendingCount > 0)
                <span class="badge badge-danger ml-2">{{ $pendingCount }}</span>
                @endif
            </a>
            <a href="{{ route('password-reset-requests.index', ['tab' => 'handled']) }}"
               class="btn btn-sm {{ $tab === 'handled' ? 'btn-primary' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                Recently Handled
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Requester</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Requested</th>
                    @if($tab === 'pending')
                        <th>Expires</th>
                    @else
                        <th>Handled</th>
                        <th>By</th>
                    @endif
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                <tr>
                    <td>
                        <strong>{{ $req->user->employee->full_name ?? $req->user->name ?? '—' }}</strong>
                    </td>
                    <td>{{ $req->user->username }}</td>
                    <td>{{ $req->user->role->role_name ?? '—' }}</td>
                    <td class="text-xs text-gray-600 dark:text-gray-400">
                        {{ $req->created_at->format('M d, Y g:i A') }}
                    </td>
                    @if($tab === 'pending')
                        <td class="text-xs {{ $req->expires_at->isPast() ? 'text-red-600' : 'text-gray-600' }} dark:text-gray-400">
                            {{ $req->expires_at->diffForHumans() }}
                        </td>
                    @else
                        <td class="text-xs text-gray-600 dark:text-gray-400">
                            {{ $req->handled_at ? $req->handled_at->format('M d, Y g:i A') : '—' }}
                        </td>
                        <td class="text-xs text-gray-600 dark:text-gray-400">
                            {{ $req->handler->employee->full_name ?? $req->handler->username ?? '—' }}
                        </td>
                    @endif
                    <td>
                        @switch($req->status)
                            @case(\App\Models\PasswordResetRequest::STATUS_PENDING)
                                <span class="badge badge-warning">Pending</span>
                                @break
                            @case(\App\Models\PasswordResetRequest::STATUS_APPROVED)
                                <span class="badge badge-success">Approved</span>
                                @break
                            @case(\App\Models\PasswordResetRequest::STATUS_DENIED)
                                <span class="badge badge-danger">Denied</span>
                                @break
                            @case(\App\Models\PasswordResetRequest::STATUS_EXPIRED)
                                <span class="badge badge-info">Expired</span>
                                @break
                        @endswitch
                    </td>
                    <td>
                        @if($req->status === \App\Models\PasswordResetRequest::STATUS_PENDING)
                            <div class="flex items-center gap-2">
                                <form action="{{ route('password-reset-requests.approve', $req->password_reset_request_id) }}"
                                      method="POST"
                                      onsubmit="return confirm('Approve this password reset? A one-time temporary password will be generated.');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                </form>
                                <button type="button" class="btn btn-sm" style="background:#b91c1c;color:#fff;"
                                        onclick="openDenyModal({{ $req->password_reset_request_id }}, '{{ addslashes($req->user->username) }}')">
                                    <i class="fas fa-times"></i> Deny
                                </button>
                            </div>
                        @else
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $req->reason ?: '—' }}
                            </span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $tab === 'pending' ? 7 : 8 }}" class="text-center text-gray-500 dark:text-gray-400 py-6">
                        @if($tab === 'pending')
                            No pending password reset requests.
                        @else
                            No handled requests yet.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($requests->hasPages())
    <div class="mt-3">
        {{ $requests->links() }}
    </div>
    @endif
</div>

{{-- Deny modal --}}
<div id="denyModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; max-width:30rem; width:90%; padding:1.5rem;" class="dark:bg-[#1f1f1f]">
        <h3 class="card-title mb-3" id="denyModalTitle">Deny Password Reset</h3>
        <form id="denyForm" method="POST" action="">
            @csrf
            <p class="text-sm text-gray-700 dark:text-gray-200 mb-3">
                You are denying the reset request for <strong id="denyUsername"></strong>.
                A short reason will be shown to them in their notification (optional).
            </p>
            <textarea name="reason" class="form-control text-sm w-full" maxlength="255" rows="3" placeholder="Optional reason..."></textarea>
            <div class="flex items-center gap-2 mt-4 justify-end">
                <button type="button" class="btn btn-sm bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200" onclick="closeDenyModal()">Cancel</button>
                <button type="submit" class="btn btn-sm" style="background:#b91c1c;color:#fff;">Deny Request</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function copyTempPassword() {
    var el = document.getElementById('tempPasswordValue');
    if (!el) return;
    var text = el.innerText;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function () {
            var notice = document.getElementById('tempCopiedNotice');
            if (notice) {
                notice.style.display = 'inline';
                setTimeout(function () { notice.style.display = 'none'; }, 2000);
            }
        });
    } else {
        var range = document.createRange();
        range.selectNode(el);
        window.getSelection().removeAllRanges();
        window.getSelection().addRange(range);
        try { document.execCommand('copy'); } catch (e) {}
    }
}

var denyBase = "{{ url('password-reset-requests') }}";

function openDenyModal(id, username) {
    var modal = document.getElementById('denyModal');
    var form = document.getElementById('denyForm');
    document.getElementById('denyUsername').innerText = username;
    form.action = denyBase + '/' + id + '/deny';
    modal.style.display = 'flex';
}

function closeDenyModal() {
    document.getElementById('denyModal').style.display = 'none';
}

document.getElementById('denyModal').addEventListener('click', function (e) {
    if (e.target === this) closeDenyModal();
});
</script>
@endpush
@endsection
