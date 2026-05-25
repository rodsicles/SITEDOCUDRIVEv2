<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Middleware\ThrottleRequests;

class DefenseAwareThrottle extends ThrottleRequests
{
    /**
     * Signature must match ThrottleRequests::handle() exactly (no added type hints).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int|string  $maxAttempts
     * @param  float|int  $decayMinutes
     * @param  string  $prefix
     */
    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1, $prefix = '')
    {
        if (config('rate_limits.relaxed', false)) {
            return $next($request);
        }

        return parent::handle($request, $next, $maxAttempts, $decayMinutes, $prefix);
    }
}
