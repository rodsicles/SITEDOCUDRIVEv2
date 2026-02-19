<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Employee Dashboard with Data Analytics - SITE')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Menu Item Styles */
        .menu-item {
            padding: 12px 16px;
            margin: 4px 0;
            display: flex;
            align-items: center;
            color: #2c3e50;
            text-decoration: none;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.9375rem;
            letter-spacing: 0.01em;
        }
        
        [data-theme="dark"] .menu-item {
            color: #e0e0e0;
        }

        .menu-item:hover {
            background: rgba(2, 138, 15, 0.06);
            transform: translateX(4px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .menu-item.active {
            background: #028a0f;
            color: white;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(2, 138, 15, 0.25);
            transform: translateX(0);
        }

        .menu-item.active:hover {
            background: #026a0c;
            transform: translateX(2px);
        }

        .menu-item i {
            margin-right: 12px;
            font-size: 1.25rem;
            width: 24px;
            text-align: center;
            transition: transform 0.25s ease;
        }

        .menu-item:hover i {
            transform: scale(1.1);
        }

        .menu-item.active i {
            transform: scale(1.05);
        }

        /* Badge Styles */
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="overflow-x-hidden bg-gray-100 dark:bg-[#121212] text-gray-800 dark:text-gray-200" data-font-size="medium">
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-white/85 dark:bg-black/85 backdrop-blur-sm z-[99999] hidden items-center justify-center animate-[fadeIn_0.3s_ease]">
        <div class="text-center bg-white dark:bg-[#2a2a2a] p-10 px-12 rounded-3xl shadow-[0_20px_60px_rgba(0,0,0,0.3)] animate-[bounceIn_0.5s_ease]">
            <div class="w-12 h-12 border-4 border-gray-300 dark:border-gray-600 border-t-[#028a0f] dark:border-t-[#02b815] rounded-full animate-spin mx-auto mb-5"></div>
            <div id="loadingText" class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">Please wait</div>
            <div id="loadingSubtext" class="text-sm text-gray-600 dark:text-gray-400">Processing...</div>
        </div>
    </div>

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-white dark:bg-[#2a2a2a] shadow-md fixed h-screen overflow-y-auto transition-all duration-300 z-[1000] sidebar">
            <div class="p-6 bg-gradient-to-br from-[#028a0f] to-[#026a0c] text-white text-center">
                <img src="{{ asset('uploads/documents/site_logo-removebg-preview.png') }}" alt="SITE Logo" class="w-16 h-16 mb-2 object-contain bg-white p-1 rounded-full shadow-lg border-2 border-white/80 mx-auto">
                <h2 class="text-base leading-tight mb-2 font-semibold">Employee Dashboard with Data Analytics</h2>
                <p class="text-xs opacity-95 mb-1">School of Information Technology and Engineering</p>
                <p class="text-xs font-semibold">{{ auth()->user()->role->role_name }}</p>
            </div>
            <nav class="p-3">
                @yield('sidebar')
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="ml-64 flex-1 p-8 w-[calc(100%-16rem)] main-content">
            <!-- Top Bar -->
            <div class="bg-white dark:bg-[#2a2a2a] p-5 px-8 rounded-xl shadow-md mb-8 flex justify-between items-center animate-[slideDown_0.5s_ease]">
                <div>
                    <h1 class="text-3xl text-gray-800 dark:text-gray-200 mb-1 font-semibold">@yield('page-title', 'Dashboard')</h1>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">@yield('page-subtitle', 'Welcome back!')</p>
                </div>
                <div class="flex items-center gap-4">
                    @if(auth()->user()->isFaculty())
                    <a href="{{ route('faculty.notifications') }}" class="relative text-2xl text-gray-600 dark:text-gray-400 hover:text-[#028a0f] dark:hover:text-[#02b815] transition-colors">
                        <i class="fas fa-bell"></i>
                        @if(isset($unreadNotifications) && $unreadNotifications > 0)
                        <span class="absolute -top-2 -right-2 bg-red-600 text-white rounded-full w-[18px] h-[18px] text-[11px] flex items-center justify-center font-bold">{{ $unreadNotifications }}</span>
                        @endif
                    </a>
                    @endif
                    
                    <!-- Theme & Settings Controls -->
                    <div class="flex gap-2 items-center">
                        <!-- Font Size -->
                        <div class="relative">
                            <button id="fontSizeBtn" class="bg-transparent border-none text-gray-600 dark:text-gray-400 text-xl p-2 rounded-full transition-all hover:bg-[rgba(2,138,15,0.1)] hover:text-[#026a0c] dark:hover:text-[#02b815] cursor-pointer" title="Font Size">
                                <i class="fas fa-text-height"></i>
                            </button>
                            <div id="fontSizeMenu" class="hidden absolute top-full right-0 bg-white dark:bg-[#2a2a2a] rounded-lg shadow-xl p-2 min-w-[120px] z-[1000] mt-1">
                                <button onclick="changeFontSize('small')" class="block w-full px-3 py-2 bg-transparent border-none text-left cursor-pointer rounded transition-colors text-gray-800 dark:text-gray-200 hover:bg-[rgba(2,138,15,0.1)]">Small</button>
                                <button onclick="changeFontSize('medium')" class="block w-full px-3 py-2 bg-transparent border-none text-left cursor-pointer rounded transition-colors text-gray-800 dark:text-gray-200 hover:bg-[rgba(2,138,15,0.1)]">Medium</button>
                                <button onclick="changeFontSize('large')" class="block w-full px-3 py-2 bg-transparent border-none text-left cursor-pointer rounded transition-colors text-gray-800 dark:text-gray-200 hover:bg-[rgba(2,138,15,0.1)]">Large</button>
                            </div>
                        </div>
                        
                        <!-- Dark Mode Toggle -->
                        <button id="darkModeToggle" class="bg-transparent border-none text-gray-600 dark:text-gray-400 text-xl p-2 rounded-full transition-all hover:bg-[rgba(2,138,15,0.1)] hover:text-[#026a0c] dark:hover:text-[#02b815] cursor-pointer" title="Toggle Dark Mode">
                            <i class="fas fa-moon"></i>
                        </button>
                        
                        <!-- Global Search -->
                        <button id="globalSearchBtn" class="bg-transparent border-none text-gray-600 dark:text-gray-400 text-xl p-2 rounded-full transition-all hover:bg-[rgba(2,138,15,0.1)] hover:text-[#026a0c] dark:hover:text-[#02b815] cursor-pointer" title="Search">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    
                    <div class="w-11 h-11 rounded-full bg-gradient-to-br from-[#028a0f] to-[#026a0c] text-white flex items-center justify-center font-semibold text-lg">
                        {{ strtoupper(substr(auth()->user()->username, 0, 2)) }}
                    </div>
                    
                    <!-- User Dropdown Menu -->
                    <div class="relative">
                        <button id="userMenuBtn" class="bg-transparent border-none text-gray-800 dark:text-gray-200 text-sm px-3 py-2 rounded-lg cursor-pointer transition-all hover:bg-gray-100 dark:hover:bg-gray-700">
                            {{ auth()->user()->username }} <i class="fas fa-chevron-down text-xs ml-1"></i>
                        </button>
                        <div id="userMenu" class="hidden absolute top-full right-0 bg-white dark:bg-[#2a2a2a] rounded-lg shadow-xl p-2 min-w-[180px] z-[1000] mt-1">
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-gray-800 dark:text-gray-200 no-underline rounded transition-colors hover:bg-[rgba(2,138,15,0.1)]">
                                <i class="fas fa-user-edit"></i> Edit Profile
                            </a>
                            <form action="{{ route('logout') }}" method="POST" class="m-0" id="logoutForm">
                                @csrf
                                <button type="submit" class="block w-full text-left px-4 py-2 bg-transparent border-none text-gray-800 dark:text-gray-200 cursor-pointer rounded transition-colors hover:bg-[rgba(2,138,15,0.1)]">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
            @if(session('success'))
            <div class="px-5 py-4 rounded-lg mb-5 bg-green-100 text-green-800 border-l-4 border-green-500 animate-[slideDown_0.5s_ease]">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
            </div>
            @endif

            @if(session('error'))
            <div class="px-5 py-4 rounded-lg mb-5 bg-red-100 text-red-800 border-l-4 border-red-500 animate-[slideDown_0.5s_ease]">
                <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
            </div>
            @endif

            @if($errors->any())
            <div class="px-5 py-4 rounded-lg mb-5 bg-red-100 text-red-800 border-l-4 border-red-500 animate-[slideDown_0.5s_ease]">
                <ul class="m-0 pl-5">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- Page Content -->
            @yield('content')
        </main>
    </div>

    <!-- Global Search Modal -->
    <div id="searchModal" class="hidden fixed inset-0 bg-black/70 z-[9999] items-start justify-center pt-24">
        <div class="bg-white dark:bg-[#2a2a2a] rounded-xl w-[90%] max-w-2xl shadow-xl animate-[slideDown_0.3s_ease]">
            <div class="p-5 border-b-2 border-gray-200 dark:border-gray-700">
                <input type="text" id="globalSearchInput" placeholder="Search employees, tasks, documents..." autocomplete="off" class="w-full p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg text-base transition-colors focus:outline-none focus:border-[#028a0f] dark:focus:border-[#02b815] bg-white dark:bg-[#1e1e1e] text-gray-800 dark:text-gray-200">
            </div>
            <div id="searchResults" class="max-h-96 overflow-y-auto p-2">
                <p class="text-center text-gray-600 dark:text-gray-400 p-5">Type to search...</p>
            </div>
        </div>
    </div>

    <!-- Document Preview Modal -->
    <div id="documentPreviewModal" class="hidden fixed inset-0 bg-black/70 z-[9999] items-center justify-center">
        <div class="bg-white dark:bg-[#2a2a2a] rounded-xl max-w-[90%] max-h-[90vh] h-[90vh] w-[90%]">
            <div class="flex justify-between items-center p-5 border-b-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-[#2a2a2a]">
                <h3 id="previewTitle" class="m-0 text-gray-800 dark:text-gray-200 text-lg font-semibold">Document Preview</h3>
                <button onclick="closePreview()" class="bg-transparent border-none text-3xl cursor-pointer text-gray-800 dark:text-gray-200 w-10 h-10 rounded-full flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">×</button>
            </div>
            <iframe id="previewFrame" class="w-full h-[calc(100%-80px)] border-none bg-white"></iframe>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-20 right-5 z-[10000]"></div>

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
                    <div class="p-3 rounded-lg mb-2 cursor-pointer transition-colors hover:bg-[rgba(2,138,15,0.1)]" onclick="window.location.href='${result.url}'">
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

        // Logout Form Loading Effect
        const logoutForm = document.getElementById('logoutForm');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const loadingText = document.getElementById('loadingText');
        const loadingSubtext = document.getElementById('loadingSubtext');
        
        if (logoutForm) {
            logoutForm.addEventListener('submit', function(e) {
                loadingText.textContent = 'Logging out';
                loadingSubtext.textContent = 'Please wait...';
                loadingOverlay.classList.remove('hidden');
                loadingOverlay.classList.add('flex');
            });
        }

        // Toast Notification System
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                info: 'bg-blue-500'
            };
            toast.className = `${colors[type] || colors.success} text-white px-6 py-4 rounded-lg mb-2 shadow-lg flex items-center gap-3 min-w-[300px] animate-[slideIn_0.3s_ease]`;
            
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
    </script>

    @stack('scripts')
</body>
</html>
