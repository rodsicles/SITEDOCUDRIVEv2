<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Security Required - SITE DocuDrive</title>
    <link rel="icon" type="image/png" href="{{ asset('images/SPUP-final-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="security-change-page">
    <header class="security-change-header">
        <div class="security-change-brand">
            <img src="{{ asset('images/site-logo.png') }}" alt="SITE logo">
            <div><strong>SITE DocuDrive</strong><span>Secure account access</span></div>
        </div>
    </header>
    <main class="security-change-main">
        <section class="security-change-panel" aria-labelledby="forceChangeTitle">
            <div class="security-change-intro">
                <span class="security-change-icon"><i class="fas fa-shield-halved" aria-hidden="true"></i></span>
                <div>
                    <p class="security-change-eyebrow">Required security step</p>
                    <h1 id="forceChangeTitle">Choose a new password</h1>
                    <p>Replace the temporary password before continuing to the system.</p>
                </div>
            </div>
            @if ($errors->any())
                <div class="security-change-errors" role="alert">
                    @foreach ($errors->all() as $error)
                        <div><i class="fas fa-circle-exclamation" aria-hidden="true"></i> {{ $error }}</div>
                    @endforeach
                </div>
            @endif
            <form method="POST" action="{{ route('password.force-change.update') }}" class="security-change-form" autocomplete="off">
                @csrf
                <div class="security-change-field">
                    <label for="current_password">Temporary password</label>
                    <input id="current_password" name="current_password" type="password" required autofocus autocomplete="current-password">
                </div>
                <div class="security-change-field">
                    <label for="new_password">New password</label>
                    <input id="new_password" name="new_password" type="password" required minlength="8" maxlength="40" autocomplete="new-password">
                    <p>Use at least 8 characters and do not reuse the temporary password.</p>
                </div>
                <div class="security-change-field">
                    <label for="new_password_confirmation">Confirm new password</label>
                    <input id="new_password_confirmation" name="new_password_confirmation" type="password" required minlength="8" maxlength="40" autocomplete="new-password">
                </div>
                <button type="submit" class="security-change-submit">Update password <i class="fas fa-arrow-right" aria-hidden="true"></i></button>
            </form>
            <div class="security-change-footer">
                <p><i class="fas fa-circle-info" aria-hidden="true"></i> This security change is recorded in the audit trail.</p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Sign out instead</button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
