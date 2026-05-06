<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Required &mdash; Change Password</title>
    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Faux-dashboard pattern in the background so the blur is visually meaningful. */
        body { font-family: 'Inter', system-ui, sans-serif; }
        .bg-pattern {
            background:
                radial-gradient(circle at 12% 18%, rgba(2,138,15,0.18) 0, transparent 32%),
                radial-gradient(circle at 88% 76%, rgba(2,138,15,0.14) 0, transparent 28%),
                linear-gradient(135deg, #f1f5f4 0%, #e7ebe9 100%);
        }
        .dark .bg-pattern {
            background:
                radial-gradient(circle at 12% 18%, rgba(2,138,15,0.28) 0, transparent 32%),
                radial-gradient(circle at 88% 76%, rgba(2,138,15,0.20) 0, transparent 28%),
                linear-gradient(135deg, #1a1a1a 0%, #0f0f0f 100%);
        }
        .blur-overlay {
            backdrop-filter: blur(10px) saturate(140%);
            -webkit-backdrop-filter: blur(10px) saturate(140%);
            background-color: rgba(0, 0, 0, 0.45);
        }
    </style>
</head>
<body class="bg-pattern min-h-screen relative overflow-hidden">

    {{-- Decorative "dashboard" silhouettes behind the blur to communicate "system is locked" --}}
    <div aria-hidden="true" class="absolute inset-0 p-10 grid grid-cols-3 gap-6 select-none pointer-events-none opacity-60">
        @for ($i = 0; $i < 9; $i++)
            <div class="bg-white/70 dark:bg-white/10 rounded-md h-32 shadow-sm"></div>
        @endfor
    </div>

    {{-- Locked backdrop --}}
    <div class="fixed inset-0 z-[9000] blur-overlay flex items-center justify-center p-4">

        {{-- Modal --}}
        <div role="dialog" aria-modal="true" aria-labelledby="forceChangeTitle"
             class="w-full max-w-md bg-white dark:bg-[#1f1f1f] border border-gray-200 dark:border-gray-700 shadow-2xl rounded-lg overflow-hidden">

            <div class="bg-[#028a0f] text-white px-5 py-4 flex items-center gap-3">
                <i class="fas fa-shield-halved text-xl"></i>
                <div>
                    <h1 id="forceChangeTitle" class="text-base font-semibold leading-tight">Security Required</h1>
                    <p class="text-xs text-green-100/90 leading-tight mt-0.5">First-time login &mdash; change your temporary password</p>
                </div>
            </div>

            <div class="px-5 pt-4 pb-2 text-xs text-gray-600 dark:text-gray-300 border-b border-gray-100 dark:border-gray-700">
                For your account's protection, you must change the temporary password issued by the administrator before you can use the system.
            </div>

            @if ($errors->any())
                <div class="mx-5 mt-3 p-2 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700/60 text-red-700 dark:text-red-200 text-xs rounded">
                    @foreach ($errors->all() as $error)
                        <div><i class="fas fa-circle-exclamation mr-1"></i>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.force-change.update') }}" class="p-5 flex flex-col gap-3" autocomplete="off">
                @csrf

                <div>
                    <label for="current_password" class="block text-xs font-medium text-gray-700 dark:text-gray-200 mb-1">
                        Current (Temporary) Password
                    </label>
                    <input id="current_password" name="current_password" type="password" required autofocus
                           class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#2a2a2a] text-gray-800 dark:text-gray-100 rounded focus:outline-none focus:border-[#028a0f]">
                </div>

                <div>
                    <label for="new_password" class="block text-xs font-medium text-gray-700 dark:text-gray-200 mb-1">
                        New Password
                    </label>
                    <input id="new_password" name="new_password" type="password" required minlength="8" maxlength="40"
                           class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#2a2a2a] text-gray-800 dark:text-gray-100 rounded focus:outline-none focus:border-[#028a0f]">
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Minimum 8 characters. Must differ from the temporary password.</p>
                </div>

                <div>
                    <label for="new_password_confirmation" class="block text-xs font-medium text-gray-700 dark:text-gray-200 mb-1">
                        Confirm New Password
                    </label>
                    <input id="new_password_confirmation" name="new_password_confirmation" type="password" required minlength="8" maxlength="40"
                           class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#2a2a2a] text-gray-800 dark:text-gray-100 rounded focus:outline-none focus:border-[#028a0f]">
                </div>

                <button type="submit"
                        class="mt-2 w-full bg-[#028a0f] hover:bg-[#026e0c] text-white text-sm font-medium py-2.5 rounded transition-colors">
                    <i class="fas fa-lock-open mr-1"></i> Update Password &amp; Continue
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="px-5 pb-4 -mt-2">
                @csrf
                <button type="submit"
                        class="w-full text-xs text-gray-500 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors">
                    <i class="fas fa-right-from-bracket mr-1"></i> Sign out instead
                </button>
            </form>

            <div class="px-5 py-3 bg-gray-50 dark:bg-[#181818] border-t border-gray-100 dark:border-gray-700 text-[11px] text-gray-500 dark:text-gray-400 flex items-start gap-2">
                <i class="fas fa-circle-info mt-0.5"></i>
                <span>This step is logged in the audit trail to establish a clear chain of custody between the administrator who issued the temporary credential and you, the rightful account holder.</span>
            </div>
        </div>
    </div>

    {{-- Block back/forward cache so the locked screen always re-renders. --}}
    <script>
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) { window.location.reload(); }
        });
    </script>
</body>
</html>
