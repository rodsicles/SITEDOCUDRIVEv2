<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Login - Employee Dashboard with Data Analytics - SITE</title>
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
                    <img src="{{ asset('images/site-logo.png') }}" alt="St. Paul University Philippines Logo" class="role-selection-logo">
                </div>
                <h2 class="role-selection-university-name">St. Paul University Philippines</h2>
                <p class="role-selection-university-location">TUGUEGARAO CITY</p>
            </div>

            <div class="role-selection-tagline">
                <h1 class="role-selection-main-title">Employee Dashboard with <span class="role-selection-accent">Data Analytics</span></h1>
                <p class="role-selection-description">Manage documents, reports and credentials of SITE employees</p>
            </div>

            <div class="role-selection-footer-year">
                A.Y. 2025-2026
            </div>
        </div>

        <!-- Right Section: Role Selection + Login Form (same position) -->
        <div class="role-selection-right">
            <div class="login-right-wrapper">

                <!-- PANEL 1: Role Selection -->
                <div id="roleSelectionPanel" class="login-panel-active">
                    <h3 class="role-selection-title">Select your role</h3>
                    <p class="role-selection-subtitle">Choose how you want to log into the system.</p>

                    <div class="role-selection-cards">
                        <!-- Dean Role -->
                        <button onclick="showLoginForm('dean')" class="role-card" style="width: 100%; text-align: left; background: none; border: none; cursor: pointer; padding: 0;">
                            <div class="role-card-content">
                                <div class="role-card-title">Dean</div>
                            </div>
                            <div class="role-card-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                        </button>

                        <!-- Coordinator Role with Submenu -->
                        <div class="role-card-container">
                            <button onclick="toggleCoordinatorMenu()" class="role-card" style="width: 100%; text-align: left; background: none; border: none; cursor: pointer; padding: 0;">
                                <div class="role-card-content">
                                    <div class="role-card-title">Coordinator</div>
                                </div>
                                <div class="role-card-arrow">
                                    <i class="fas fa-chevron-down" id="coordinatorToggleIcon"></i>
                                </div>
                            </button>
                            <div class="coordinator-submenu" id="coordinatorSubmenu">
                                <button onclick="showLoginForm('coordinator')" class="coordinator-option" style="background: none; border: none; cursor: pointer; width: 100%; text-align: left; padding: 0;">
                                    <span>IT & Engineering Coordinator</span>
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Faculty Role -->
                        <button onclick="showLoginForm('faculty')" class="role-card" style="width: 100%; text-align: left; background: none; border: none; cursor: pointer; padding: 0;">
                            <div class="role-card-content">
                                <div class="role-card-title">Faculty</div>
                            </div>
                            <div class="role-card-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                        </button>
                    </div>

                    <div class="role-selection-footer-text">Cantas Christi Urget Nos</div>
                </div>

                <!-- PANEL 2: Login Form (hidden by default) -->
                <div id="loginFormPanel" class="login-panel-hidden">
                    <div class="login-inline-controls">
                        <button onclick="backToRoleSelection()" type="button" class="login-inline-back-btn" title="Back to role selection">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                        <button id="themeToggle" type="button" class="login-inline-theme-toggle">
                            <i class="fas fa-moon"></i>
                        </button>
                    </div>

                    <h3 class="login-inline-title">Sign In</h3>
                    <p class="login-inline-role-label" id="roleLabel">Dean</p>

                    @if($errors->any())
                    <div class="login-inline-error">
                        {{ $errors->first() }}
                    </div>
                    @endif

                    <form action="{{ route('login.post') }}" method="POST" id="loginForm">
                        @csrf

                        <div class="login-inline-field">
                            <label class="login-inline-label">Username</label>
                            <div class="login-inline-input-wrapper">
                                <i class="fas fa-user login-inline-input-icon"></i>
                                <input type="text" name="username" class="login-inline-input" placeholder="Enter your username" required>
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

                        <input type="hidden" name="role" id="selectedRole" value="{{ old('role', 'dean') }}">

                        <button type="submit" class="login-inline-submit">
                            SIGN IN <i class="fas fa-arrow-right"></i>
                        </button>
                    </form>

                    <div class="role-selection-footer-text login-inline-footer">Cantas Christi Urget Nos</div>
                </div>

            </div>
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

            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);

            html.classList.toggle('dark');
            document.body.classList.toggle('dark');

            updateThemeIcon(newTheme);
        });

        function updateThemeIcon(theme) {
            if (themeToggle) {
                const icon = themeToggle.querySelector('i');
                icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        }

        // Toggle Coordinator Menu
        function toggleCoordinatorMenu() {
            const submenu = document.getElementById('coordinatorSubmenu');
            const icon = document.getElementById('coordinatorToggleIcon');

            submenu.classList.toggle('open');
            icon.style.transform = submenu.classList.contains('open') ? 'rotate(180deg)' : 'rotate(0deg)';
        }

        // Show Login Form (swap panels in place)
        function showLoginForm(role) {
            document.getElementById('roleSelectionPanel').classList.remove('login-panel-active');
            document.getElementById('roleSelectionPanel').classList.add('login-panel-hidden');
            document.getElementById('loginFormPanel').classList.remove('login-panel-hidden');
            document.getElementById('loginFormPanel').classList.add('login-panel-active');

            const roleNames = {
                'dean': 'Dean',
                'coordinator': 'Coordinator',
                'faculty': 'Faculty'
            };
            document.getElementById('roleLabel').textContent = roleNames[role];
            document.getElementById('selectedRole').value = role;

            // Focus username input
            setTimeout(() => {
                const usernameInput = document.querySelector('.login-inline-input');
                if (usernameInput) usernameInput.focus();
            }, 50);
        }

        // Back to Role Selection
        function backToRoleSelection() {
            document.getElementById('loginFormPanel').classList.remove('login-panel-active');
            document.getElementById('loginFormPanel').classList.add('login-panel-hidden');
            document.getElementById('roleSelectionPanel').classList.remove('login-panel-hidden');
            document.getElementById('roleSelectionPanel').classList.add('login-panel-active');
        }

        // Auto-show login panel on validation errors (after failed login)
        @if($errors->any())
            showLoginForm('{{ old('role', 'dean') }}');
        @endif

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
