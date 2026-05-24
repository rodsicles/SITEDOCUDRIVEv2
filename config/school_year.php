<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Temporary hard delete of archived school years (dry-run / cleanup)
    |--------------------------------------------------------------------------
    |
    | When false, Dean cannot permanently delete an archived school year bucket.
    | Set ALLOW_ARCHIVE_HARD_DELETE=false after dry runs end.
    |
    */
    'allow_archive_hard_delete' => env('ALLOW_ARCHIVE_HARD_DELETE', true),

];
