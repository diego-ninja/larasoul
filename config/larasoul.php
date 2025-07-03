<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Auth middleware
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify the middleware that should be used for
    | authenticating the user in the routes.
    */
    'auth_middleware' => 'auth',
    /*
    |--------------------------------------------------------------------------
    | Verisoul config
    |--------------------------------------------------------------------------
    | These options allow you to configure the Verisoul API.
    |
    | - api_key: The API key for the Verisoul API.
    | - enabled: Whether Verisoul is enabled.
    | - environment: The environment to use for the Verisoul API.
    | - timeout: The timeout for the Verisoul API.
    | - retry_attempts: The number of retry attempts for the Verisoul API.
    | - retry_delay: The delay between retry attempts for the Verisoul API.
    | - id_check: The configuration for the ID check service.
    | - face_match: The configuration for the face match service.
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
            'verification_url' => env('VERISOUL_ID_CHECK_VERIFICATION_URL', '/larasoul/id-check/verify'),
        ],

        'face_match' => [
            'enabled' => env('VERISOUL_FACE_MATCH_ENABLED', true),
            'verification_url' => env('VERISOUL_FACE_MATCH_VERIFICATION_URL', '/larasoul/face-match/verify'),
        ],
    ],
];
