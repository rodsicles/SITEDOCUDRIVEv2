<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Register - Employee Dashboard</title>
    <link rel="icon" type="image/png" href="{{ asset('images/SPUP-final-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-portal-page" data-font-size="medium">

    <header class="login-portal-header">
        <div class="login-portal-header-inner">
            <div class="login-portal-brand">
                <img src="{{ asset('images/site-logo.png') }}" alt="SITE Logo" class="login-portal-brand-logo">
                <div class="login-portal-brand-text">
                    <h1 class="login-portal-brand-title">School of Information Technology and Engineering</h1>
                    <p class="login-portal-brand-subtitle">St. Paul University Philippines</p>
                </div>
            </div>
        </div>
    </header>

    <main class="login-portal-main">
        <div class="login-portal-card">
            <div class="login-portal-card-header">
                <h2 class="login-portal-card-title">Create Account</h2>
                <p class="login-portal-card-subtitle">Quick Registration</p>
            </div>

            <div class="login-portal-card-body">

                @if(session('success'))
                <div style="background: #065f46; color: #6ee7b7; padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem;">
                    {{ session('success') }}
                </div>
                @endif

                @if($errors->any())
                <div class="login-portal-error">
                    {{ $errors->first() }}
                </div>
                @endif

                <form action="{{ route('register.post') }}" method="POST">
                    @csrf

                    <div class="login-portal-field">
                        <label class="login-portal-label">Full Name</label>
                        <div class="login-portal-input-wrapper">
                            <i class="fas fa-id-card login-portal-input-icon"></i>
                            <input type="text" name="name" class="login-portal-input"
                                placeholder="Enter full name" required value="{{ old('name') }}">
                        </div>
                    </div>

                    <div class="login-portal-field">
                        <label class="login-portal-label">Username</label>
                        <div class="login-portal-input-wrapper">
                            <i class="fas fa-user login-portal-input-icon"></i>
                            <input type="text" name="username" class="login-portal-input"
                                placeholder="Enter username" required value="{{ old('username') }}">
                        </div>
                    </div>

                    <div class="login-portal-field">
                        <label class="login-portal-label">Email</label>
                        <div class="login-portal-input-wrapper">
                            <i class="fas fa-envelope login-portal-input-icon"></i>
                            <input type="email" name="email" class="login-portal-input"
                                placeholder="Enter email" required value="{{ old('email') }}">
                        </div>
                    </div>

                    <div class="login-portal-field">
                        <label class="login-portal-label">Password</label>
                        <div class="login-portal-input-wrapper">
                            <i class="fas fa-lock login-portal-input-icon"></i>
                            <input type="password" name="password" class="login-portal-input"
                                placeholder="Min 8 characters" required minlength="8">
                        </div>
                    </div>

                    <div class="login-portal-field">
                        <label class="login-portal-label">Role</label>
                        <div class="login-portal-input-wrapper">
                            <i class="fas fa-user-tag login-portal-input-icon"></i>
                            <select name="role_id" class="login-portal-input" required>
                                <option value="">Select Role</option>
                                <option value="1" {{ old('role_id') == '1' ? 'selected' : '' }}>Dean</option>
                                <option value="2" {{ old('role_id') == '2' ? 'selected' : '' }}>Program Coordinator</option>
                                <option value="3" {{ old('role_id') == '3' ? 'selected' : '' }}>Faculty Employee</option>
                                <option value="4" {{ old('role_id') == '4' ? 'selected' : '' }}>Secretary</option>
                            </select>
                        </div>
                    </div>

                    <div class="login-portal-field">
                        <label class="login-portal-label">Department</label>
                        <div class="login-portal-input-wrapper">
                            <i class="fas fa-building login-portal-input-icon"></i>
                            <select name="department" class="login-portal-input" required>
                                <option value="">Select Department</option>
                                <option value="Engineering" {{ old('department') == 'Engineering' ? 'selected' : '' }}>Engineering</option>
                                <option value="Information Technology" {{ old('department') == 'Information Technology' ? 'selected' : '' }}>Information Technology</option>
                            </select>
                        </div>
                    </div>

                    <div class="login-portal-submit-wrap">
                        <button type="submit" class="login-portal-submit">
                            CREATE ACCOUNT <i class="fas fa-user-plus"></i>
                        </button>
                    </div>
                </form>

                <div style="text-align: center; margin-top: 1rem;">
                    <a href="{{ route('login') }}" style="color: #6ee7b7; text-decoration: underline; font-size: 0.875rem;">
                        <i class="fas fa-arrow-left"></i> Back to Login
                    </a>
                </div>
            </div>
        </div>
    </main>

    <footer class="login-portal-footer">
        &copy; {{ date('Y') }} St. Paul University Philippines. All rights reserved.
    </footer>
</body>
</html>
