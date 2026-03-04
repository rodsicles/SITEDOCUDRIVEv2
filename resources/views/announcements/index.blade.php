@extends('layouts.dashboard')

@section('title', 'Announcements')

@section('page-title', 'Announcements')
@section('page-subtitle', 'Stay updated with the latest news and updates')

@section('sidebar')
    @if($sidebar['rolePrefix'] === 'dean')
        <a href="{{ route('dean.dashboard') }}" class="menu-item">
            <i class="fas fa-chart-line"></i> Dashboard
        </a>
        <a href="{{ route('leave.index') }}" class="menu-item">
            <i class="fas fa-calendar-alt"></i> Leave Requests
        </a>
        <a href="{{ route('calendar.index') }}" class="menu-item">
            <i class="fas fa-calendar"></i> Calendar
        </a>
        <a href="{{ route('announcements.index') }}" class="menu-item active">
            <i class="fas fa-bullhorn"></i> Announcements
        </a>
        <a href="{{ route('dean.employees') }}" class="menu-item">
            <i class="fas fa-users"></i> Faculty Members
        </a>
        <a href="{{ route('dean.reports') }}" class="menu-item">
            <i class="fas fa-file-alt"></i> Performance Reports
        </a>
        <a href="{{ route('dean.analytics') }}" class="menu-item">
            <i class="fas fa-chart-pie"></i> Analytics
        </a>
        <a href="{{ route('dean.documents') }}" class="menu-item">
            <i class="fas fa-folder"></i> Documents
        </a>
    @elseif($sidebar['rolePrefix'] === 'coordinator')
        <a href="{{ route('coordinator.dashboard') }}" class="menu-item">
            <i class="fas fa-chart-line"></i> Dashboard
        </a>
        <a href="{{ route('coordinator.tasks') }}" class="menu-item">
            <i class="fas fa-tasks"></i> Tasks
        </a>
        <a href="{{ route('leave.index') }}" class="menu-item">
            <i class="fas fa-calendar-alt"></i> Leave Requests
        </a>
        <a href="{{ route('calendar.index') }}" class="menu-item">
            <i class="fas fa-calendar"></i> Calendar
        </a>
        <a href="{{ route('announcements.index') }}" class="menu-item active">
            <i class="fas fa-bullhorn"></i> Announcements
        </a>
        <a href="{{ route('coordinator.faculty') }}" class="menu-item">
            <i class="fas fa-users"></i> Faculty Members
        </a>
        <a href="{{ route('coordinator.documents') }}" class="menu-item">
            <i class="fas fa-folder"></i> Documents
        </a>
    @else
        <a href="{{ route('faculty.dashboard') }}" class="menu-item">
            <i class="fas fa-chart-line"></i> Dashboard
        </a>
        <a href="{{ route('faculty.tasks') }}" class="menu-item">
            <i class="fas fa-tasks"></i> My Tasks
        </a>
        <a href="{{ route('leave.index') }}" class="menu-item">
            <i class="fas fa-calendar-alt"></i> Leave Requests
        </a>
        <a href="{{ route('calendar.index') }}" class="menu-item">
            <i class="fas fa-calendar"></i> Calendar
        </a>
        <a href="{{ route('announcements.index') }}" class="menu-item active">
            <i class="fas fa-bullhorn"></i> Announcements
        </a>
        <a href="{{ route('faculty.notifications') }}" class="menu-item">
            <i class="fas fa-bell"></i> Notifications
            @if($sidebar['unreadNotifications'] > 0)
            <span class="badge badge-danger ml-auto">{{ $sidebar['unreadNotifications'] }}</span>
            @endif
        </a>
        <a href="{{ route('faculty.profile') }}" class="menu-item">
            <i class="fas fa-user"></i> My Profile
        </a>
        <a href="{{ route('faculty.documents') }}" class="menu-item">
            <i class="fas fa-folder"></i> Documents
        </a>
    @endif
@endsection

@section('content')
    <!-- Header with Create button -->
    @if(auth()->user()->isDean() || auth()->user()->isProgramCoordinator())
    <div class="flex justify-between items-center mb-6">
        <div>
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $announcements->total() }} announcement{{ $announcements->total() !== 1 ? 's' : '' }}</span>
        </div>
        <a href="{{ route('announcements.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i> Post Announcement
        </a>
    </div>
    @endif

    <!-- Announcements Feed -->
    <div class="announcement-feed">
        @forelse($announcements as $announcement)
            <div class="announcement-card {{ $announcement->is_pinned ? 'announcement-pinned' : '' }} {{ !$announcement->isReadBy(auth()->user()) ? 'announcement-unread' : '' }}" 
                 data-id="{{ $announcement->announcement_id }}"
                 id="announcement-{{ $announcement->announcement_id }}">
                
                <!-- Pin Badge -->
                @if($announcement->is_pinned)
                <div class="announcement-pin-badge">
                    <i class="fas fa-thumbtack"></i> Pinned
                </div>
                @endif

                <!-- Header -->
                <div class="announcement-header">
                    <div class="announcement-avatar">
                        {{ strtoupper(substr($announcement->author->username ?? 'A', 0, 2)) }}
                    </div>
                    <div class="announcement-meta">
                        <div class="announcement-author">
                            {{ $announcement->author->employee->full_name ?? $announcement->author->username }}
                            <span class="announcement-role-badge">{{ $announcement->author->role->role_name }}</span>
                        </div>
                        <div class="announcement-time">
                            <i class="far fa-clock"></i> {{ $announcement->created_at->diffForHumans() }}
                            @if($announcement->visibility !== 'All')
                                <span class="announcement-visibility-tag">
                                    <i class="fas fa-eye"></i> {{ $announcement->visibility }}
                                </span>
                            @endif
                            @if($announcement->department !== 'All')
                                <span class="announcement-dept-tag">
                                    <i class="fas fa-building"></i> {{ $announcement->department }}
                                </span>
                            @endif
                            @if($announcement->expires_at)
                                <span class="announcement-expires-tag" title="Expires {{ $announcement->expires_at->format('M d, Y h:i A') }}">
                                    <i class="fas fa-hourglass-half"></i> Expires {{ $announcement->expires_at->diffForHumans() }}
                                </span>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Action Menu (Author or Dean) -->
                    @if($announcement->author_id === auth()->id() || auth()->user()->isDean())
                    <div class="announcement-actions-menu">
                        <button class="announcement-menu-btn" onclick="toggleAnnouncementMenu({{ $announcement->announcement_id }})">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="announcement-dropdown" id="menu-{{ $announcement->announcement_id }}">
                            @if($announcement->author_id === auth()->id())
                            <a href="{{ route('announcements.edit', $announcement->announcement_id) }}" class="announcement-dropdown-item">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            @endif
                            <form action="{{ route('announcements.destroy', $announcement->announcement_id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this announcement?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="announcement-dropdown-item text-red-600 dark:text-red-400">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Content -->
                <div class="announcement-title">{{ $announcement->title }}</div>
                <div class="announcement-body">{!! nl2br(e($announcement->body)) !!}</div>

                <!-- Footer: Reactions & Read Status -->
                <div class="announcement-footer">
                    <div class="announcement-reactions">
                        @php
                            $emojis = ['👍', '❤️', '🎉', '👏', '💡'];
                            $reactionCounts = $announcement->reactions->groupBy('emoji')->map->count();
                            $userReactions = $announcement->reactions->where('user_id', auth()->id())->pluck('emoji')->toArray();
                        @endphp
                        @foreach($emojis as $emoji)
                            <button class="reaction-btn {{ in_array($emoji, $userReactions) ? 'reaction-active' : '' }}" 
                                    onclick="toggleReaction({{ $announcement->announcement_id }}, '{{ $emoji }}', this)"
                                    title="{{ $emoji }}">
                                <span class="reaction-emoji">{{ $emoji }}</span>
                                <span class="reaction-count">{{ $reactionCounts[$emoji] ?? 0 }}</span>
                            </button>
                        @endforeach
                    </div>
                    <div class="announcement-read-status">
                        @if($announcement->isReadBy(auth()->user()))
                            <span class="text-green-600 dark:text-green-400 text-xs"><i class="fas fa-check-double"></i> Read</span>
                        @else
                            <button class="mark-read-btn" onclick="markAsRead({{ $announcement->announcement_id }}, this)">
                                <i class="fas fa-check"></i> Mark as read
                            </button>
                        @endif
                        <span class="text-gray-400 dark:text-gray-500 text-xs ml-2">
                            <i class="fas fa-eye"></i> {{ $announcement->reads->count() }} read
                        </span>
                    </div>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-bullhorn"></i></div>
                <div class="empty-state-text">No announcements yet</div>
                @if(auth()->user()->isDean() || auth()->user()->isProgramCoordinator())
                <a href="{{ route('announcements.create') }}" class="btn btn-primary mt-4">Post the first announcement</a>
                @endif
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($announcements->hasPages())
    <div class="mt-6">
        {{ $announcements->links() }}
    </div>
    @endif

    <script>
        // Mark as read via AJAX
        function markAsRead(id, btn) {
            fetch(`/announcements/${id}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    btn.outerHTML = '<span class="text-green-600 dark:text-green-400 text-xs"><i class="fas fa-check-double"></i> Read</span>';
                    const card = document.getElementById('announcement-' + id);
                    if (card) card.classList.remove('announcement-unread');
                }
            });
        }

        // Toggle reaction via AJAX
        function toggleReaction(id, emoji, btn) {
            fetch(`/announcements/${id}/react`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ emoji })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    btn.classList.toggle('reaction-active');
                    const countEl = btn.querySelector('.reaction-count');
                    const reaction = data.reactions.find(r => r.emoji === emoji);
                    countEl.textContent = reaction ? reaction.count : 0;
                }
            });
        }

        // Toggle announcement action menu
        function toggleAnnouncementMenu(id) {
            const menu = document.getElementById('menu-' + id);
            document.querySelectorAll('.announcement-dropdown').forEach(m => {
                if (m !== menu) m.classList.remove('active');
            });
            menu.classList.toggle('active');
        }

        // Close menus on outside click
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.announcement-actions-menu')) {
                document.querySelectorAll('.announcement-dropdown').forEach(m => m.classList.remove('active'));
            }
        });

        // Auto-mark as read when scrolled into view
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && entry.target.classList.contains('announcement-unread')) {
                    const id = entry.target.dataset.id;
                    setTimeout(() => {
                        if (entry.target.classList.contains('announcement-unread')) {
                            fetch(`/announcements/${id}/read`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Accept': 'application/json',
                                }
                            }).then(r => r.json()).then(data => {
                                if (data.success) {
                                    entry.target.classList.remove('announcement-unread');
                                    const readBtn = entry.target.querySelector('.mark-read-btn');
                                    if (readBtn) {
                                        readBtn.outerHTML = '<span class="text-green-600 dark:text-green-400 text-xs"><i class="fas fa-check-double"></i> Read</span>';
                                    }
                                }
                            });
                        }
                    }, 2000);
                }
            });
        }, { threshold: 0.5 });

        document.querySelectorAll('.announcement-card.announcement-unread').forEach(card => {
            observer.observe(card);
        });
    </script>
@endsection
