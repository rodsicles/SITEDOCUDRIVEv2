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

        @forelse ($announcements as $announcement)
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
                <div class="flex items-center justify-between pt-3 border-t border-gray-200 dark:border-gray-700 flex-wrap gap-2">

                    {{-- Reaction bar --}}
                    @php
                        $allowedReactions = \App\Http\Controllers\AnnouncementController::ALLOWED_REACTIONS;
                        $reactionGroups   = $announcement->reactions->groupBy('emoji');
                        $myReactions      = $announcement->reactions
                            ->where('user_id', auth()->id())
                            ->pluck('emoji')
                            ->all();
                    @endphp
                    @php
                        $likeEmoji = '👍';
                        $likeCount = $reactionGroups->get($likeEmoji)?->count() ?? 0;
                        $iLiked    = in_array($likeEmoji, $myReactions, true);
                    @endphp
                    <div class="flex items-center gap-1"
                         data-reaction-bar="{{ $announcement->announcement_id }}">
                        <button type="button"
                                data-reaction-btn="{{ $likeEmoji }}"
                                data-announcement="{{ $announcement->announcement_id }}"
                                aria-pressed="{{ $iLiked ? 'true' : 'false' }}"
                                class="reaction-btn inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold border rounded-full transition-colors cursor-pointer
                                    {{ $iLiked
                                        ? 'border-[#028a0f] bg-[#028a0f] text-white dark:bg-[#026a0c] dark:border-[#026a0c]'
                                        : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-[#2a2a2a] text-gray-600 dark:text-gray-300 hover:border-[#028a0f] hover:text-[#028a0f] dark:hover:border-[#02b815] dark:hover:text-[#02b815]' }}">
                            <span class="text-sm leading-none">{{ $likeEmoji }}</span>
                            <span>Like</span>
                            <span data-reaction-count="{{ $likeEmoji }}"
                                  class="font-bold {{ $likeCount === 0 ? 'hidden' : '' }}">{{ $likeCount }}</span>
                        </button>
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
                                class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 bg-transparent border-none cursor-pointer font-medium">
                            <i class="fas fa-check mr-0.5"></i> Mark as read
                        </button>
                        @endif
                        <span class="text-xs text-gray-400 dark:text-gray-500">
                            <i class="fas fa-eye mr-0.5"></i> {{ $announcement->reads->count() }}
                        </span>
                        @if(auth()->id() === $announcement->author_id || auth()->user()->isDean())
                            <button type="button"
                                    onclick="openReceipts({{ $announcement->announcement_id }})"
                                    class="text-xs text-[#028a0f] dark:text-[#02b815] hover:underline bg-transparent border-none cursor-pointer font-medium">
                                <i class="fas fa-clipboard-check mr-0.5"></i> Receipts
                            </button>
                        @endif
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

        <div class="mt-4 px-2 pb-2">
            {{ $announcements->links('partials.pagination') }}
        </div>
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

        // Toggle reaction
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-reaction-btn]');
            if (!btn) return;
            e.preventDefault();

            if (btn.dataset.busy === '1') return;
            btn.dataset.busy = '1';

            const id    = btn.dataset.announcement;
            const emoji = btn.dataset.reactionBtn;

            fetch(`/announcements/${id}/react`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ emoji }),
            })
            .then(r => r.ok ? r.json() : Promise.reject(r))
            .then(data => {
                if (!data.success) return;
                const bar = document.querySelector(`[data-reaction-bar="${id}"]`);
                if (!bar) return;

                const userSet = new Set(data.user_reactions || []);

                bar.querySelectorAll('[data-reaction-btn]').forEach(b => {
                    const e2      = b.dataset.reactionBtn;
                    const count   = (data.counts && data.counts[e2]) || 0;
                    const mine    = userSet.has(e2);
                    const countEl = b.querySelector(`[data-reaction-count]`);

                    if (countEl) {
                        countEl.textContent = count;
                        countEl.classList.toggle('hidden', count === 0);
                    }

                    b.setAttribute('aria-pressed', mine ? 'true' : 'false');

                    const activeClasses   = ['border-[#028a0f]','bg-[#028a0f]','text-white','dark:bg-[#026a0c]','dark:border-[#026a0c]'];
                    const inactiveClasses = ['border-gray-300','dark:border-gray-600','bg-white','dark:bg-[#2a2a2a]','text-gray-600','dark:text-gray-300','hover:border-[#028a0f]','hover:text-[#028a0f]','dark:hover:border-[#02b815]','dark:hover:text-[#02b815]'];

                    if (mine) {
                        b.classList.remove(...inactiveClasses);
                        b.classList.add(...activeClasses);
                    } else {
                        b.classList.remove(...activeClasses);
                        b.classList.add(...inactiveClasses);
                    }
                });
            })
            .catch(() => { /* silently ignore; user can retry */ })
            .finally(() => { delete btn.dataset.busy; });
        });

        // ---------- Read Receipts modal ----------
        function openReceipts(id) {
            const modal = document.getElementById('receiptsModal');
            const titleEl = document.getElementById('receiptsTitle');
            const summary = document.getElementById('receiptsSummary');
            const readList = document.getElementById('receiptsReadList');
            const unreadList = document.getElementById('receiptsUnreadList');
            const tabRead = document.getElementById('tabRead');
            const tabUnread = document.getElementById('tabUnread');

            titleEl.textContent = 'Loading...';
            summary.textContent = '';
            readList.innerHTML = '<div class="p-3 text-xs text-gray-500">Loading...</div>';
            unreadList.innerHTML = '<div class="p-3 text-xs text-gray-500">Loading...</div>';
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            fetch(`/announcements/${id}/receipts`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.ok ? r.json() : Promise.reject(r))
            .then(data => {
                titleEl.textContent = data.title;
                summary.textContent = `${data.total_read} of ${data.total_audience} have read`;
                tabRead.textContent = `Read (${data.total_read})`;
                tabUnread.textContent = `Not yet read (${data.total_unread})`;

                const renderRow = (row, includeWhen) => {
                    const when = includeWhen && row.read_at
                        ? `<span class="text-[11px] text-gray-400 dark:text-gray-500">${new Date(row.read_at).toLocaleString()}</span>`
                        : '';
                    const meta = [row.role, row.department].filter(Boolean).join(' &middot; ');
                    return `<div class="flex items-center justify-between gap-2 px-3 py-2 border-b border-gray-100 dark:border-gray-700">
                        <div class="min-w-0">
                            <div class="text-xs font-semibold text-gray-800 dark:text-gray-100 truncate">${row.name}</div>
                            <div class="text-[11px] text-gray-500 dark:text-gray-400 truncate">${meta}</div>
                        </div>
                        ${when}
                    </div>`;
                };

                readList.innerHTML = data.read.length
                    ? data.read.map(r => renderRow(r, true)).join('')
                    : '<div class="p-3 text-xs text-gray-500">No one has read this yet.</div>';
                unreadList.innerHTML = data.unread.length
                    ? data.unread.map(r => renderRow(r, false)).join('')
                    : '<div class="p-3 text-xs text-gray-500">Everyone has read this announcement.</div>';
            })
            .catch(() => {
                titleEl.textContent = 'Error';
                summary.textContent = 'Could not load receipts.';
                readList.innerHTML = '';
                unreadList.innerHTML = '';
            });
        }
        function closeReceipts() {
            const modal = document.getElementById('receiptsModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        function showReceiptsTab(which) {
            const readPane = document.getElementById('receiptsReadList');
            const unreadPane = document.getElementById('receiptsUnreadList');
            const tabRead = document.getElementById('tabRead');
            const tabUnread = document.getElementById('tabUnread');
            const active = ['border-[#028a0f]','text-[#028a0f]','dark:text-[#02b815]','dark:border-[#02b815]'];
            const inactive = ['border-transparent','text-gray-500','dark:text-gray-400'];
            if (which === 'read') {
                readPane.classList.remove('hidden');
                unreadPane.classList.add('hidden');
                tabRead.classList.remove(...inactive); tabRead.classList.add(...active);
                tabUnread.classList.remove(...active); tabUnread.classList.add(...inactive);
            } else {
                readPane.classList.add('hidden');
                unreadPane.classList.remove('hidden');
                tabUnread.classList.remove(...inactive); tabUnread.classList.add(...active);
                tabRead.classList.remove(...active); tabRead.classList.add(...inactive);
            }
        }
    </script>

    {{-- Receipts modal --}}
    <div id="receiptsModal" class="hidden fixed inset-0 z-[9999] bg-black/50 items-center justify-center p-4" onclick="if(event.target===this)closeReceipts()">
        <div class="bg-white dark:bg-[#1f1f1f] w-full max-w-lg rounded-md shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="flex items-start justify-between gap-3 px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                <div class="min-w-0">
                    <h3 id="receiptsTitle" class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">Read Receipts</h3>
                    <div id="receiptsSummary" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"></div>
                </div>
                <button type="button" onclick="closeReceipts()" class="text-gray-500 hover:text-red-600 text-sm bg-transparent border-none cursor-pointer">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="flex border-b border-gray-100 dark:border-gray-700">
                <button id="tabRead" type="button" onclick="showReceiptsTab('read')"
                        class="flex-1 px-3 py-2 text-xs font-medium border-b-2 border-[#028a0f] text-[#028a0f] dark:border-[#02b815] dark:text-[#02b815] bg-transparent cursor-pointer">
                    Read
                </button>
                <button id="tabUnread" type="button" onclick="showReceiptsTab('unread')"
                        class="flex-1 px-3 py-2 text-xs font-medium border-b-2 border-transparent text-gray-500 dark:text-gray-400 bg-transparent cursor-pointer">
                    Not yet read
                </button>
            </div>
            <div id="receiptsReadList" class="max-h-80 overflow-y-auto"></div>
            <div id="receiptsUnreadList" class="max-h-80 overflow-y-auto hidden"></div>
        </div>
    </div>
@endsection
