<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Server error - SITE DocuDrive</title>
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
            <p class="system-message-code">Error 500</p>
            <h1 id="errorTitle">Something went wrong</h1>
            <p>We could not complete your request. Try again in a moment. If the problem continues, contact support.</p>
            <div class="system-message-actions">
                <button type="button" class="btn btn-secondary" onclick="location.reload()">Try again</button>
                <a href="{{ url('/') }}" class="btn btn-primary">Home</a>
            </div>
        </section>
    </main>
</body>
</html>
