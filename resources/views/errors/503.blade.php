<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unavailable - SITE DocuDrive</title>
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
            <p class="system-message-code">Error 503</p>
            <h1 id="errorTitle">Temporarily unavailable</h1>
            <p>SITE DocuDrive is undergoing maintenance or is under heavy load. Please try again shortly.</p>
            <div class="system-message-actions">
                <button type="button" class="btn btn-secondary" onclick="location.reload()">Try again</button>
            </div>
        </section>
    </main>
</body>
</html>
