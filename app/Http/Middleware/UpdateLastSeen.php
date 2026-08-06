<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastSeen
{
    /**
     * Throttle DB writes: only update once per minute per user.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $shouldUpdate = is_null($user->last_seen_at)
                || $user->last_seen_at->lt(now()->subMinute());

            if ($shouldUpdate) {
                $user->timestamps = false;
                $user->forceFill(['last_seen_at' => now()])->save();
            }
        }

        return $next($request);
    }
}
