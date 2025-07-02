<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Verisoul
    |--------------------------------------------------------------------------
    |
    */
    'verisoul' => [
        'api_key' => env('VERISOUL_API_KEY'),
        'enabled' => env('VERISOUL_ENABLED', false),
        'environment' => env('VERISOUL_ENVIRONMENT', 'sandbox'),
        'timeout' => env('VERISOUL_TIMEOUT', 30),
        'retry_attempts' => env('VERISOUL_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('VERISOUL_RETRY_DELAY', 1000),

        'id_check' => [
            'enabled' => env('VERISOUL_ID_CHECK_ENABLED', true),
            'verification_url' => env('VERISOUL_ID_CHECK_VERIFICATION_URL', '/anti-fraud/verisoul/id-check/verify'),
        ],

        'face_match' => [
            'enabled' => env('VERISOUL_FACE_MATCH_ENABLED', true),
            'verification_url' => env('VERISOUL_FACE_MATCH_VERIFICATION_URL', '/anti-fraud/verisoul/face-match/verify'),
        ],
    ],
];
