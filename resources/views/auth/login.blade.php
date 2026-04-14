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
                <h3 class="role-selection-title" style="margin-bottom: 4px;">SITE Employee Portal</h3>
                <p class="role-selection-subtitle" style="margin-bottom: 24px;">Select your role and enter your credentials to continue.</p>

                <!-- Error Message -->
                @if($errors->any())
                <div class="login-inline-error" style="margin-bottom: 16px;">
                    {{ $errors->first() }}
                </div>
                @endif

                <!-- Role Cards -->
                <div style="margin-bottom: 24px;">
                    <!-- Dean -->
                    <div class="login-role-card" id="card-dean" onclick="selectRole('dean')" style="display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; border: 1px solid #e5e7eb; margin-bottom: 8px; cursor: pointer; background: transparent;">
                        <span style="font-weight: 600; font-size: 0.95rem; color: #1f2937;">Dean</span>
                        <i class="fas fa-arrow-right" style="color: #9ca3af; font-size: 0.8rem;"></i>
                    </div>

                    <!-- Coordinator with submenu -->
                    <div style="margin-bottom: 8px;">
                        <div class="login-role-card" id="card-coordinator" onclick="toggleCoordinatorMenu()" style="display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; border: 1px solid #e5e7eb; cursor: pointer; background: transparent;">
                            <span style="font-weight: 600; font-size: 0.95rem; color: #1f2937;">Coordinator</span>
                            <i class="fas fa-chevron-down" id="coordChevron" style="color: #9ca3af; font-size: 0.8rem;"></i>
                        </div>
                        <div id="coordinatorSubmenu" style="display: none; border: 1px solid #e5e7eb; border-top: none; background: #f9fafb; padding: 8px 0;">
                            <div class="login-role-card" id="card-coordinator-engineering" onclick="selectRole('coordinator', 'Engineering')" style="display: flex; justify-content: space-between; align-items: center; padding: 10px 20px; cursor: pointer; background: transparent; border: none;">
                                <span style="font-size: 0.9rem; color: #028a0f; font-weight: 500;">Coordinator of Engineering</span>
                                <i class="fas fa-arrow-right" style="color: #028a0f; font-size: 0.75rem;"></i>
                            </div>
                            <div class="login-role-card" id="card-coordinator-it" onclick="selectRole('coordinator', 'Information Technology')" style="display: flex; justify-content: space-between; align-items: center; padding: 10px 20px; cursor: pointer; background: transparent; border: none; border-top: 1px solid #e5e7eb;">
                                <span style="font-size: 0.9rem; color: #028a0f; font-weight: 500;">Coordinator of Information Technology</span>
                                <i class="fas fa-arrow-right" style="color: #028a0f; font-size: 0.75rem;"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Faculty with submenu -->
                    <div style="margin-bottom: 8px;">
                        <div class="login-role-card" id="card-faculty" onclick="toggleFacultyMenu()" style="display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; border: 1px solid #e5e7eb; cursor: pointer; background: transparent;">
                            <span style="font-weight: 600; font-size: 0.95rem; color: #1f2937;">Faculty</span>
                            <i class="fas fa-chevron-down" id="facultyChevron" style="color: #9ca3af; font-size: 0.8rem;"></i>
                        </div>
                        <div id="facultySubmenu" style="display: none; border: 1px solid #e5e7eb; border-top: none; background: #f9fafb; padding: 8px 0;">
                            <div class="login-role-card" id="card-faculty-engineering" onclick="selectRole('faculty', 'Engineering')" style="display: flex; justify-content: space-between; align-items: center; padding: 10px 20px; cursor: pointer; background: transparent; border: none;">
                                <span style="font-size: 0.9rem; color: #028a0f; font-weight: 500;">Faculty of Engineering</span>
                                <i class="fas fa-arrow-right" style="color: #028a0f; font-size: 0.75rem;"></i>
                            </div>
                            <div class="login-role-card" id="card-faculty-it" onclick="selectRole('faculty', 'Information Technology')" style="display: flex; justify-content: space-between; align-items: center; padding: 10px 20px; cursor: pointer; background: transparent; border: none; border-top: 1px solid #e5e7eb;">
                                <span style="font-size: 0.9rem; color: #028a0f; font-weight: 500;">Faculty of Information Technology</span>
                                <i class="fas fa-arrow-right" style="color: #028a0f; font-size: 0.75rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>

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

                    <input type="hidden" name="role" id="selectedRole" value="{{ old('role', 'dean') }}">
                    <input type="hidden" name="department" id="selectedDepartment" value="{{ old('department', '') }}">

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

        // Role Selection
        function selectRole(role, department) {
            // Clear all card highlights
            document.querySelectorAll('.login-role-card').forEach(card => {
                card.style.borderColor = '#e5e7eb';
                card.style.background = 'transparent';
                card.style.borderLeftWidth = '1px';
            });

            // Highlight selected card
            let activeIds = [];
            if (role === 'coordinator' && department === 'Engineering') {
                activeIds = ['card-coordinator', 'card-coordinator-engineering'];
            } else if (role === 'coordinator' && department === 'Information Technology') {
                activeIds = ['card-coordinator', 'card-coordinator-it'];
            } else if (role === 'faculty' && department === 'Engineering') {
                activeIds = ['card-faculty', 'card-faculty-engineering'];
            } else if (role === 'faculty' && department === 'Information Technology') {
                activeIds = ['card-faculty', 'card-faculty-it'];
            } else {
                activeIds = ['card-' + role];
            }

            activeIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.style.borderColor = '#028a0f';
                    el.style.background = 'rgba(2, 138, 15, 0.08)';
                    // Only add thick left border to parent cards
                    if (id === 'card-dean' || id === 'card-coordinator' || id === 'card-faculty') {
                        el.style.borderLeftWidth = '4px';
                    }
                }
            });

            document.getElementById('selectedRole').value = role;
            document.getElementById('selectedDepartment').value = department || '';
        }

        function toggleCoordinatorMenu() {
            const submenu = document.getElementById('coordinatorSubmenu');
            const chevron = document.getElementById('coordChevron');
            const isOpen = submenu.style.display !== 'none';

            submenu.style.display = isOpen ? 'none' : 'block';
            chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';

            // If closing and coordinator was selected, deselect
            if (isOpen && document.getElementById('selectedRole').value === 'coordinator') {
                document.getElementById('selectedRole').value = '';
                document.getElementById('selectedDepartment').value = '';
                document.querySelectorAll('.login-role-card').forEach(card => {
                    card.style.borderColor = '#e5e7eb';
                    card.style.background = 'transparent';
                    card.style.borderLeftWidth = '1px';
                });
            }
        }

        function toggleFacultyMenu() {
            const submenu = document.getElementById('facultySubmenu');
            const chevron = document.getElementById('facultyChevron');
            const isOpen = submenu.style.display !== 'none';

            submenu.style.display = isOpen ? 'none' : 'block';
            chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';

            // If closing and faculty was selected, deselect
            if (isOpen && document.getElementById('selectedRole').value === 'faculty') {
                document.getElementById('selectedRole').value = '';
                document.getElementById('selectedDepartment').value = '';
                document.querySelectorAll('.login-role-card').forEach(card => {
                    card.style.borderColor = '#e5e7eb';
                    card.style.background = 'transparent';
                    card.style.borderLeftWidth = '1px';
                });
            }
        }

        // Default: highlight on load based on old values
        @if(old('role') === 'coordinator' && old('department'))
            selectRole('coordinator', '{{ old('department') }}');
        @elseif(old('role') === 'faculty' && old('department'))
            selectRole('faculty', '{{ old('department') }}');
        @else
            selectRole('{{ old('role', 'dean') }}');
        @endif

        // If old role was coordinator, open submenu
        @if(old('role') === 'coordinator')
            document.getElementById('coordinatorSubmenu').style.display = 'block';
            document.getElementById('coordChevron').style.transform = 'rotate(180deg)';
        @endif

        // If old role was faculty, open submenu
        @if(old('role') === 'faculty')
            document.getElementById('facultySubmenu').style.display = 'block';
            document.getElementById('facultyChevron').style.transform = 'rotate(180deg)';
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
                // Validate a role is selected
                const role = document.getElementById('selectedRole').value;
                if (!role) {
                    e.preventDefault();
                    alert('Please select a role first.');
                    return;
                }
                // Validate department is selected for coordinators and faculty
                if (role === 'coordinator' || role === 'faculty') {
                    const dept = document.getElementById('selectedDepartment').value;
                    if (!dept) {
                        e.preventDefault();
                        alert('Please select your department (Engineering or Information Technology).');
                        return;
                    }
                }
                loadingOverlay.classList.remove('hidden');
                loadingOverlay.classList.add('flex');
            });
        }
    </script>
</body>
</html>
