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

    /*
    |--------------------------------------------------------------------------
    | Speech providers
    |--------------------------------------------------------------------------
    |
    | "fake" plays a short chime instead of real speech; it needs no account
    | and is meant for development until a provider is chosen.
    |
    */

    'speech' => [
        'tts' => env('FALADEV_TTS_DRIVER', 'fake'),
        'sample_phrase' => "Hi! This is a quick audio check. If you can hear me clearly, you're ready to start your interview.",
    ],

];
