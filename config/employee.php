<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Temporary account hard delete (dry-run / data cleanup)
    |--------------------------------------------------------------------------
    |
    | When false, Dean cannot permanently delete faculty/coordinator accounts.
    | Set ALLOW_ACCOUNT_HARD_DELETE=false in production after dry runs end.
    |
    */
    'allow_hard_delete' => env('ALLOW_ACCOUNT_HARD_DELETE', true),

];
