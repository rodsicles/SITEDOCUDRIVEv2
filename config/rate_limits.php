<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Relax route rate limits (defense / demo)
    |--------------------------------------------------------------------------
    |
    | When true, throttle middleware on uploads, folders, faculty, approvals,
    | archives, backup, etc. is skipped. Client form cooldown is also disabled.
    |
    | Set DEFENSE_RELAX_RATE_LIMITS=false on Laravel Cloud after defense.
    |
    */
    'relaxed' => filter_var(env('DEFENSE_RELAX_RATE_LIMITS', true), FILTER_VALIDATE_BOOL),

    /*
    |--------------------------------------------------------------------------
    | Client submit cooldown (milliseconds)
    |--------------------------------------------------------------------------
    */
    'request_guard_cooldown_ms' => (int) env(
        'REQUEST_GUARD_COOLDOWN_MS',
        filter_var(env('DEFENSE_RELAX_RATE_LIMITS', true), FILTER_VALIDATE_BOOL) ? 0 : 2500
    ),

];
