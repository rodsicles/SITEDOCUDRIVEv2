<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces an authenticated user to change their password on first login
 * (or whenever an admin has flagged the account via must_change_password).
 *
 * While the flag is true, the user can ONLY hit:
 *   - The forced-change page itself (GET/POST)
 *   - Logout
 * Every other request is redirected to the forced-change page so there
 * is no way to access dashboards/data until the password is rotated.
 */
class EnsurePasswordChanged
{
    /**
     * Routes that remain reachable while the user is locked into the
     * forced-change flow. Keep this list intentionally tiny.
     */
    protected array $exemptRoutes = [
        'password.force-change.show',
        'password.force-change.update',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user || empty($user->must_change_password)) {
            return $next($request);
        }

        $currentRouteName = optional($request->route())->getName();

        if (in_array($currentRouteName, $this->exemptRoutes, true)) {
            return $next($request);
        }

        // For AJAX/JSON callers, return a 423 (Locked) so the front-end can
        // react gracefully instead of receiving an HTML redirect blob.
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Password change required before continuing.',
                'redirect' => route('password.force-change.show'),
            ], 423);
        }

        return redirect()->route('password.force-change.show');
    }
}
