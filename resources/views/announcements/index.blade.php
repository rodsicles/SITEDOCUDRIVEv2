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

    @if(session('success'))
    <div class="alert alert-success">
        <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
    </div>
    @endif

    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-bullhorn mr-2 text-[#028a0f] dark:text-[#02b815]"></i>
                Announcements
                <span class="badge badge-info ml-2">{{ $announcements->total() }}</span>
            </h3>
            @if(auth()->user()->isDean() || auth()->user()->isProgramCoordinator())
            <a href="{{ route('announcements.create') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> Post Announcement
            </a>
            @endif
        </div>

        @forelse($announcements as $announcement)

        {{-- Announcement Card --}}
        <div id="announcement-{{ $announcement->announcement_id }}"
             data-id="{{ $announcement->announcement_id }}"
             data-unread="{{ $announcement->isReadBy(auth()->user()) ? '0' : '1' }}"
             class="mb-4 rounded-xl border transition-all duration-200
                {{ $announcement->is_pinned
                    ? 'border-l-4 border-[#028a0f] dark:border-[#02b815] bg-green-50 dark:bg-[#1a2a1a]'
                    : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-[#1e1e1e]' }}
                {{ !$announcement->isReadBy(auth()->user()) ? 'shadow-[0_0_0_2px_rgba(59,130,246,0.3)]' : '' }}">

            {{-- Pinned indicator --}}
            @if($announcement->is_pinned)
            <div class="flex items-center gap-1.5 px-5 pt-3 text-[#028a0f] dark:text-[#02b815]">
                <i class="fas fa-thumbtack text-xs"></i>
                <span class="text-[0.68rem] font-bold uppercase tracking-widest">Pinned</span>
            </div>
            @endif

            <div class="p-5">
                {{-- Author row --}}
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="flex items-center gap-3 min-w-0">
                        {{-- Avatar --}}
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                             style="background:linear-gradient(135deg,#028a0f,#026a0c);">
                            {{ strtoupper(substr($announcement->author->username ?? 'A', 0, 2)) }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                                    {{ $announcement->author->employee->full_name ?? $announcement->author->username }}
                                </span>
                                <span class="px-2 py-0.5 text-[0.65rem] font-semibold rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                    {{ $announcement->author->role->role_name }}
                                </span>
                                @if(!$announcement->isReadBy(auth()->user()))
                                <span class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0"></span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 mt-1 flex-wrap text-xs text-gray-500 dark:text-gray-400">
                                <span><i class="far fa-clock mr-0.5"></i>{{ $announcement->created_at->diffForHumans() }}</span>
                                @if($announcement->visibility !== 'All')
                                <span class="badge badge-info py-0.5 px-2 text-[0.65rem]">
                                    <i class="fas fa-eye mr-0.5"></i>{{ $announcement->visibility }}
                                </span>
                                @endif
                                @if($announcement->department !== 'All')
                                <span class="px-2 py-0.5 rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 text-[0.65rem] font-semibold">
                                    <i class="fas fa-building mr-0.5"></i>{{ $announcement->department }}
                                </span>
                                @endif
                                @if($announcement->expires_at)
                                <span class="px-2 py-0.5 rounded-full bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 text-[0.65rem] font-semibold"
                                      title="Expires {{ $announcement->expires_at->format('M d, Y h:i A') }}">
                                    <i class="fas fa-hourglass-half mr-0.5"></i>Expires {{ $announcement->expires_at->diffForHumans() }}
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Action menu --}}
                    @if($announcement->author_id === auth()->id() || auth()->user()->isDean())
                    <div class="relative flex-shrink-0" id="menu-wrapper-{{ $announcement->announcement_id }}">
                        <button type="button"
                                onclick="toggleMenu({{ $announcement->announcement_id }})"
                                class="w-8 h-8 rounded-full flex items-center justify-center text-gray-400 dark:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 transition-colors bg-transparent border-none cursor-pointer">
                            <i class="fas fa-ellipsis-v text-sm"></i>
                        </button>
                        <div id="menu-{{ $announcement->announcement_id }}"
                             class="hidden absolute right-0 top-full mt-1 w-32 bg-white dark:bg-[#2a2a2a] rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50 overflow-hidden">
                            @if($announcement->author_id === auth()->id())
                            <a href="{{ route('announcements.edit', $announcement->announcement_id) }}"
                               class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 no-underline">
                                <i class="fas fa-edit w-4 text-center"></i> Edit
                            </a>
                            @endif
                            <form action="{{ route('announcements.destroy', $announcement->announcement_id) }}" method="POST"
                                  onsubmit="return confirm('Delete this announcement?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="flex items-center gap-2 w-full px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 bg-transparent border-none cursor-pointer text-left">
                                    <i class="fas fa-trash w-4 text-center"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Title --}}
                <h4 class="text-base font-bold text-gray-800 dark:text-gray-200 mb-2 mt-0">
                    {{ $announcement->title }}
                </h4>

                {{-- Body --}}
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4 break-words">
                    {!! nl2br(e($announcement->body)) !!}
                </p>

                {{-- Footer --}}
                <div class="flex items-center justify-between pt-3 border-t border-gray-200 dark:border-gray-700 flex-wrap gap-2">

                    {{-- Reactions --}}
                    <div class="flex items-center gap-1.5 flex-wrap">
                        @php
                            $emojis = ['👍', '❤️', '🎉', '👏', '💡'];
                            $reactionCounts = $announcement->reactions->groupBy('emoji')->map->count();
                            $userReactions = $announcement->reactions->where('user_id', auth()->id())->pluck('emoji')->toArray();
                        @endphp
                        @foreach($emojis as $emoji)
                        <button type="button"
                                onclick="toggleReaction({{ $announcement->announcement_id }}, '{{ $emoji }}', this)"
                                data-active="{{ in_array($emoji, $userReactions) ? '1' : '0' }}"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs border cursor-pointer transition-all bg-transparent
                                    {{ in_array($emoji, $userReactions)
                                        ? 'border-[#028a0f] dark:border-[#02b815] bg-green-50 dark:bg-green-900/20 font-bold text-[#028a0f] dark:text-[#02b815]'
                                        : 'border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                            <span>{{ $emoji }}</span>
                            <span class="reaction-count font-medium">{{ $reactionCounts[$emoji] ?? 0 }}</span>
                        </button>
                        @endforeach
                    </div>

                    {{-- Read status --}}
                    <div class="flex items-center gap-3">
                        @if($announcement->isReadBy(auth()->user()))
                        <span class="text-xs text-green-600 dark:text-green-400 font-medium">
                            <i class="fas fa-check-double mr-0.5"></i> Read
                        </span>
                        @else
                        <button type="button"
                                id="read-btn-{{ $announcement->announcement_id }}"
                                onclick="markAsRead({{ $announcement->announcement_id }})"
                                class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 bg-transparent border-none cursor-pointer font-medium transition-colors">
                            <i class="fas fa-check mr-0.5"></i> Mark as read
                        </button>
                        @endif
                        <span class="text-xs text-gray-400 dark:text-gray-500">
                            <i class="fas fa-eye mr-0.5"></i> {{ $announcement->reads->count() }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        @empty
        <div class="empty-state">
            <div class="empty-state-icon"><i class="fas fa-bullhorn"></i></div>
            <div class="empty-state-text">No announcements yet.</div>
            @if(auth()->user()->isDean() || auth()->user()->isProgramCoordinator())
            <a href="{{ route('announcements.create') }}" class="btn btn-primary mt-4">Post the first announcement</a>
            @endif
        </div>
        @endforelse

        @if($announcements->hasPages())
        <div class="mt-4">{{ $announcements->links() }}</div>
        @endif
    </div>

    <script>
        // Mark as read
        function markAsRead(id) {
            fetch(`/announcements/${id}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            }).then(r => r.json()).then(data => {
                if (!data.success) return;
                const btn = document.getElementById('read-btn-' + id);
                if (btn) btn.outerHTML = '<span class="text-xs text-green-600 dark:text-green-400 font-medium"><i class="fas fa-check-double mr-0.5"></i> Read</span>';
                const card = document.getElementById('announcement-' + id);
                if (card) {
                    card.dataset.unread = '0';
                    card.style.boxShadow = '';
                    card.classList.remove('shadow-[0_0_0_2px_rgba(59,130,246,0.3)]');
                    // remove blue dot
                    const dot = card.querySelector('.bg-blue-500');
                    if (dot) dot.remove();
                }
            });
        }

        // Toggle reaction
        function toggleReaction(id, emoji, btn) {
            fetch(`/announcements/${id}/react`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ emoji })
            }).then(r => r.json()).then(data => {
                if (!data.success) return;
                const isActive = data.action === 'added';
                btn.dataset.active = isActive ? '1' : '0';

                // Active styles
                btn.classList.toggle('border-[#028a0f]', isActive);
                btn.classList.toggle('dark:border-[#02b815]', isActive);
                btn.classList.toggle('bg-green-50', isActive);
                btn.classList.toggle('dark:bg-green-900/20', isActive);
                btn.classList.toggle('font-bold', isActive);
                btn.classList.toggle('text-[#028a0f]', isActive);
                btn.classList.toggle('dark:text-[#02b815]', isActive);
                // Inactive styles
                btn.classList.toggle('border-gray-200', !isActive);
                btn.classList.toggle('dark:border-gray-700', !isActive);
                btn.classList.toggle('text-gray-500', !isActive);
                btn.classList.toggle('dark:text-gray-400', !isActive);

                const reaction = data.reactions.find(r => r.emoji === emoji);
                const countEl = btn.querySelector('.reaction-count');
                if (countEl) countEl.textContent = reaction ? reaction.count : 0;
            });
        }

        // Action dropdown toggle
        function toggleMenu(id) {
            const menu = document.getElementById('menu-' + id);
            document.querySelectorAll('[id^="menu-"]').forEach(m => {
                if (m !== menu && /^menu-\d+$/.test(m.id)) m.classList.add('hidden');
            });
            menu.classList.toggle('hidden');
        }
        document.addEventListener('click', e => {
            if (!e.target.closest('[id^="menu-wrapper-"]')) {
                document.querySelectorAll('[id^="menu-"]').forEach(m => {
                    if (/^menu-\d+$/.test(m.id)) m.classList.add('hidden');
                });
            }
        });

        // Auto-mark as read after 1.5 s of being 60% visible
        const readObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                const card = entry.target;
                if (card.dataset.unread !== '1') return;
                const id = card.dataset.id;
                setTimeout(() => {
                    if (card.dataset.unread === '1') markAsRead(id);
                }, 1500);
            });
        }, { threshold: 0.6 });

        document.querySelectorAll('[data-unread="1"]').forEach(c => readObserver.observe(c));
    </script>
@endsection
