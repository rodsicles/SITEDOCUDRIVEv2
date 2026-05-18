<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Required &mdash; Change Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; height: 100%; }
        body {
            font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            color: #1f2937;
            background:
                radial-gradient(circle at 12% 18%, rgba(2,138,15,0.20) 0, transparent 32%),
                radial-gradient(circle at 88% 76%, rgba(2,138,15,0.15) 0, transparent 28%),
                linear-gradient(135deg, #f1f5f4 0%, #d8dfdb 100%);
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }

        /* Decorative cards behind blur to communicate "system locked" */
        .bg-cards {
            position: absolute;
            inset: 0;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            padding: 40px;
            opacity: 0.55;
            pointer-events: none;
            user-select: none;
        }
        .bg-cards > div {
            background: rgba(255,255,255,0.7);
            border-radius: 6px;
            height: 130px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        }

        /* Locked overlay */
        .overlay {
            position: fixed;
            inset: 0;
            z-index: 9000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            background-color: rgba(0,0,0,0.45);
            backdrop-filter: blur(10px) saturate(140%);
            -webkit-backdrop-filter: blur(10px) saturate(140%);
        }

        /* Modal card */
        .modal {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35);
            overflow: hidden;
        }

        .modal-header {
            background: #028a0f;
            color: #ffffff;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .modal-header i { font-size: 20px; }
        .modal-header h1 {
            margin: 0;
            font-size: 15px;
            font-weight: 600;
            line-height: 1.2;
        }
        .modal-header p {
            margin: 2px 0 0;
            font-size: 11px;
            color: #d4f0d6;
            line-height: 1.2;
        }

        .modal-intro {
            padding: 12px 20px;
            font-size: 12px;
            color: #4b5563;
            border-bottom: 1px solid #f3f4f6;
        }

        .errors {
            margin: 12px 20px 0;
            padding: 8px 10px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            font-size: 12px;
            border-radius: 4px;
        }
        .errors div + div { margin-top: 2px; }

        .modal-form {
            padding: 18px 20px 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .field label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 4px;
        }
        .field input[type="password"] {
            width: 100%;
            padding: 9px 11px;
            font-size: 13px;
            color: #111827;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 5px;
            outline: none;
            transition: border-color 0.15s;
        }
        .field input[type="password"]:focus {
            border-color: #028a0f;
            box-shadow: 0 0 0 3px rgba(2,138,15,0.12);
        }
        .field-hint {
            font-size: 11px;
            color: #6b7280;
            margin: 4px 0 0;
        }

        .submit-btn {
            margin-top: 4px;
            width: 100%;
            background: #028a0f;
            color: #ffffff;
            font-size: 13px;
            font-weight: 600;
            padding: 10px 14px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.15s;
        }
        .submit-btn:hover { background: #026e0c; }

        .logout-form {
            padding: 0 20px 16px;
            margin: 0;
        }
        .logout-btn {
            width: 100%;
            background: transparent;
            border: none;
            color: #6b7280;
            font-size: 11px;
            cursor: pointer;
            padding: 6px;
            transition: color 0.15s;
        }
        .logout-btn:hover { color: #b91c1c; }

        .modal-footer {
            padding: 12px 20px;
            background: #f9fafb;
            border-top: 1px solid #f3f4f6;
            font-size: 11px;
            color: #6b7280;
            display: flex;
            gap: 8px;
            line-height: 1.45;
        }
        .modal-footer i { margin-top: 2px; flex-shrink: 0; }

        @media (max-width: 480px) {
            .modal { max-width: 100%; }
            .bg-cards { grid-template-columns: repeat(2, 1fr); padding: 20px; gap: 14px; }
        }
    </style>
</head>
<body>

    <div class="bg-cards" aria-hidden="true">
        @for ($i = 0; $i < 9; $i++)
            <div></div>
        @endfor
    </div>

    <div class="overlay">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="forceChangeTitle">

            <div class="modal-header">
                <i class="fas fa-shield-halved"></i>
                <div>
                    <h1 id="forceChangeTitle">Security Required</h1>
                    <p>First-time login &mdash; change your temporary password</p>
                </div>
            </div>

            <div class="modal-intro">
                For your account's protection, you must change the temporary password issued by the administrator before you can use the system.
            </div>

            @if ($errors->any())
                <div class="errors">
                    @foreach ($errors->all() as $error)
                        <div><i class="fas fa-circle-exclamation"></i> {{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.force-change.update') }}" class="modal-form" autocomplete="off">
                @csrf

                <div class="field">
                    <label for="current_password">Current (Temporary) Password</label>
                    <input id="current_password" name="current_password" type="password" required autofocus>
                </div>

                <div class="field">
                    <label for="new_password">New Password</label>
                    <input id="new_password" name="new_password" type="password" required minlength="8" maxlength="40">
                    <p class="field-hint">Minimum 8 characters. Must differ from the temporary password.</p>
                </div>

                <div class="field">
                    <label for="new_password_confirmation">Confirm New Password</label>
                    <input id="new_password_confirmation" name="new_password_confirmation" type="password" required minlength="8" maxlength="40">
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-lock-open"></i> Update Password &amp; Continue
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="logout-form">
                @csrf
                <button type="submit" class="logout-btn">
                    <i class="fas fa-right-from-bracket"></i> Sign out instead
                </button>
            </form>

            <div class="modal-footer">
                <i class="fas fa-circle-info"></i>
                <span>This step is logged in the audit trail to establish a clear chain of custody between the administrator who issued the temporary credential and you, the rightful account holder.</span>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) { window.location.reload(); }
        });
    </script>
</body>
</html>
