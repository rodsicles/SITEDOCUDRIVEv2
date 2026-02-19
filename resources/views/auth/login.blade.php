<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Employee Dashboard with Data Analytics - SITE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="flex justify-center items-center min-h-screen h-full p-5 bg-gradient-to-br from-[#028a0f] to-[#026a0c] dark:from-[#0a1f0c] dark:to-[#051108] transition-colors duration-300" data-font-size="medium">
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-white/85 dark:bg-black/85 backdrop-blur-sm z-[9999] hidden items-center justify-center animate-[fadeIn_0.3s_ease] transition-colors duration-300">
        <div class="text-center bg-white dark:bg-[#2a2a2a] p-10 px-12 rounded-3xl shadow-[0_20px_60px_rgba(0,0,0,0.3)] animate-[bounceIn_0.5s_ease] transition-colors duration-300">
            <div class="w-12 h-12 border-4 border-gray-300 dark:border-gray-600 border-t-[#028a0f] dark:border-t-[#02b815] rounded-full animate-spin mx-auto mb-5 transition-colors duration-300"></div>
            <div class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2 transition-colors duration-300">Logging in</div>
            <div class="text-sm text-gray-600 dark:text-gray-400 transition-colors duration-300">Please wait...</div>
        </div>
    </div>

    <div class="bg-white dark:bg-[#2a2a2a] rounded-3xl shadow-[0_20px_60px_rgba(0,0,0,0.3)] overflow-hidden max-w-md w-full animate-[slideUp_0.6s_ease] relative transition-colors duration-300">
        <div class="p-10 px-8 bg-gradient-to-br from-[#028a0f] to-[#026a0c] dark:from-[#02b815] dark:to-[#028a0f] text-white text-center relative transition-colors duration-300">
            <button id="themeToggle" type="button" class="absolute top-4 right-4 bg-white/20 dark:bg-white/10 border-none rounded-full w-10 h-10 flex items-center justify-center cursor-pointer transition-all duration-300 hover:bg-white/30 dark:hover:bg-white/20 hover:scale-110 text-white text-lg">
                <i class="fas fa-moon"></i>
            </button>
            <img src="{{ asset('uploads/documents/site_logo-removebg-preview.png') }}" alt="SITE Logo" class="w-20 h-20 mb-4 object-contain bg-white p-1 rounded-full shadow-lg border-2 border-white/90 mx-auto">
            <h1 class="text-2xl leading-tight mb-2 font-semibold">Employee Dashboard with Data Analytics</h1>
            <p class="text-sm mb-1">School of Information Technology and Engineering (SITE)</p>
            <p class="text-sm opacity-90">Sign in to continue</p>
        </div>
        <div class="p-10 px-8">
            @if($errors->any())
            <div class="px-3 py-3 rounded-lg mb-5 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 border-l-4 border-red-500 dark:border-red-400 text-sm transition-colors duration-300">
                {{ $errors->first() }}
            </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" id="loginForm">
                @csrf
                <div class="mb-6">
                    <label class="block mb-2 font-medium text-gray-800 dark:text-gray-200 text-sm transition-colors duration-300">Username</label>
                    <input type="text" name="username" class="w-full px-5 py-4 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-base transition-all duration-300 focus:outline-none focus:border-[#028a0f] dark:focus:border-[#02b815] focus:shadow-[0_0_0_4px_rgba(2,138,15,0.1)] bg-white dark:bg-[#1e1e1e] text-gray-800 dark:text-gray-200" placeholder="Enter your username" required autofocus>
                </div>

                <div class="mb-6">
                    <label class="block mb-2 font-medium text-gray-800 dark:text-gray-200 text-sm transition-colors duration-300">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" class="w-full px-5 py-4 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-base transition-all duration-300 focus:outline-none focus:border-[#028a0f] dark:focus:border-[#02b815] focus:shadow-[0_0_0_4px_rgba(2,138,15,0.1)] bg-white dark:bg-[#1e1e1e] text-gray-800 dark:text-gray-200" placeholder="Enter your password" required>
                    </div>
                </div>

                <button type="submit" class="w-full px-4 py-4 bg-[#028a0f] dark:bg-[#02b815] text-white border-none rounded-xl text-base font-semibold cursor-pointer transition-all duration-300 hover:bg-[#026a0c] dark:hover:bg-[#028a0f] hover:-translate-y-0.5 hover:shadow-[0_8px_20px_rgba(2,138,15,0.3)] active:translate-y-0 mt-2">
                    Sign In
                </button>
            </form>
        </div>
    </div>

    <script>
        // Dark Mode Toggle
        const themeToggle = document.getElementById('themeToggle');
        const html = document.documentElement;
        
        // Load saved theme on page load
        const savedTheme = localStorage.getItem('theme') || 'light';
        html.setAttribute('data-theme', savedTheme);
        if (savedTheme === 'dark') {
            document.body.classList.add('dark');
            html.classList.add('dark');
        }
        updateThemeIcon(savedTheme);
        
        themeToggle.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            // Update data-theme attribute
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Toggle dark class on both html and body
            html.classList.toggle('dark');
            document.body.classList.toggle('dark');
            
            updateThemeIcon(newTheme);
        });
        
        function updateThemeIcon(theme) {
            const icon = themeToggle.querySelector('i');
            icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }

        // Login Form Loading Effect
        const loginForm = document.getElementById('loginForm');
        const loadingOverlay = document.getElementById('loadingOverlay');
        
        loginForm.addEventListener('submit', function(e) {
            loadingOverlay.classList.remove('hidden');
            loadingOverlay.classList.add('flex');
        });
    </script>
</body>
</html>
