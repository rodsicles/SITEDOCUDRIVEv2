@extends('layouts.dashboard')

@section('title', 'Announcements')

@section('page-title', 'Announcements')
@section('page-subtitle', 'Stay updated with the latest news and updates')

@section('sidebar')
    @if(auth()->user()->isFaculty())
        @include('partials.faculty-sidebar')
    @elseif(auth()->user()->isProgramCoordinator())
        @include('partials.coordinator-sidebar')
    @else
        @include('partials.dean-sidebar')
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
             class="mb-4 border
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
                        <div class="w-10 h-10 flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                             style="background:linear-gradient(135deg,#028a0f,#026a0c);">
                            {{ strtoupper(substr($announcement->author->username ?? 'A', 0, 2)) }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                                    {{ $announcement->author->employee->full_name ?? $announcement->author->username }}
                                </span>
                                <span class="px-2 py-0.5 text-[0.65rem] font-semibold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                    {{ $announcement->author->role->role_name }}
                                </span>
                                @if(!$announcement->isReadBy(auth()->user()))
                                <span class="w-2 h-2 bg-blue-500 flex-shrink-0"></span>
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
                                <span class="px-2 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 text-[0.65rem] font-semibold">
                                    <i class="fas fa-building mr-0.5"></i>{{ $announcement->department }}
                                </span>
                                @endif
                                @if($announcement->expires_at)
                                <span class="px-2 py-0.5 bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 text-[0.65rem] font-semibold"
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
                                class="w-8 h-8 flex items-center justify-center text-gray-400 dark:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 bg-transparent border-none cursor-pointer">
                            <i class="fas fa-ellipsis-v text-sm"></i>
                        </button>
                        <div id="menu-{{ $announcement->announcement_id }}"
                             class="hidden absolute right-0 top-full mt-1 w-32 bg-white dark:bg-[#2a2a2a] border border-gray-200 dark:border-gray-700 z-50 overflow-hidden">
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
                <div class="flex items-center justify-end pt-3 border-t border-gray-200 dark:border-gray-700 flex-wrap gap-2">

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
                                class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 bg-transparent border-none cursor-pointer font-medium">
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
