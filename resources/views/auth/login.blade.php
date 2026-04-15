<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Login - Employee Dashboard with Data Analytics - SITE</title>
    <link rel="icon" type="image/png" href="{{ asset('images/SPUP-final-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="role-selection-page" data-font-size="medium">

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="login-loading-overlay">
        <div class="login-loading-box">
            <div class="login-loading-spinner"></div>
            <div class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">Logging in</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Please wait...</div>
        </div>
    </div>

    <!-- MAIN LAYOUT -->
    <div class="role-selection-container">
        <!-- Left Section: Branding -->
        <div class="role-selection-left">
            <div class="role-selection-branding">
                <div class="role-selection-logo-wrapper">
                    <img src="{{ asset('images/SPUP-final-logo.png') }}" alt="St. Paul University Philippines Logo" class="role-selection-logo">
                </div>
                <div class="role-selection-branding-text">
                    <h2 class="role-selection-university-name">St. Paul University Philippines</h2>
                    <p class="role-selection-university-location">Tuguegarao City</p>
                </div>
            </div>

            <div class="role-selection-tagline">
                <div class="role-selection-main-title-wrapper">
                    <img src="{{ asset('images/site-logo.png') }}" alt="Logo" class="role-selection-title-logo">
                    <h1 class="role-selection-main-title">Employee Dashboard with <span class="role-selection-accent">Data Analytics</span></h1>
                </div>
                <p class="role-selection-description">Manage documents, reports and credentials of SITE employees</p>

                <div class="role-selection-features">
                    <div class="role-selection-feature-item">
                        <i class="fas fa-file-alt role-selection-feature-icon"></i>
                        <span>Document Management & Tracking</span>
                    </div>
                    <div class="role-selection-feature-item">
                        <i class="fas fa-chart-bar role-selection-feature-icon"></i>
                        <span>Analytics & Report Generation</span>
                    </div>
                    <div class="role-selection-feature-item">
                        <i class="fas fa-id-card role-selection-feature-icon"></i>
                        <span>Employee Credentials Management</span>
                    </div>
                    <div class="role-selection-feature-item">
                        <i class="fas fa-shield-alt role-selection-feature-icon"></i>
                        <span>Role-Based Access Control</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Section: Single Login Form -->
        <div class="role-selection-right">
            <div class="login-right-wrapper">

                <!-- Theme Toggle -->
                <div style="display: flex; justify-content: flex-end; margin-bottom: 12px;">
                    <button id="themeToggle" type="button" class="login-inline-theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>

                <!-- Header -->
                <p class="role-selection-subtitle" style="margin-bottom: 24px;">Enter your credentials to continue.</p>

                <!-- Error Message -->
                @if($errors->any())
                <div class="login-inline-error" style="margin-bottom: 16px;">
                    {{ $errors->first() }}
                </div>
                @endif

                <!-- Login Form -->
                <form action="{{ route('login.post') }}" method="POST" id="loginForm">
                    @csrf

                    <div class="login-inline-field">
                        <label class="login-inline-label">Username</label>
                        <div class="login-inline-input-wrapper">
                            <i class="fas fa-user login-inline-input-icon"></i>
                            <input type="text" name="username" class="login-inline-input" placeholder="Enter your username" required value="{{ old('username') }}">
                        </div>
                    </div>

                    <div class="login-inline-field">
                        <label class="login-inline-label">Password</label>
                        <div class="login-inline-input-wrapper">
                            <i class="fas fa-lock login-inline-input-icon"></i>
                            <input type="password" id="password" name="password" class="login-inline-input has-toggle" placeholder="Enter your password" required>
                            <button type="button" id="togglePassword" class="login-inline-password-toggle">
                                <i class="fas fa-eye text-base" id="toggleIcon"></i>
                            </button>
                        </div>
                        <div id="capsLockWarning" class="login-inline-capslock">
                            <i class="fas fa-exclamation-triangle"></i> Caps Lock is on
                        </div>
                    </div>

                    <!-- Remember me -->
                    <div class="login-inline-remember">
                        <label class="login-inline-toggle">
                            <input type="checkbox" id="remember" name="remember">
                            <span class="login-inline-toggle-slider"></span>
                        </label>
                        <label for="remember" class="login-inline-toggle-label">Remember me</label>
                    </div>

                    <button type="submit" class="login-inline-submit">
                        SIGN IN <i class="fas fa-arrow-right"></i>
                    </button>
                </form>

            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="login-page-footer">
        <div class="login-footer-content">
            <span>A.Y. 2025-2026</span>
            <span class="login-footer-separator">|</span>
            <span>Caritas Christi Urget Nos</span>
        </div>
    </footer>

    <script>
        // Dark Mode Toggle
        const themeToggle = document.getElementById('themeToggle');
        const html = document.documentElement;

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
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            html.classList.toggle('dark');
            document.body.classList.toggle('dark');
            updateThemeIcon(newTheme);
        });

        function updateThemeIcon(theme) {
            const icon = themeToggle.querySelector('i');
            icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }

        // Show/Hide Password Toggle
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');

        if (togglePassword) {
            togglePassword.addEventListener('click', () => {
                const isPassword = passwordInput.type === 'password';
                passwordInput.type = isPassword ? 'text' : 'password';
                toggleIcon.className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
            });
        }

        // Caps Lock Detection
        const capsWarning = document.getElementById('capsLockWarning');
        if (passwordInput) {
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
        }

        // Login Form Loading Effect
        const loginForm = document.getElementById('loginForm');
        const loadingOverlay = document.getElementById('loadingOverlay');

        if (loginForm) {
            loginForm.addEventListener('submit', function(e) {
                loadingOverlay.classList.remove('hidden');
                loadingOverlay.classList.add('flex');
            });
        }
    </script>
</body>
</html>
