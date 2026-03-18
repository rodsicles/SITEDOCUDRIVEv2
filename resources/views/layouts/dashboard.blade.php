<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Employee Dashboard with Data Analytics - SITE')</title>
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
        <aside class="w-64 bg-white dark:bg-[#2a2a2a] border-r border-gray-200 dark:border-gray-700 fixed h-screen overflow-y-auto z-[1000] sidebar">
            <div class="p-4 bg-[#028a0f] text-white text-center border-b border-[#026a0c]">
                <img src="{{ asset('images/site-logo.png') }}" alt="SITE Logo" class="w-14 h-14 mb-2 object-contain bg-white p-1 border-2 border-white/80 mx-auto">
                <h2 class="text-sm leading-tight mb-1 font-semibold">Employee Dashboard</h2>
                <p class="text-xs opacity-95 mb-1">Data Analytics System</p>
                <p class="text-xs font-semibold">{{ auth()->user()->role->role_name }}</p>
            </div>
            <nav class="p-2">
                @yield('sidebar')
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="ml-64 flex-1 p-8 w-[calc(100%-16rem)] main-content">
            <!-- Top Bar -->
            <div class="bg-white dark:bg-[#2a2a2a] p-4 px-6 border border-gray-200 dark:border-gray-700 mb-4 flex justify-between items-center top-bar">
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
                    @if(auth()->user()->isFaculty())
                    <a href="{{ route('faculty.notifications') }}" class="relative text-lg max-md:text-base text-gray-600 dark:text-gray-400">
                        <i class="fas fa-bell"></i>
                        @if(isset($unreadNotifications) && $unreadNotifications > 0)
                        <span class="absolute -top-2 -right-2 bg-[#028a0f] text-white w-4 h-4 text-xs flex items-center justify-center font-bold">{{ $unreadNotifications }}</span>
                        @endif
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
                        <button id="userMenuBtn" class="bg-transparent border-none text-gray-800 dark:text-gray-200 text-xs px-2 py-1 max-md:px-1 max-md:py-1 cursor-pointer">
                            <span class="max-md:hidden">{{ auth()->user()->username }}</span> <i class="fas fa-chevron-down text-xs ml-1"></i>
                        </button>
                        <div id="userMenu" class="hidden absolute top-full right-0 bg-white dark:bg-[#2a2a2a] border border-gray-200 dark:border-gray-700 p-2 min-w-[140px] z-[1000] mt-1">
                            <a href="{{ route('profile.edit') }}" class="block px-3 py-1 text-gray-800 dark:text-gray-200 no-underline text-xs">
                                <i class="fas fa-user-edit"></i> Edit Profile
                            </a>
                            <form action="{{ route('logout') }}" method="POST" class="m-0" id="logoutForm">
                                @csrf
                                <button type="submit" class="block w-full text-left px-3 py-1 bg-transparent border-none text-gray-800 dark:text-gray-200 cursor-pointer text-xs">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </button>
                            </form>
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
                <input type="text" id="globalSearchInput" placeholder="Search employees, tasks, documents..." autocomplete="off" maxlength="35" class="w-full p-2 border border-gray-300 dark:border-gray-600 text-xs focus:outline-none focus:border-[#028a0f] dark:focus:border-[#028a0f] bg-white dark:bg-[#1e1e1e] text-gray-800 dark:text-gray-200">
            </div>
            <div id="searchResults" class="max-h-72 overflow-y-auto p-2">
                <p class="text-center text-gray-600 dark:text-gray-400 p-3 text-xs">Type to search...</p>
            </div>
        </div>
    </div>

    <!-- Document Preview Modal -->
    <div id="documentPreviewModal" class="hidden fixed inset-0 bg-black/60 z-[9999] items-center justify-center">
        <div class="bg-white dark:bg-[#2a2a2a] max-w-[90%] max-h-[90vh] h-[90vh] w-[90%] border border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center p-3 border-b border-gray-200 dark:border-gray-700">
                <h3 id="previewTitle" class="m-0 text-gray-800 dark:text-gray-200 text-sm font-semibold">Document Preview</h3>
                <button onclick="closePreview()" class="bg-transparent border-none text-xl cursor-pointer text-gray-800 dark:text-gray-200 w-8 h-8 flex items-center justify-center">×</button>
            </div>
            <iframe id="previewFrame" class="w-full h-[calc(100%-48px)] border-none bg-white"></iframe>
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
            
            if (query.length < 2) {
                searchResults.innerHTML = '<p class="text-center text-gray-600 dark:text-gray-400 p-5">Type at least 2 characters...</p>';
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
        
        function displaySearchResults(results) {
            if (results.length === 0) {
                searchResults.innerHTML = '<p class="text-center text-gray-600 dark:text-gray-400 p-5">No results found</p>';
                return;
            }
            
            let html = '';
            results.forEach(result => {
                html += `
                    <div class="p-3 mb-2 cursor-pointer hover:bg-[rgba(2,138,15,0.1)]" onclick="window.location.href='${result.url}'">
                        <div class="font-semibold text-gray-800 dark:text-gray-200 mb-1">${result.title}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">${result.type}</div>
                    </div>
                `;
            });
            searchResults.innerHTML = html;
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

        // Document Preview Modal Functions
        function openPreview(url, title) {
            document.getElementById('previewTitle').textContent = title;
            document.getElementById('previewFrame').src = url;
            const modal = document.getElementById('documentPreviewModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closePreview() {
            const modal = document.getElementById('documentPreviewModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.getElementById('previewFrame').src = '';
        }

        // Close preview on ESC
        document.addEventListener('keydown', (e) => {
            const modal = document.getElementById('documentPreviewModal');
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closePreview();
            }
        });

        // Mobile Sidebar Toggle
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const sidebar = document.querySelector('.sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

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
</body>
</html>
