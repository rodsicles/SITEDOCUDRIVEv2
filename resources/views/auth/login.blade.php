<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Login - Employee Dashboard with Data Analytics - SITE</title>
    <link rel="icon" type="image/png" href="{{ asset('images/SPUP-final-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-portal-page" data-font-size="medium">

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="login-loading-overlay">
        <div class="login-loading-box">
            <div class="login-loading-spinner"></div>
            <div class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">Logging in</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Please wait...</div>
        </div>
    </div>

    {{-- ───────────────── TOP HEADER BAR ───────────────── --}}
    <header class="login-portal-header">
        <div class="login-portal-header-inner">
            <div class="login-portal-brand">
                <img src="{{ asset('images/site-logo.png') }}" alt="SITE Logo" class="login-portal-brand-logo">
                <div class="login-portal-brand-text">
                    <h1 class="login-portal-brand-title">School of Information Technology and Engineering</h1>
                    <p class="login-portal-brand-subtitle">St. Paul University Philippines</p>
                </div>
            </div>

            <div class="login-portal-header-meta">
                <button id="themeToggle" type="button" class="login-portal-theme-toggle" aria-label="Toggle theme">
                    <i class="fas fa-moon"></i>
                </button>
                <div class="login-portal-secure">
                    <i class="fas fa-shield-alt"></i>
                    <span>Secure Login Portal</span>
                </div>
            </div>
        </div>
    </header>

    {{-- ───────────────── MAIN CARD ───────────────── --}}
    <main class="login-portal-main">
        <div class="login-portal-card">
            <div class="login-portal-card-header">
                <h2 class="login-portal-card-title">Employee Dashboard</h2>
                <p class="login-portal-card-subtitle">with Data Analytics</p>
            </div>

            <div class="login-portal-card-body">
                <div class="login-portal-welcome">
                    <h3 class="login-portal-welcome-title">Welcome Back</h3>
                    <p class="login-portal-welcome-sub">Sign in to your account</p>
                </div>

                {{-- Error Message --}}
                @if($errors->any())
                <div class="login-portal-error">
                    {{ $errors->first() }}
                </div>
                @endif

                <form action="{{ route('login.post') }}" method="POST" id="loginForm">
                    @csrf

                    <div class="login-portal-field">
                        <label class="login-portal-label">Username</label>
                        <div class="login-portal-input-wrapper">
                            <i class="fas fa-user login-portal-input-icon"></i>
                            <input type="text" name="username" class="login-portal-input"
                                placeholder="Enter username" required value="{{ old('username') }}">
                        </div>
                    </div>

                    <div class="login-portal-field">
                        <label class="login-portal-label">Password</label>
                        <div class="login-portal-input-wrapper">
                            <i class="fas fa-lock login-portal-input-icon"></i>
                            <input type="password" id="password" name="password"
                                class="login-portal-input has-toggle" placeholder="Enter password" required>
                            <button type="button" id="togglePassword" class="login-portal-pw-toggle" aria-label="Toggle password visibility">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                        <div id="capsLockWarning" class="login-portal-capslock">
                            <i class="fas fa-exclamation-triangle"></i> Caps Lock is on
                        </div>
                    </div>

                    <div class="login-portal-row">
                        <label class="login-portal-remember">
                            <input type="checkbox" id="remember" name="remember" checked>
                            <span>Remember me</span>
                        </label>
                        <a href="#" class="login-portal-forgot">Forgot password?</a>
                    </div>

                    <div class="login-portal-submit-wrap">
                        <button type="submit" class="login-portal-submit">
                            SIGN IN <i class="fas fa-sign-in-alt"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <p class="login-portal-ay">A.Y. 2025-2026 &nbsp;|&nbsp; Caritas Christi Urget Nos</p>
    </main>

    {{-- ───────────────── FOOTER ───────────────── --}}
    <footer class="login-portal-footer">
        &copy; {{ date('Y') }} St. Paul University Philippines. All rights reserved.
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

        // Show/Hide Password
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

        // Caps Lock detection
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

        // Loading overlay on submit
        const loginForm = document.getElementById('loginForm');
        const loadingOverlay = document.getElementById('loadingOverlay');
        if (loginForm) {
            loginForm.addEventListener('submit', function() {
                loadingOverlay.classList.remove('hidden');
                loadingOverlay.classList.add('flex');
            });
        }
    </script>
</body>
</html>
