<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Employee Dashboard with Data Analytics - SITE')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/SPUP-final-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Menu Item Styles */
        .menu-item {
            padding: 8px 12px;
            margin: 2px 0;
            display: flex;
            align-items: center;
            color: #2c3e50;
            text-decoration: none;
            position: relative;
            font-weight: 500;
            font-size: 0.875rem;
            letter-spacing: 0em;
            border: 1px solid transparent;
            background: transparent;
        }

        [data-theme="dark"] .menu-item {
            color: #e0e0e0;
        }

        .menu-item:active {
            background: rgba(2, 138, 15, 0.1);
            border-color: #028a0f;
        }

        .menu-item.active {
            background: #028a0f;
            color: white;
            font-weight: 600;
            border-color: #028a0f;
        }

        .menu-item.active:active {
            background: #026a0c;
            border-color: #026a0c;
        }

        .menu-item i {
            margin-right: 10px;
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }

        /* Badge Styles */
        .badge {
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            border: 1px solid #d0d0d0;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 0.75rem !important;
            }

            .top-bar {
                padding: 0.75rem 1rem !important;
                margin-bottom: 0.75rem !important;
            }

            .top-bar h1 {
                font-size: 1.25rem !important;
            }

            .top-bar p {
                font-size: 0.75rem !important;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr !important;
                gap: 0.5rem !important;
            }

            .stat-card {
                padding: 0.75rem !important;
            }

            .stat-value {
                font-size: 1.25rem !important;
            }

            .stat-label {
                font-size: 0.65rem !important;
            }

            .content-card {
                padding: 0.75rem !important;
                margin-bottom: 0.75rem !important;
            }

            .data-table {
                font-size: 0.75rem;
            }

            .data-table thead th {
                font-size: 0.6rem;
                padding: 0.4rem 0.4rem;
            }

            .data-table tbody td {
                padding: 0.4rem 0.4rem;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="overflow-x-hidden bg-gray-100 dark:bg-[#121212] text-gray-800 dark:text-gray-200" data-font-size="medium">


    <div class="flex min-h-screen">
        <!-- Sidebar Overlay (mobile) -->
        <div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-[999] hidden md:hidden"></div>

        <!-- Sidebar -->
        <aside class="w-64 bg-white dark:bg-[#2a2a2a] border-r border-gray-200 dark:border-gray-700 fixed h-screen overflow-hidden z-[1000] sidebar">
            <div class="sidebar-brand">
                <div class="sidebar-brand-line"></div>
                <img src="{{ asset('images/site-logo.png') }}" alt="SITE Logo" class="sidebar-brand-logo">
                <div class="sidebar-brand-line"></div>
                <h2 class="sidebar-brand-title">EMPLOYEE DASHBOARD</h2>
                <p class="sidebar-brand-welcome">Welcome, {{ auth()->user()->role->role_name }}</p>
                <a href="{{ route('user-guide') }}" class="sidebar-guide-btn {{ request()->routeIs('user-guide') ? 'active' : '' }}">
                    <i class="fas fa-book-open"></i> User Guide
                </a>
            </div>
            <nav class="p-2">
                @yield('sidebar')
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="ml-64 flex-1 p-8 w-[calc(100%-16rem)] main-content">
            <!-- Top Bar -->
            <div class="bg-white dark:bg-[#2a2a2a] p-4 px-6 border border-gray-200 dark:border-gray-700 mb-4 flex justify-between items-center top-bar sticky top-0 z-[300] shadow-sm">
                <div class="flex items-center gap-3 min-w-0">
                    <!-- Mobile Hamburger Menu -->
                    <button id="mobileMenuToggle" class="hidden max-md:block text-xl text-gray-800 dark:text-gray-200 bg-transparent border-none cursor-pointer flex-shrink-0 p-1">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="min-w-0">
                        <h1 class="text-2xl max-md:text-lg text-gray-800 dark:text-gray-200 mb-0 font-semibold truncate">@yield('page-title', 'Dashboard')</h1>
                        <p class="text-gray-600 dark:text-gray-400 text-xs max-md:text-xs truncate">@yield('page-subtitle', 'Welcome back!')</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 max-md:gap-2 flex-shrink-0">
                    @if(auth()->user()->isFaculty() || auth()->user()->isProgramCoordinator())
                    <a href="{{ auth()->user()->isFaculty() ? route('faculty.notifications') : route('coordinator.notifications') }}" class="relative text-lg max-md:text-base text-gray-600 dark:text-gray-400" id="notification-bell-link">
                        <i class="fas fa-bell"></i>
                        <span id="notification-badge" class="absolute -top-2 -right-2 bg-[#028a0f] text-white w-4 h-4 text-xs flex items-center justify-center font-bold {{ (isset($unreadNotifications) && $unreadNotifications > 0) ? '' : 'hidden' }}">{{ $unreadNotifications ?? 0 }}</span>
                    </a>
                    @endif

                    <!-- Theme & Settings Controls -->
                    <div class="flex gap-2 max-md:gap-1 items-center">
                        <!-- Font Size (hidden on mobile) -->
                        <div class="relative max-md:hidden">
                            <button id="fontSizeBtn" class="bg-transparent border-none text-gray-600 dark:text-gray-400 text-lg p-2 cursor-pointer" title="Font Size">
                                <i class="fas fa-text-height"></i>
                            </button>
                            <div id="fontSizeMenu" class="hidden absolute top-full right-0 bg-white dark:bg-[#2a2a2a] border border-gray-200 dark:border-gray-700 p-2 min-w-[100px] z-[1000] mt-1">
                                <button onclick="changeFontSize('small')" class="block w-full px-2 py-1 bg-transparent border-none text-left cursor-pointer text-gray-800 dark:text-gray-200">Small</button>
                                <button onclick="changeFontSize('medium')" class="block w-full px-2 py-1 bg-transparent border-none text-left cursor-pointer text-gray-800 dark:text-gray-200">Medium</button>
                                <button onclick="changeFontSize('large')" class="block w-full px-2 py-1 bg-transparent border-none text-left cursor-pointer text-gray-800 dark:text-gray-200">Large</button>
                            </div>
                        </div>

                        <!-- Dark Mode Toggle -->
                        <button id="darkModeToggle" class="bg-transparent border-none text-gray-600 dark:text-gray-400 text-lg max-md:text-base p-2 max-md:p-1 cursor-pointer" title="Toggle Dark Mode">
                            <i class="fas fa-moon"></i>
                        </button>

                        <!-- Global Search -->
                        <button id="globalSearchBtn" class="bg-transparent border-none text-gray-600 dark:text-gray-400 text-lg max-md:text-base p-2 max-md:p-1 cursor-pointer" title="Search">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>

                    <div class="w-10 h-10 max-md:w-8 max-md:h-8 bg-[#028a0f] text-white flex items-center justify-center font-semibold text-sm max-md:text-xs flex-shrink-0 border border-[#026a0c]">
                        {{ strtoupper(substr(auth()->user()->username, 0, 2)) }}
                    </div>

                    <!-- User Dropdown Menu -->
                    <div class="relative">
                        <button id="userMenuBtn" class="bg-transparent border-none text-gray-800 dark:text-gray-200 text-sm px-2 py-1 max-md:px-1 max-md:py-1 cursor-pointer font-medium">
                            <span class="max-md:hidden">{{ auth()->user()->username }}</span> <i class="fas fa-chevron-down text-xs ml-1"></i>
                        </button>
                        <div id="userMenu" class="hidden absolute top-full right-0 bg-white dark:bg-[#2a2a2a] border border-gray-200 dark:border-gray-700 min-w-[200px] z-[1000] mt-1">
                            <!-- User Info -->
                            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 m-0">{{ auth()->user()->employee->full_name ?? auth()->user()->username }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 m-0 mt-1">
                                    <span class="inline-block px-2 py-0.5 bg-[#028a0f] text-white text-[10px] font-semibold">{{ auth()->user()->role->role_name }}</span>
                                </p>
                            </div>
                            <!-- Menu Items -->
                            <div class="py-1">
                                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-gray-700 dark:text-gray-200 no-underline text-sm">
                                    <i class="fas fa-user-edit text-gray-400 dark:text-gray-500 w-4 text-center"></i> Edit Profile
                                </a>
                            </div>
                            <!-- Logout -->
                            <div class="border-t border-gray-200 dark:border-gray-700 p-2">
                                <form action="{{ route('logout') }}" method="POST" class="m-0" id="logoutForm">
                                    @csrf
                                    <button type="submit" class="flex items-center justify-center gap-2 w-full px-4 py-2.5 bg-red-600 text-white border-none cursor-pointer text-sm font-semibold">
                                        <i class="fas fa-sign-out-alt"></i> Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alerts removed - using toast only -->

            <!-- Page Content -->
            @yield('content')
        </main>
    </div>

    <!-- Global Search Modal -->
    <div id="searchModal" class="hidden fixed inset-0 bg-black/60 z-[9999] items-start justify-center pt-16">
        <div class="bg-white dark:bg-[#2a2a2a] w-[90%] max-w-2xl border border-gray-200 dark:border-gray-700">
            <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                <input type="text" id="globalSearchInput" placeholder="Search files, announcements, users..." autocomplete="off" maxlength="80" class="w-full p-2 border border-gray-300 dark:border-gray-600 text-xs focus:outline-none focus:border-[#028a0f] dark:focus:border-[#028a0f] bg-white dark:bg-[#1e1e1e] text-gray-800 dark:text-gray-200">
            </div>
            <div id="searchResults" class="max-h-72 overflow-y-auto p-2">
                <p class="text-center text-gray-600 dark:text-gray-400 p-3 text-xs">Type to search...</p>
            </div>
        </div>
    </div>



    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-20 right-5 z-[10000]"></div>

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
        });
        
        // User Menu Toggle
        const userMenuBtn = document.getElementById('userMenuBtn');
        const userMenu = document.getElementById('userMenu');
        
        userMenuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userMenu.classList.toggle('hidden');
            fontSizeMenu.classList.add('hidden');
        });
        
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
        
        globalSearchBtn.addEventListener('click', () => {
            searchModal.classList.remove('hidden');
            searchModal.classList.add('flex');
            searchInput.focus();
        });
        
        searchModal.addEventListener('click', (e) => {
            if (e.target === searchModal) {
                searchModal.classList.add('hidden');
                searchModal.classList.remove('flex');
                searchInput.value = '';
                searchResults.innerHTML = '<p class="text-center text-gray-600 dark:text-gray-400 p-5">Type to search...</p>';
            }
        });
        
        // ESC key to close search
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !searchModal.classList.contains('hidden')) {
                searchModal.classList.add('hidden');
                searchModal.classList.remove('flex');
                searchInput.value = '';
            }
        });
        
        // Search functionality
        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();
            
            if (query.length < 3) {
                searchResults.innerHTML = '<p class="text-center text-gray-600 dark:text-gray-400 p-5">Type at least 3 characters...</p>';
                return;
            }
            
            searchResults.innerHTML = '<p class="text-center text-gray-600 dark:text-gray-400 p-5"><i class="fas fa-spinner fa-spin"></i> Searching...</p>';
            
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
            .catch(error => {
                searchResults.innerHTML = '<p class="text-center text-gray-600 dark:text-gray-400 p-5">No results found</p>';
            });
        }
        
        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function displaySearchResults(results) {
            if (results.length === 0) {
                searchResults.innerHTML = '<p class="text-center text-gray-600 dark:text-gray-400 p-5">No results found</p>';
                return;
            }

            searchResults.innerHTML = '';
            results.forEach(result => {
                const item = document.createElement('div');
                item.className = 'p-3 mb-2 cursor-pointer hover:bg-[rgba(2,138,15,0.1)]';
                item.addEventListener('click', () => {
                    window.location.href = result.url;
                });

                const title = document.createElement('div');
                title.className = 'font-semibold text-gray-800 dark:text-gray-200 mb-1';
                title.textContent = result.title;

                const type = document.createElement('div');
                type.className = 'text-[10px] uppercase tracking-wide text-[#028a0f] dark:text-[#34d399] font-semibold mb-0.5';
                type.textContent = result.type || 'Result';

                item.appendChild(title);
                item.appendChild(type);

                if (result.subtitle) {
                    const subtitle = document.createElement('div');
                    subtitle.className = 'text-xs text-gray-600 dark:text-gray-400';
                    subtitle.textContent = result.subtitle;
                    item.appendChild(subtitle);
                }

                searchResults.appendChild(item);
            });
        }
        
        // Keyboard shortcut: Ctrl+K for search
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                searchModal.classList.remove('hidden');
                searchModal.classList.add('flex');
                searchInput.focus();
            }
        });

        // Toast Notification System
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                info: 'bg-blue-500'
            };
            toast.className = `${colors[type] || colors.success} text-white px-6 py-4 mb-2 flex items-center gap-3 min-w-[300px]`;
            
            const icon = type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ';
            toast.innerHTML = `
                <span class="text-xl font-bold">${icon}</span>
                <span>${message}</span>
            `;
            
            document.getElementById('toastContainer').appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        @if(session('success'))
            showToast('{{ session("success") }}', 'success');
        @endif

        @if(session('error'))
            showToast('{{ session("error") }}', 'error');
        @endif

        @if($errors->any())
            showToast('{{ $errors->first() }}', 'error');
        @endif



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
                wrapper.style.cssText = 'position:relative;border:2px dashed #cbd5e1;border-radius:6px;padding:18px;background:#f8fafc;color:#475569;text-align:center;font-size:12px;cursor:pointer;transition:all .15s;';

                const label = document.createElement('div');
                label.innerHTML = '<i class="fas fa-cloud-upload-alt" style="font-size:22px;color:#64748b;display:block;margin-bottom:6px;"></i>'
                    + '<strong style="color:#334155;">Drag &amp; drop file here</strong>'
                    + '<span style="opacity:.7;"> or click to browse</span>'
                    + '<div data-drop-filename style="margin-top:6px;font-size:11px;color:#0f766e;font-weight:600;"></div>';

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
                        wrapper.style.borderColor = '#028a0f';
                        wrapper.style.background = '#f0fdf4';
                        wrapper.style.color = '#028a0f';
                    })
                );
                ['dragleave', 'drop'].forEach(evt =>
                    wrapper.addEventListener(evt, e => {
                        e.preventDefault();
                        wrapper.style.borderColor = '#cbd5e1';
                        wrapper.style.background = '#f8fafc';
                        wrapper.style.color = '#475569';
                    })
                );
                wrapper.addEventListener('drop', e => {
                    if (!e.dataTransfer || !e.dataTransfer.files || !e.dataTransfer.files.length) return;
                    // Use DataTransfer to assign dropped files to the input.
                    try {
                        const dt = new DataTransfer();
                        const acceptMultiple = input.multiple;
                        const files = acceptMultiple ? Array.from(e.dataTransfer.files) : [e.dataTransfer.files[0]];
                        files.forEach(f => dt.items.add(f));
                        input.files = dt.files;
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    } catch (err) {
                        // Older browsers: fall back to triggering click so user picks again.
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
        }

        function closeSidebar() {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.add('hidden');
            document.body.style.overflow = '';
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
        @if(auth()->user()->isFaculty() || auth()->user()->isProgramCoordinator())
        <script>
            // Notification badge live polling (every 30s) for the faculty top-bar bell.
            (function() {
                const url = "{{ auth()->user()->isFaculty() ? route('faculty.notifications.unread-count') : route('coordinator.notifications.unread-count') }}";
                const badge = document.getElementById('notification-badge');
                if (!badge) return;

                function applyCount(count) {
                    if (!badge) return;
                    if (count > 0) {
                        badge.textContent = count > 99 ? '99+' : count;
                        badge.classList.remove('hidden');
                    } else {
                        badge.textContent = '0';
                        badge.classList.add('hidden');
                    }
                }

                async function refresh() {
                    if (window.requestGuard && !window.requestGuard.canProceed('notification-badge')) {
                        return;
                    }
                    const run = window.requestGuard
                        ? () => window.requestGuard.guardedFetch(url, {}, 'notification-badge')
                        : () => fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' }).then(r => ({ skipped: false, response: r }));
                    try {
                        const result = await run();
                        if (result.skipped || !result.response) return;
                        const r = result.response;
                        if (!r.ok) return;
                        const data = await r.json();
                        if (data && typeof data.count !== 'undefined') applyCount(data.count);
                    } catch (e) {}
                }

                // Expose so per-page scripts (e.g. notifications page) can force-refresh
                window.refreshNotificationBadge = refresh;

                // Pause polling when tab is hidden to save resources
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
