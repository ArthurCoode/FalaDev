<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Usage limits
    |--------------------------------------------------------------------------
    |
    | Per-account limit used while validating the product (MVP item 9). The
    | default is a placeholder until real cost per session is measured.
    |
    */

    'usage' => [
        'monthly_session_limit' => (int) env('FALADEV_MONTHLY_SESSION_LIMIT', 10),
    ],

];
