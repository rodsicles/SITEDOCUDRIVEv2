<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Login - SITE DocuDrive - SITE</title>
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
        <section class="login-auth-shell" aria-labelledby="loginTitle">
            <aside class="login-auth-identity" aria-label="SITE DocuDrive information">
                <div class="login-auth-identity__brand">
                    <img src="{{ asset('images/site-logo.png') }}" alt="" class="login-auth-identity__logo" aria-hidden="true">
                    <span class="login-auth-identity__eyebrow">Employee portal</span>
                </div>
                <div class="login-auth-identity__content">
                    <h2>SITE DocuDrive</h2>
                    <p class="login-auth-identity__subtitle">Employee Document Management System</p>
                    <p class="login-auth-identity__description">
                        Secure access to institutional documents, submissions, reviews, and academic records.
                    </p>
                </div>
                <div class="login-auth-identity__security">
                    <i class="fas fa-shield-alt" aria-hidden="true"></i>
                    <span>Authorized employees only</span>
                </div>
            </aside>

            <div class="login-auth-form-panel">
                <div class="login-auth-mobile-brand" aria-hidden="true">
                    <img src="{{ asset('images/site-logo.png') }}" alt="">
                    <span>SITE DocuDrive</span>
                </div>

                <div class="login-auth-heading">
                    <p class="login-auth-heading__eyebrow">Employee access</p>
                    <h2 id="loginTitle">Sign in</h2>
                    <p>Use your SITE employee account to continue.</p>
                </div>

                @if(session('success'))
                <div class="login-auth-message login-auth-message--success" role="status">
                    <i class="fas fa-check-circle" aria-hidden="true"></i>
                    <span>{{ session('success') }}</span>
                </div>
                @endif

                @if($errors->any())
                <div class="login-auth-message login-auth-message--error" role="alert">
                    <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
                @endif

                <form action="{{ route('login.post') }}" method="POST" id="loginForm" class="login-auth-form">
                    @csrf

                    <div class="login-portal-field">
                        <label class="login-portal-label" for="username">Username</label>
                        <div class="login-portal-input-wrapper">
                            <i class="fas fa-user login-portal-input-icon" aria-hidden="true"></i>
                            <input type="text" id="username" name="username" class="login-portal-input"
                                placeholder="Enter username" required autocomplete="username" value="{{ old('username') }}">
                        </div>
                    </div>

                    <div class="login-portal-field">
                        <label class="login-portal-label" for="password">Password</label>
                        <div class="login-portal-input-wrapper">
                            <i class="fas fa-lock login-portal-input-icon" aria-hidden="true"></i>
                            <input type="password" id="password" name="password"
                                class="login-portal-input has-toggle" placeholder="Enter password" required autocomplete="current-password">
                            <button type="button" id="togglePassword" class="login-portal-pw-toggle" aria-label="Show password" aria-pressed="false">
                                <i class="fas fa-eye" id="toggleIcon" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div id="capsLockWarning" class="login-portal-capslock" role="status">
                            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i> Caps Lock is on
                        </div>
                    </div>

                    <div class="login-portal-row">
                        <label class="login-portal-remember">
                            <input type="checkbox" id="remember" name="remember" checked>
                            <span>Remember me</span>
                        </label>
                        <a href="{{ route('password.forgot.show') }}" class="login-portal-forgot">Forgot password?</a>
                    </div>

                    <button type="submit" class="login-portal-submit">
                        <span>Sign in</span>
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </button>
                </form>

                <p class="login-auth-help">
                    <i class="fas fa-circle-info" aria-hidden="true"></i>
                    For account access concerns, contact your system administrator.
                </p>
            </div>
        </section>
    </main>

    {{-- ───────────────── FOOTER ───────────────── --}}
    <footer class="login-portal-footer">
        <span>&copy; {{ date('Y') }} St. Paul University Philippines</span>
        <span class="login-portal-footer__divider" aria-hidden="true"></span>
        <span>A.Y. 2025-2026</span>
        <span class="login-portal-footer__divider" aria-hidden="true"></span>
        <em>Caritas Christi Urget Nos</em>
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
                togglePassword.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
                togglePassword.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
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
