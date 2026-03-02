<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Login - Employee Dashboard with Data Analytics - SITE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page" data-font-size="medium">

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="login-loading-overlay">
        <div class="login-loading-box">
            <div class="login-loading-spinner"></div>
            <div class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">Logging in</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Please wait...</div>
        </div>
    </div>

    <!-- Gray header bar -->
    <div class="login-bar top-0"></div>

    <div class="login-card">
        <div class="login-header">
            <button id="themeToggle" type="button" class="login-theme-toggle">
                <i class="fas fa-moon"></i>
            </button>
            <img src="{{ asset('uploads/documents/site_logo-removebg-preview.png') }}" alt="SITE Logo" class="login-header-logo">
            <h1 class="login-header-title">Employee Dashboard with Data Analytics</h1>
            <p class="login-header-subtitle">School of Information Technology and Engineering (SITE)</p>
            <p class="login-header-hint">Sign in to continue</p>
        </div>
        <div class="login-body">
            @if($errors->any())
            <div class="login-error">
                {{ $errors->first() }}
            </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" id="loginForm">
                @csrf
                <div class="mb-4 sm:mb-6 login-field">
                    <label class="login-label">Username</label>
                    <div class="login-input-wrapper">
                        <i class="fas fa-user login-input-icon"></i>
                        <input type="text" name="username" class="login-input" placeholder="Enter your username" required autofocus>
                    </div>
                </div>

                <div class="mb-4 sm:mb-6 login-field">
                    <label class="login-label">Password</label>
                    <div class="login-input-wrapper">
                        <i class="fas fa-lock login-input-icon"></i>
                        <input type="password" id="password" name="password" class="login-input has-toggle" placeholder="Enter your password" required>
                        <button type="button" id="togglePassword" class="login-password-toggle">
                            <i class="fas fa-eye text-base" id="toggleIcon"></i>
                        </button>
                    </div>
                    <div id="capsLockWarning" class="login-capslock">
                        <i class="fas fa-exclamation-triangle"></i> Caps Lock is on
                    </div>
                </div>

                <!-- Remember me -->
                <div class="flex items-center gap-3 mb-5 sm:mb-6 login-field">
                    <label class="login-toggle">
                        <input type="checkbox" id="remember" name="remember">
                        <span class="login-toggle-slider"></span>
                    </label>
                    <label for="remember" class="login-toggle-label">Remember me</label>
                </div>

                <div class="login-field">
                    <button type="submit" class="login-submit">
                        SIGN IN <i class="fas fa-arrow-right login-submit-arrow"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer branding -->
    <div class="login-footer">
        &copy; {{ date('Y') }} St. Paul University Philippines
    </div>

    <!-- Green footer bar -->
    <div class="login-bar bottom-0"></div>

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

        // Show/Hide Password Toggle
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');

        togglePassword.addEventListener('click', () => {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            toggleIcon.className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
        });

        // Caps Lock Detection
        const capsWarning = document.getElementById('capsLockWarning');
        passwordInput.addEventListener('keyup', function(e) {
            if (e.getModifierState && e.getModifierState('CapsLock')) {
                capsWarning.classList.add('visible');
            } else {
                capsWarning.classList.remove('visible');
            }
        });
        passwordInput.addEventListener('blur', function() {
            capsWarning.classList.remove('visible');
        });
    </script>
</body>
</html>
