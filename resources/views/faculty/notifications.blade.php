@extends('layouts.dashboard')

@section('title', 'Notifications - Faculty')

@section('page-title', 'Notifications')
@section('page-subtitle', 'View all your notifications')

@section('sidebar')
    <a href="{{ route('faculty.dashboard') }}" class="menu-item">
        <i class="fas fa-chart-line"></i> Dashboard
    </a>
    <a href="{{ route('faculty.tasks') }}" class="menu-item">
        <i class="fas fa-tasks"></i> My Tasks
    </a>
    <a href="{{ route('faculty.notifications') }}" class="menu-item active">
        <i class="fas fa-bell"></i> Notifications
    </a>
    <a href="{{ route('faculty.profile') }}" class="menu-item">
        <i class="fas fa-user"></i> My Profile
    </a>
    <a href="{{ route('faculty.documents') }}" class="menu-item">
        <i class="fas fa-folder"></i> Documents
    </a>
@endsection

@section('content')
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">All Notifications</h3>
            <span class="badge badge-info">{{ $notifications->total() }} Total</span>
        </div>
        <table class="data-table">
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
                <tr class="{{ !$notification->is_read ? 'bg-[#028a0f]/5 dark:bg-[#028a0f]/10' : '' }}">
                    <td class="{{ !$notification->is_read ? 'font-semibold' : '' }}">
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
                        <form action="{{ route('faculty.mark-notification-read', $notification->notification_id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary py-1.5 px-4 text-xs">
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
