<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="request-guard-cooldown-ms" content="{{ config('rate_limits.request_guard_cooldown_ms', 2500) }}">
    <title>@yield('title', 'Employee Dashboard with Data Analytics - SITE')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/SPUP-final-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="site-app-body authenticated-ui route-{{ str_replace('.', '-', request()->route()?->getName() ?? 'page') }} overflow-x-hidden text-gray-800 dark:text-gray-200"
      data-font-size="medium"
      data-user-role="{{ auth()->user()->role->role_name ?? 'user' }}">


    <div class="flex min-h-screen">
        <!-- Sidebar Overlay (mobile) -->
        <div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-[999] hidden md:hidden"></div>

        <!-- Sidebar -->
        <aside id="appSidebar" class="w-64 fixed h-screen overflow-hidden z-[1000] sidebar" aria-label="Primary navigation">
            <div class="sidebar-brand">
                <div class="sidebar-brand-heading">
                    <img src="{{ asset('images/site-logo.png') }}" alt="SITE Logo" class="sidebar-brand-logo">
                    <h2 class="sidebar-brand-title">SITE DocuDrive</h2>
                </div>
                <p class="sidebar-brand-welcome">Welcome, {{ auth()->user()->role->role_name }}</p>
                <a href="{{ route('user-guide') }}" class="sidebar-guide-btn {{ request()->routeIs('user-guide') ? 'active' : '' }}">
                    <i class="fas fa-book-open"></i> User Guide
                </a>
            </div>
            <nav class="p-2">
                @yield('sidebar')
            </nav>
            @include('partials.sidebar-account')
        </aside>

        <!-- Main Content -->
        <main class="ml-64 flex-1 p-8 w-[calc(100%-16rem)] main-content" id="main-content">
            <!-- Top Bar -->
            <div class="p-4 px-6 mb-4 flex justify-between items-center gap-4 top-bar sticky top-0 z-[300]">
                <div class="flex items-center gap-3 min-w-0 flex-shrink-0">
                    <!-- Mobile Hamburger Menu -->
                    <button id="mobileMenuToggle" class="hidden max-md:block top-control text-xl text-gray-800 dark:text-gray-200 cursor-pointer flex-shrink-0" type="button" aria-label="Open navigation menu" aria-controls="appSidebar" aria-expanded="false">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="min-w-0">
                        <h1 class="text-2xl max-md:text-lg text-gray-800 dark:text-gray-200 mb-0 font-semibold truncate">@yield('page-title', 'Dashboard')</h1>
                        <p class="text-gray-600 dark:text-gray-400 text-xs max-md:text-xs truncate">@yield('page-subtitle', 'Welcome back!')</p>
                    </div>
                </div>
                @hasSection('page-header-extra')
                    <div class="page-header-extra min-w-0 flex-1 max-md:hidden">
                        @yield('page-header-extra')
                    </div>
                @endif
                <div class="flex items-center gap-3 max-md:gap-2 flex-shrink-0">
                    @php
                        $notificationsPageUrl = match (true) {
                            auth()->user()->isFaculty() => route('faculty.notifications'),
                            auth()->user()->isProgramCoordinator() => route('coordinator.notifications'),
                            auth()->user()->isDeanOrSecretary() => route('dean.notifications'),
                            default => null,
                        };
                    @endphp
                    @if($notificationsPageUrl)
                    <div class="relative" id="notification-dropdown-wrap">
                        <button type="button" class="relative top-control text-gray-600 dark:text-gray-400 cursor-pointer flex flex-col items-center leading-none" id="notification-bell-btn" aria-label="Notifications" aria-expanded="false" aria-haspopup="true">
                            <span class="relative text-lg max-md:text-base">
                                <i class="fas fa-bell"></i>
                                <span id="notification-badge" class="absolute -top-2 -right-2 bg-[#0d5c3b] text-white w-4 h-4 text-xs flex items-center justify-center font-bold {{ (isset($unreadNotifications) && $unreadNotifications > 0) ? '' : 'hidden' }}">{{ $unreadNotifications ?? 0 }}</span>
                            </span>
                            <i class="fas fa-caret-down text-[9px] mt-0.5 opacity-70"></i>
                        </button>
                        <div id="notification-dropdown" class="hidden notification-dropdown absolute top-full right-0 mt-2 w-[min(22rem,calc(100vw-2rem))] bg-white dark:bg-[#2a2a2a] border border-gray-200 dark:border-gray-700 shadow-lg z-[1000]">
                            <div class="notification-dropdown-header">
                                <span class="font-semibold text-sm text-gray-800 dark:text-gray-200">Notifications</span>
                                <button type="button" id="notification-mark-all-btn" class="text-xs text-[#0d5c3b] dark:text-[#56c596] bg-transparent border-none cursor-pointer font-semibold hidden">Mark all read</button>
                            </div>
                            <div id="notification-dropdown-list" class="notification-dropdown-list">
                                <p class="notification-dropdown-empty">Loading...</p>
                            </div>
                            <div class="notification-dropdown-footer">
                                <a href="{{ $notificationsPageUrl }}" class="text-xs font-semibold text-[#0d5c3b] dark:text-[#56c596] no-underline">See all notifications</a>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Theme & Settings Controls -->
                    <div class="flex gap-2 max-md:gap-1 items-center">
                        <!-- Font Size (hidden on mobile) -->
                        <div class="relative max-md:hidden">
                            <button id="fontSizeBtn" class="top-control text-gray-600 dark:text-gray-400 text-lg cursor-pointer" type="button" title="Font size" aria-label="Font size" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-text-height"></i>
                            </button>
                            <div id="fontSizeMenu" class="hidden absolute top-full right-0 bg-white dark:bg-[#2a2a2a] border border-gray-200 dark:border-gray-700 p-2 min-w-[100px] z-[1000] mt-1">
                                <button onclick="changeFontSize('small')" class="block w-full px-2 py-1 bg-transparent border-none text-left cursor-pointer text-gray-800 dark:text-gray-200">Small</button>
                                <button onclick="changeFontSize('medium')" class="block w-full px-2 py-1 bg-transparent border-none text-left cursor-pointer text-gray-800 dark:text-gray-200">Medium</button>
                                <button onclick="changeFontSize('large')" class="block w-full px-2 py-1 bg-transparent border-none text-left cursor-pointer text-gray-800 dark:text-gray-200">Large</button>
                            </div>
                        </div>

                        <!-- Dark Mode Toggle -->
                        <button id="darkModeToggle" class="top-control text-gray-600 dark:text-gray-400 text-lg max-md:text-base cursor-pointer" type="button" title="Toggle color theme" aria-label="Toggle color theme">
                            <i class="fas fa-moon"></i>
                        </button>

                        <!-- Global Search -->
                        <button id="globalSearchBtn" class="top-control text-gray-600 dark:text-gray-400 text-lg max-md:text-base cursor-pointer" type="button" title="Search" aria-label="Open global search">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>

                </div>
            </div>

            <!-- Alerts removed - using toast only -->

            <!-- Page Content -->
            <div class="app-page">
            @yield('content')
            </div>
        </main>
    </div>

    <!-- Global Search Modal -->
    <div id="searchModal" class="hidden fixed inset-0 bg-black/60 z-[9999] items-start justify-center pt-14" role="dialog" aria-modal="true" aria-label="Search">
        <div class="search-palette">
            <input type="text" id="globalSearchInput" placeholder="Search files, announcements, and people" autocomplete="off" maxlength="80" class="search-palette__input">
            <div id="searchResults" class="search-palette__results" role="listbox">
                <p class="search-palette__hint">Type at least 3 characters.</p>
            </div>
        </div>
    </div>



    <!-- Toast Container -->
    <div id="toastContainer" class="ui-toast-stack" aria-live="polite" aria-atomic="true"></div>

    <script>
        // Dark Mode Toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        const html = document.documentElement;
        
        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        html.setAttribute('data-theme', savedTheme);
        if (savedTheme === 'dark') {
            document.body.classList.add('dark');
        }
        updateDarkModeIcon(savedTheme);
        
        darkModeToggle.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            document.body.classList.toggle('dark');
            updateDarkModeIcon(newTheme);
        });
        
        function updateDarkModeIcon(theme) {
            const icon = darkModeToggle.querySelector('i');
            icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
        
        // Font Size Toggle
        const fontSizeBtn = document.getElementById('fontSizeBtn');
        const fontSizeMenu = document.getElementById('fontSizeMenu');
        
        fontSizeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            fontSizeMenu.classList.toggle('hidden');
            document.getElementById('userMenu').classList.add('hidden');
        });
        
        document.addEventListener('click', () => {
            fontSizeMenu.classList.add('hidden');
            document.getElementById('userMenu').classList.add('hidden');
            document.getElementById('userMenuBtn')?.setAttribute('aria-expanded', 'false');
            const notifDropdown = document.getElementById('notification-dropdown');
            const notifBtn = document.getElementById('notification-bell-btn');
            if (notifDropdown) notifDropdown.classList.add('hidden');
            if (notifBtn) notifBtn.setAttribute('aria-expanded', 'false');
        });
        
        // User Menu Toggle
        const userMenuBtn = document.getElementById('userMenuBtn');
        const userMenu = document.getElementById('userMenu');
        
        userMenuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userMenu.classList.toggle('hidden');
            const isOpen = !userMenu.classList.contains('hidden');
            userMenuBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            fontSizeMenu.classList.add('hidden');
            const notifDropdown = document.getElementById('notification-dropdown');
            const notifBtn = document.getElementById('notification-bell-btn');
            if (notifDropdown) notifDropdown.classList.add('hidden');
            if (notifBtn) notifBtn.setAttribute('aria-expanded', 'false');
        });

        userMenu.addEventListener('click', (e) => e.stopPropagation());
        
        // Load saved font size
        const savedFontSize = localStorage.getItem('fontSize') || 'medium';
        html.setAttribute('data-font-size', savedFontSize);
        const fontSizes = { small: '13px', medium: '15px', large: '17px' };
        document.body.style.fontSize = fontSizes[savedFontSize];
        
        function changeFontSize(size) {
            html.setAttribute('data-font-size', size);
            localStorage.setItem('fontSize', size);
            document.body.style.fontSize = fontSizes[size];
            fontSizeMenu.classList.add('hidden');
        }
        
        // Global Search
        const searchModal = document.getElementById('searchModal');
        const globalSearchBtn = document.getElementById('globalSearchBtn');
        const searchInput = document.getElementById('globalSearchInput');
        const searchResults = document.getElementById('searchResults');
        let searchActiveIndex = -1;
        const searchHint = '<p class="search-palette__hint">Type at least 3 characters.</p>';

        function openSearch() {
            searchModal.classList.remove('hidden');
            searchModal.classList.add('flex');
            searchInput.focus();
        }

        function closeSearch() {
            searchModal.classList.add('hidden');
            searchModal.classList.remove('flex');
            searchInput.value = '';
            searchResults.innerHTML = searchHint;
            searchActiveIndex = -1;
        }

        globalSearchBtn.addEventListener('click', openSearch);

        searchModal.addEventListener('click', (e) => {
            if (e.target === searchModal) closeSearch();
        });
        
        // Search functionality
        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();
            
            if (query.length < 3) {
                searchResults.innerHTML = searchHint;
                searchActiveIndex = -1;
                return;
            }

            searchResults.innerHTML = '<p class="search-palette__hint">Searching…</p>';
            
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 500);
        });
        
        function performSearch(query) {
            fetch(`/search?q=${encodeURIComponent(query)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                displaySearchResults(data);
            })
            .catch(() => {
                searchResults.innerHTML = '<p class="search-palette__empty">No results found</p>';
            });
        }
        
        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function dedupeSearchResults(results) {
            const seenPeople = new Set();
            const seenOther = new Set();
            const out = [];
            (Array.isArray(results) ? results : []).forEach((result) => {
                const type = result.type || '';
                const title = String(result.title || '').toLowerCase().trim();
                const url = result.url || '';
                const isPerson = type === 'User' || type === 'Employee';
                if (isPerson) {
                    const key = url || ('name:' + title);
                    if (seenPeople.has(key)) return;
                    seenPeople.add(key);
                    out.push(result);
                    return;
                }
                const key = type + '|' + url + '|' + title;
                if (seenOther.has(key)) return;
                seenOther.add(key);
                out.push(result);
            });
            return out;
        }

        function highlightSearchResult() {
            const items = searchResults.querySelectorAll('.search-result');
            items.forEach((item, index) => {
                item.classList.toggle('is-active', index === searchActiveIndex);
            });
            if (searchActiveIndex >= 0 && items[searchActiveIndex]) {
                items[searchActiveIndex].scrollIntoView({ block: 'nearest' });
            }
        }

        function displaySearchResults(results) {
            const unique = dedupeSearchResults(results);
            if (unique.length === 0) {
                searchResults.innerHTML = '<p class="search-palette__empty">No results found</p>';
                return;
            }

            searchResults.innerHTML = '';
            unique.forEach((result) => {
                const item = document.createElement('div');
                const isPerson = result.type === 'User' || result.type === 'Employee';
                item.className = 'search-result';
                item.setAttribute('role', 'option');
                item.addEventListener('click', () => { window.location.href = result.url; });
                item.addEventListener('mouseenter', () => {
                    const items = [...searchResults.querySelectorAll('.search-result')];
                    searchActiveIndex = items.indexOf(item);
                    highlightSearchResult();
                });

                const title = document.createElement('div');
                title.className = 'search-result__title';
                title.textContent = result.title;

                const meta = document.createElement('div');
                meta.className = 'search-result__meta';
                meta.textContent = isPerson
                    ? (result.subtitle || 'Person')
                    : [result.type || 'Result', result.subtitle].filter(Boolean).join(' · ');

                item.appendChild(title);
                item.appendChild(meta);
                searchResults.appendChild(item);
            });
            searchActiveIndex = 0;
            highlightSearchResult();
        }

        document.addEventListener('keydown', (e) => {
            const open = !searchModal.classList.contains('hidden');
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                openSearch();
                return;
            }
            if (!open) return;
            if (e.key === 'Escape') {
                closeSearch();
                return;
            }
            const items = searchResults.querySelectorAll('.search-result');
            if (!items.length) return;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                searchActiveIndex = Math.min(searchActiveIndex + 1, items.length - 1);
                highlightSearchResult();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                searchActiveIndex = Math.max(searchActiveIndex - 1, 0);
                highlightSearchResult();
            } else if (e.key === 'Enter' && searchActiveIndex >= 0 && items[searchActiveIndex]) {
                e.preventDefault();
                items[searchActiveIndex].click();
            }
        });

        // Toast Notification System — instant show/hide (no slide animation)
        function showToast(message, type = 'success') {
            if (type === 'error' && typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Upload notice',
                    text: message,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#0d5c3b',
                    customClass: { popup: 'swal-flat' },
                    showClass: { popup: '' },
                    hideClass: { popup: '' },
                });
                return;
            }

            const container = document.getElementById('toastContainer');
            if (!container) return;

            container.querySelectorAll('.ui-toast').forEach((el) => el.remove());

            const toast = document.createElement('div');
            toast.className = 'ui-toast ui-toast--' + (type === 'error' ? 'error' : (type === 'info' ? 'info' : 'success'));
            toast.setAttribute('role', 'status');

            const text = document.createElement('span');
            text.className = 'ui-toast__text';
            text.textContent = String(message || '').replace(/\s+successfully!?$/i, '').trim();

            const close = document.createElement('button');
            close.type = 'button';
            close.className = 'ui-toast__close';
            close.setAttribute('aria-label', 'Dismiss');
            close.innerHTML = '&times;';
            close.addEventListener('click', () => toast.remove());

            toast.appendChild(text);
            toast.appendChild(close);
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 2800);
        }

        (function () {
            if (window.__sessionFlashShown) {
                return;
            }
            window.__sessionFlashShown = true;

            @if(session('success'))
                showToast(@json(session('success')), 'success');
            @endif

            @if(session('error'))
                showToast(@json(session('error')), 'error');
            @endif

            @unless(session()->has('success') || session()->has('error'))
                @if($errors->any())
                    showToast(@json($errors->first()), 'error');
                @endif
            @endunless
        })();



        // ---------- Drag-and-Drop file input enhancement ----------
        // Any <input type="file" data-dropzone="1"> gets wrapped with a drop target
        // so users can drag files from the desktop straight onto the field.
        // The browser still sends the same multipart/form-data POST, so backend
        // validation (mimes:, mimetypes:, max:, quota) is unchanged.
        (function () {
            const enhance = (input) => {
                if (input.dataset.dropEnhanced === '1') return;
                input.dataset.dropEnhanced = '1';

                const wrapper = document.createElement('div');
                wrapper.className = 'drop-zone';
                wrapper.style.cssText = 'position:relative;border:1px dashed #9fb8aa;padding:32px 16px;background:#f7faf8;color:#68766f;text-align:center;font-size:12px;cursor:pointer;';

                const label = document.createElement('div');
                label.innerHTML = '<i class="fas fa-cloud-upload-alt" style="font-size:24px;color:#0d5c3b;display:block;margin-bottom:8px;"></i>'
                    + '<strong style="color:#0b4931;">Drag &amp; drop files here</strong>'
                    + '<span style="opacity:.75;"> or choose files from your device</span>'
                    + '<div data-drop-filename style="margin-top:8px;font-size:11px;color:#0d5c3b;font-weight:600;"></div>';

                input.parentNode.insertBefore(wrapper, input);
                wrapper.appendChild(label);
                wrapper.appendChild(input);
                input.style.position = 'absolute';
                input.style.opacity = '0';
                input.style.inset = '0';
                input.style.width = '100%';
                input.style.height = '100%';
                input.style.cursor = 'pointer';

                const fileNameEl = wrapper.querySelector('[data-drop-filename]');
                const setFileLabel = () => {
                    if (input.files && input.files.length) {
                        const names = Array.from(input.files).map(f => f.name).join(', ');
                        fileNameEl.textContent = '\u2713 ' + names;
                    } else {
                        fileNameEl.textContent = '';
                    }
                };
                input.addEventListener('change', setFileLabel);

                ['dragenter', 'dragover'].forEach(evt =>
                    wrapper.addEventListener(evt, e => {
                        e.preventDefault();
                        wrapper.classList.add('is-dragging');
                        wrapper.style.borderColor = '#0d5c3b';
                        wrapper.style.background = '#edf7f1';
                        wrapper.style.color = '#0d5c3b';
                    })
                );
                ['dragleave', 'drop'].forEach(evt =>
                    wrapper.addEventListener(evt, e => {
                        e.preventDefault();
                        wrapper.classList.remove('is-dragging');
                        wrapper.style.borderColor = '#9fb8aa';
                        wrapper.style.background = '#f7faf8';
                        wrapper.style.color = '#68766f';
                    })
                );
                const fileMatchesAccept = (file, acceptAttr) => {
                    if (!acceptAttr) return true;
                    const ext = (file.name || '').toLowerCase().split('.').pop();
                    const tokens = acceptAttr.split(',').map(t => t.trim().toLowerCase()).filter(Boolean);
                    return tokens.some(token => {
                        if (token.startsWith('.')) return ext === token.slice(1);
                        if (token.includes('/')) {
                            if (token.endsWith('/*')) {
                                const prefix = token.slice(0, token.indexOf('/'));
                                return (file.type || '').toLowerCase().startsWith(prefix + '/');
                            }
                            return (file.type || '').toLowerCase() === token;
                        }
                        return false;
                    });
                };

                wrapper.addEventListener('drop', e => {
                    if (!e.dataTransfer || !e.dataTransfer.files || !e.dataTransfer.files.length) return;
                    try {
                        const dt = new DataTransfer();
                        const acceptMultiple = input.multiple;
                        const acceptAttr = input.getAttribute('accept') || '';
                        let dropped = acceptMultiple ? Array.from(e.dataTransfer.files) : [e.dataTransfer.files[0]];
                        const rejected = [];
                        dropped = dropped.filter(f => {
                            if (fileMatchesAccept(f, acceptAttr)) return true;
                            rejected.push(f.name);
                            return false;
                        });
                        if (rejected.length && typeof showToast === 'function') {
                            const hint = acceptAttr.includes('pdf') ? 'PDF only' : (acceptAttr.includes('doc') ? 'Word only' : 'this type');
                            showToast(rejected.join(', ') + ' cannot be added. ' + hint + '.', 'error');
                        }
                        if (!dropped.length) return;
                        dropped.forEach(f => dt.items.add(f));
                        input.files = dt.files;
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    } catch (err) {
                        console.warn('Drop assignment unsupported, fallback to click:', err);
                    }
                });

                if (input.files && input.files.length) setFileLabel();
            };

            const init = () => document.querySelectorAll('input[type="file"][data-dropzone="1"]').forEach(enhance);
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
            // Watch for late-rendered inputs (e.g. inside dynamically opened modals).
            const mo = new MutationObserver(() => init());
            mo.observe(document.body, { childList: true, subtree: true });
        })();



        // Mobile Sidebar Toggle
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const sidebar = document.querySelector('.sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        // Sidebar scrollbar: fade in/out when pointer enters/leaves panel
        if (sidebar) {
            sidebar.addEventListener('mouseenter', () => {
                sidebar.classList.add('sidebar-scrollbar-visible');
            });
            sidebar.addEventListener('mouseleave', () => {
                sidebar.classList.remove('sidebar-scrollbar-visible');
            });
        }

        // Persist sidebar nav scroll across full page navigations (sessionStorage only; not auth)
        (function () {
            const sidebarNav = document.querySelector('.sidebar nav');
            if (!sidebarNav) return;

            const scrollStorageKey = @json('emp-dashboard-sidebar-scroll:' . \Illuminate\Support\Str::slug(auth()->user()->role->role_name ?? 'user'));

            function saveSidebarScroll() {
                try {
                    sessionStorage.setItem(scrollStorageKey, String(sidebarNav.scrollTop));
                } catch (e) { /* quota / private mode */ }
            }

            function restoreSidebarScroll() {
                try {
                    const saved = sessionStorage.getItem(scrollStorageKey);
                    if (saved === null) return;
                    const top = parseInt(saved, 10);
                    if (!Number.isFinite(top) || top < 0) return;
                    sidebarNav.scrollTop = top;
                } catch (e) { /* ignore */ }
            }

            restoreSidebarScroll();
            requestAnimationFrame(() => {
                restoreSidebarScroll();
                requestAnimationFrame(restoreSidebarScroll);
            });

            let scrollSaveTimer;
            sidebarNav.addEventListener('scroll', () => {
                clearTimeout(scrollSaveTimer);
                scrollSaveTimer = setTimeout(saveSidebarScroll, 80);
            }, { passive: true });

            window.addEventListener('pagehide', saveSidebarScroll);

            sidebarNav.querySelectorAll('a[href]').forEach((link) => {
                link.addEventListener('click', saveSidebarScroll);
            });

            window.addEventListener('pageshow', (event) => {
                if (event.persisted) restoreSidebarScroll();
            });
        })();

        function openSidebar() {
            sidebar.classList.add('active');
            sidebarOverlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            if (mobileMenuToggle) mobileMenuToggle.setAttribute('aria-expanded', 'true');
        }

        function closeSidebar() {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.add('hidden');
            document.body.style.overflow = '';
            if (mobileMenuToggle) mobileMenuToggle.setAttribute('aria-expanded', 'false');
        }

        if (mobileMenuToggle) {
            mobileMenuToggle.addEventListener('click', () => {
                if (sidebar.classList.contains('active')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', closeSidebar);
        }

        // Close sidebar on resize to desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                closeSidebar();
            }
        });

        // Auto-wrap data tables for mobile horizontal scrolling
        document.querySelectorAll('.data-table').forEach(table => {
            if (!table.parentElement.classList.contains('overflow-x-auto')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'overflow-x-auto';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }
        });
    </script>

    @stack('scripts')

    @auth
        @if(auth()->user()->isFaculty() || auth()->user()->isProgramCoordinator() || auth()->user()->isDeanOrSecretary())
        <script>
            // Notification badge live polling + dropdown panel
            (function() {
                @php
                    $notificationsUnreadUrl = match (true) {
                        auth()->user()->isFaculty() => route('faculty.notifications.unread-count'),
                        auth()->user()->isProgramCoordinator() => route('coordinator.notifications.unread-count'),
                        auth()->user()->isDeanOrSecretary() => route('dean.notifications.unread-count'),
                        default => '',
                    };
                    $notificationsRecentUrlJs = match (true) {
                        auth()->user()->isFaculty() => route('faculty.notifications.recent'),
                        auth()->user()->isProgramCoordinator() => route('coordinator.notifications.recent'),
                        auth()->user()->isDeanOrSecretary() => route('dean.notifications.recent'),
                        default => '',
                    };
                    $notificationsReadJsonPrefixJs = match (true) {
                        auth()->user()->isFaculty() => 'faculty',
                        auth()->user()->isProgramCoordinator() => 'coordinator',
                        auth()->user()->isDeanOrSecretary() => 'dean',
                        default => '',
                    };
                    $notificationsMarkAllJsonUrl = match (true) {
                        auth()->user()->isFaculty() => route('faculty.notifications.mark-all-read-json'),
                        auth()->user()->isProgramCoordinator() => route('coordinator.notifications.mark-all-read-json'),
                        auth()->user()->isDeanOrSecretary() => route('dean.notifications.mark-all-read-json'),
                        default => '',
                    };
                    $notificationsReadJsonUrlTemplateJs = $notificationsReadJsonPrefixJs
                        ? route($notificationsReadJsonPrefixJs . '.notifications.read-json', ['id' => '__ID__'])
                        : '';
                @endphp
                const unreadUrl = @json($notificationsUnreadUrl);
                const recentUrl = @json($notificationsRecentUrlJs);
                const readJsonUrlTemplate = @json($notificationsReadJsonUrlTemplateJs);
                const markAllUrl = @json($notificationsMarkAllJsonUrl);
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const badge = document.getElementById('notification-badge');
                const bellBtn = document.getElementById('notification-bell-btn');
                const dropdown = document.getElementById('notification-dropdown');
                const listEl = document.getElementById('notification-dropdown-list');
                const markAllBtn = document.getElementById('notification-mark-all-btn');
                let dropdownLoaded = false;

                function applyCount(count) {
                    if (!badge) return;
                    if (count > 0) {
                        badge.textContent = count > 99 ? '99+' : count;
                        badge.classList.remove('hidden');
                        if (markAllBtn) markAllBtn.classList.remove('hidden');
                    } else {
                        badge.textContent = '0';
                        badge.classList.add('hidden');
                        if (markAllBtn) markAllBtn.classList.add('hidden');
                    }
                }

                function escapeHtml(str) {
                    const div = document.createElement('div');
                    div.textContent = str ?? '';
                    return div.innerHTML;
                }

                function renderNotifications(items) {
                    if (!listEl) return;
                    if (!items.length) {
                        listEl.innerHTML = '<p class="notification-dropdown-empty">No notifications yet.</p>';
                        return;
                    }
                    listEl.innerHTML = '';
                    items.forEach(item => {
                        const row = document.createElement('button');
                        row.type = 'button';
                        row.className = 'notification-dropdown-item' + (item.is_read ? '' : ' notification-dropdown-item--unread');
                        if (item.tone === 'danger') row.classList.add('notification-dropdown-item--danger');
                        if (item.tone === 'success') row.classList.add('notification-dropdown-item--success');
                        row.dataset.id = item.id;
                        if (item.action_url) row.dataset.actionUrl = item.action_url;
                        row.innerHTML = '<span class="notification-dropdown-item-msg">' + escapeHtml(item.message) + '</span>'
                            + '<span class="notification-dropdown-item-time">' + escapeHtml(item.time_ago) + '</span>';
                        listEl.appendChild(row);
                    });
                }

                async function loadDropdown(force) {
                    if (!recentUrl || !listEl) return;
                    if (dropdownLoaded && !force) return;
                    listEl.innerHTML = '<p class="notification-dropdown-empty"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';
                    try {
                        const r = await fetch(recentUrl, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        if (!r.ok) throw new Error('Failed');
                        const data = await r.json();
                        renderNotifications(data.notifications || []);
                        if (typeof data.unread_count !== 'undefined') applyCount(data.unread_count);
                        dropdownLoaded = true;
                    } catch (e) {
                        listEl.innerHTML = '<p class="notification-dropdown-empty">Could not load notifications.</p>';
                    }
                }

                async function markRead(id, rowEl) {
                    if (!readJsonUrlTemplate || !id) return;
                    try {
                        await fetch(readJsonUrlTemplate.replace('__ID__', encodeURIComponent(id)), {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                        });
                        if (rowEl) {
                            rowEl.classList.remove('notification-dropdown-item--unread');
                        }
                        await refresh();
                        dropdownLoaded = false;
                    } catch (e) {}
                }

                if (listEl) {
                    listEl.addEventListener('click', async (e) => {
                        const row = e.target.closest('.notification-dropdown-item');
                        if (!row) return;
                        const actionUrl = row.dataset.actionUrl || '';
                        const isUnread = row.classList.contains('notification-dropdown-item--unread');
                        if (isUnread) {
                            await markRead(row.dataset.id, row);
                        }
                        if (actionUrl && actionUrl.charAt(0) === '/') {
                            window.location.href = actionUrl;
                        }
                    });
                }

                if (markAllBtn) {
                    markAllBtn.addEventListener('click', async (e) => {
                        e.stopPropagation();
                        if (!markAllUrl) return;
                        try {
                            await fetch(markAllUrl, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                            });
                            dropdownLoaded = false;
                            await loadDropdown(true);
                            await refresh();
                        } catch (err) {}
                    });
                }

                if (bellBtn && dropdown) {
                    bellBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const isOpen = !dropdown.classList.contains('hidden');
                        dropdown.classList.toggle('hidden', isOpen);
                        bellBtn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
                        document.getElementById('userMenu')?.classList.add('hidden');
                        document.getElementById('userMenuBtn')?.setAttribute('aria-expanded', 'false');
                        document.getElementById('fontSizeMenu')?.classList.add('hidden');
                        if (!isOpen) loadDropdown(true);
                    });
                    dropdown.addEventListener('click', (e) => e.stopPropagation());
                }

                async function refresh() {
                    if (!unreadUrl || !badge) return;
                    if (window.requestGuard && !window.requestGuard.canProceed('notification-badge')) {
                        return;
                    }
                    const run = window.requestGuard
                        ? () => window.requestGuard.guardedFetch(unreadUrl, {}, 'notification-badge')
                        : () => fetch(unreadUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' }).then(r => ({ skipped: false, response: r }));
                    try {
                        const result = await run();
                        if (result.skipped || !result.response) return;
                        const r = result.response;
                        if (!r.ok) return;
                        const data = await r.json();
                        if (data && typeof data.count !== 'undefined') applyCount(data.count);
                    } catch (e) {}
                }

                window.refreshNotificationBadge = refresh;

                let intervalId = null;
                function start() {
                    if (intervalId) return;
                    intervalId = setInterval(refresh, 30000);
                }
                function stop() {
                    if (intervalId) { clearInterval(intervalId); intervalId = null; }
                }
                document.addEventListener('visibilitychange', function() {
                    if (document.hidden) { stop(); } else { refresh(); start(); }
                });
                refresh();
                start();
            })();
        </script>
        @endif
    @endauth
</body>
</html>
