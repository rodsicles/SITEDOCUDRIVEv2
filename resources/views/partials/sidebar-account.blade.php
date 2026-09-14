@php
    $accountUser = auth()->user();
    $accountName = $accountUser->employee->full_name ?? $accountUser->username;
    $accountRole = $accountUser->role->role_name ?? 'Employee';
    $accountAvatar = $accountUser->avatarUrl();
@endphp

<div class="sidebar-account {{ request()->routeIs('profile.*') ? 'is-active' : '' }}">
    <div id="userMenu" class="sidebar-account-menu hidden" role="menu" aria-label="Account menu">
        <div class="sidebar-account-menu__identity">
            <strong>{{ $accountName }}</strong>
            <span>{{ $accountRole }}</span>
        </div>
        <a href="{{ route('profile.edit') }}" role="menuitem">
            <i class="fas fa-user-pen" aria-hidden="true"></i>
            View and edit profile
        </a>
        <form action="{{ route('logout') }}" method="POST" id="logoutForm">
            @csrf
            <button type="submit" role="menuitem">
                <i class="fas fa-arrow-right-from-bracket" aria-hidden="true"></i>
                Sign out
            </button>
        </form>
    </div>

    <button type="button" id="userMenuBtn" class="sidebar-account-trigger" aria-haspopup="menu" aria-expanded="false" aria-controls="userMenu">
        <span class="sidebar-account-avatar">
            @if($accountAvatar)
                <img src="{{ $accountAvatar }}" alt="Profile photo of {{ $accountName }}">
            @else
                <span aria-hidden="true">{{ $accountUser->initials() }}</span>
            @endif
        </span>
        <span class="sidebar-account-copy">
            <strong title="{{ $accountName }}">{{ $accountName }}</strong>
            <small>{{ $accountRole }}</small>
        </span>
        <i class="fas fa-chevron-up sidebar-account-chevron" aria-hidden="true"></i>
    </button>
</div>
