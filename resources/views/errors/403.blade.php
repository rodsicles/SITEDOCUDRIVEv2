<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access Restricted - SITE DocuDrive</title>
    <link rel="icon" type="image/png" href="{{ asset('images/SPUP-final-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="system-message-page">
    <main class="system-message-shell">
        <section class="system-message-panel" aria-labelledby="errorTitle">
            <div class="system-message-brand">
                <img src="{{ asset('images/site-logo.png') }}" alt="SITE logo">
                <span>SITE DocuDrive</span>
            </div>
            <p class="system-message-code">Error 403</p>
            <h1 id="errorTitle">Access restricted</h1>
            <p>You do not have permission to open this page. Return to a page your role can access, or sign out.</p>
            <div class="system-message-actions">
                <button type="button" class="btn btn-secondary" onclick="history.back()">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i> Go back
                </button>
                @auth
                @php
                    $dashboardUrl = match (true) {
                        auth()->user()->isFaculty() => route('faculty.dashboard'),
                        auth()->user()->isProgramCoordinator() => route('coordinator.dashboard'),
                        auth()->user()->isDeanOrSecretary() => route('dean.dashboard'),
                        default => url('/'),
                    };
                @endphp
                <a href="{{ $dashboardUrl }}" class="btn btn-primary">Dashboard</a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-sign-out-alt" aria-hidden="true"></i> Sign out
                    </button>
                </form>
                @endauth
            </div>
        </section>
    </main>
</body>
</html>
