<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Session expired - SITE DocuDrive</title>
    <link rel="icon" type="image/png" href="{{ asset('images/SPUP-final-logo.png') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="system-message-page">
    <main class="system-message-shell">
        <section class="system-message-panel" aria-labelledby="errorTitle">
            <div class="system-message-brand">
                <img src="{{ asset('images/site-logo.png') }}" alt="SITE logo">
                <span>SITE DocuDrive</span>
            </div>
            <p class="system-message-code">Error 419</p>
            <h1 id="errorTitle">Session expired</h1>
            <p>Your session timed out for security. Sign in again, then retry your last action.</p>
            <div class="system-message-actions">
                <a href="{{ route('login') }}" class="btn btn-primary">Sign in</a>
            </div>
        </section>
    </main>
</body>
</html>
