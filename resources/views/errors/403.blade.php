<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>403 - Forbidden</title>
    <style>
        body {
            background: #1a2332;
            color: #e2e8f0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .box {
            text-align: center;
            max-width: 480px;
            padding: 0 20px;
        }
        .code {
            display: inline-block;
            font-weight: 700;
            font-size: 1.1rem;
            border-right: 1px solid #475569;
            padding-right: 16px;
            margin-right: 16px;
            vertical-align: top;
        }
        .message {
            display: inline-block;
            text-align: left;
            font-size: 0.95rem;
            letter-spacing: 0.02em;
            max-width: 320px;
        }
        form {
            margin-top: 28px;
        }
        button {
            background: #028a0f;
            color: #fff;
            border: none;
            padding: 10px 24px;
            font-size: 0.9rem;
            cursor: pointer;
            border-radius: 4px;
        }
        button:hover {
            background: #026a0c;
        }
    </style>
</head>
<body>
    <div class="box">
        <span class="code">403</span>
        <span class="message">{{ $exception->getMessage() ?: 'Forbidden' }}</span>

        @auth
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit">Log Out</button>
        </form>
        @endauth
    </div>
</body>
</html>
