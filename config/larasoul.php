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
    | Verisoul API Configuration
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
        'liveness' => [
            'auto_send' => env('VERISOUL_AUTO_SEND_ID_CHECK', true),
            'auto_enroll' => env('VERISOUL_AUTO_ENROLL_ID_CHECK', true),
            'verification_callback_url' => env('VERISOUL_ID_CHECK_VERIFICATION_CALLBACK_URL', 'larasoul.liveness.verify'),
            'id_check' => [
                'enabled' => env('VERISOUL_ID_CHECK_ENABLED', true),
            ],
            'face_match' => [
                'enabled' => env('VERISOUL_FACE_MATCH_ENABLED', true),
            ],
        ],
        // Frontend JavaScript SDK Configuration
        'frontend' => [
            'enabled' => env('VERISOUL_FRONTEND_ENABLED', false),
            'project_id' => env('VERISOUL_PROJECT_ID'),
            'async_loading' => env('VERISOUL_ASYNC_LOADING', true),
            'auto_inject' => env('VERISOUL_AUTO_INJECT', false),
            'session_capture' => [
                'enabled' => env('VERISOUL_SESSION_CAPTURE_ENABLED', true),
                'endpoint' => env('VERISOUL_SESSION_ENDPOINT', '/verisoul/session'),
                'auto_send' => env('VERISOUL_AUTO_SEND_SESSION', true),
            ],
            'excluded_routes' => [
                'api/*',
                'admin/*',
                '_debugbar/*',
            ],
        ],
    ],

    'session' => [
        'verisoul_session_id' => env('VERISOUL_SESSION_ID', 'verisoul_session_id'),
        'cache_key_prefix' => env('VERISOUL_CACHE_KEY_PREFIX', 'verisoul_session'),
    ],
    /*
    |--------------------------------------------------------------------------
    | User Verification Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the user verification system
    |
    */
    'verification' => [
        /*
        |--------------------------------------------------------------------------
        | Verification Expiry
        |--------------------------------------------------------------------------
        |
        | How long a verification is valid before it expires
        |
        */
        'expiry_months' => env('VERISOUL_VERIFICATION_EXPIRY_MONTHS', 3),

        /*
        |--------------------------------------------------------------------------
        | Verification Attempts
        |--------------------------------------------------------------------------
        |
        | Maximum number of verification attempts before requiring manual review
        |
        */
        'max_attempts' => env('VERISOUL_MAX_VERIFICATION_ATTEMPTS', 3),

        /*
        |--------------------------------------------------------------------------
        | Risk Score Thresholds
        |--------------------------------------------------------------------------
        |
        | Risk score thresholds for different risk levels
        |
        */
        'risk_thresholds' => [
            'low' => 0.3,
            'medium' => 0.7,
            'high' => 1.0,
        ],

        /*
        |--------------------------------------------------------------------------
        | Auto Actions
        |--------------------------------------------------------------------------
        |
        | Automatic actions based on verification results
        |
        */
        'auto_actions' => [
            'suspend_high_risk' => env('VERISOUL_AUTO_SUSPEND_HIGH_RISK', false),
            'approve_low_risk' => env('VERISOUL_AUTO_APPROVE_LOW_RISK', true),
            'require_manual_review_medium_risk' => env('VERISOUL_MANUAL_REVIEW_MEDIUM_RISK', true),
        ],

        /*
        |--------------------------------------------------------------------------
        | Verification Requirements
        |--------------------------------------------------------------------------
        |
        | What verification types are required for different user levels
        |
        */
        'requirements' => [
            'basic' => ['phone'],
            'standard' => ['phone', 'face'],
            'premium' => ['phone', 'face', 'document'],
            'high_value' => ['phone', 'face', 'identity'],
        ],

        /*
        |--------------------------------------------------------------------------
        | Cache Settings
        |--------------------------------------------------------------------------
        |
        | Caching configuration for verification data
        |
        */
        'cache' => [
            'verification_ttl' => env('VERISOUL_VERIFICATION_CACHE_TTL', 3600), // 1 hour
            'status_ttl' => env('VERISOUL_STATUS_CACHE_TTL', 300), // 5 minutes
            'prefix' => env('VERISOUL_CACHE_PREFIX', 'verisoul'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Notifications
        |--------------------------------------------------------------------------
        |
        | Notification settings for verification events
        |
        */
        'notifications' => [
            'channels' => [
                'high_risk' => ['mail', 'slack'],
                'verification_failed' => ['mail'],
                'verification_completed' => ['mail'],
                'manual_review' => ['mail', 'slack'],
                'fraud_detected' => ['mail', 'slack', 'sms'],
            ],
            'admin_channels' => [
                'mail' => env('VERISOUL_ADMIN_EMAIL'),
                'slack' => env('VERISOUL_SLACK_WEBHOOK'),
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Risk Check Settings
        |--------------------------------------------------------------------------
        |
        | Settings for periodic risk checks
        |
        */
        'risk_checks' => [
            'interval_days' => env('VERISOUL_RISK_CHECK_INTERVAL_DAYS', 30),
            'auto_update' => env('VERISOUL_AUTO_UPDATE_RISK_SCORES', true),
            'expiry_warning_days' => env('VERISOUL_EXPIRY_WARNING_DAYS', 7),
        ],

        /*
        |--------------------------------------------------------------------------
        | Document Verification Settings
        |--------------------------------------------------------------------------
        |
        | Settings specific to document verification
        |
        */
        'document' => [
            'accepted_types' => [
                'drivers_license',
                'passport',
                'national_id',
                'state_id',
            ],
            'accepted_countries' => ['US', 'CA', 'GB', 'AU'], // Empty array for all countries
            'require_face_match' => env('VERISOUL_REQUIRE_FACE_MATCH', true),
            'min_face_match_score' => env('VERISOUL_MIN_FACE_MATCH_SCORE', 0.8),
        ],

        /*
        |--------------------------------------------------------------------------
        | Face Verification Settings
        |--------------------------------------------------------------------------
        |
        | Settings specific to face verification
        |
        */
        'face' => [
            'require_liveness' => env('VERISOUL_REQUIRE_LIVENESS', true),
            'min_quality_score' => env('VERISOUL_MIN_FACE_QUALITY_SCORE', 0.7),
            'allow_retries' => env('VERISOUL_ALLOW_FACE_RETRIES', true),
        ],

        /*
        |--------------------------------------------------------------------------
        | Phone Verification Settings
        |--------------------------------------------------------------------------
        |
        | Settings specific to phone verification
        |
        */
        'phone' => [
            'require_sms' => env('VERISOUL_REQUIRE_SMS_VERIFICATION', true),
            'allowed_line_types' => ['mobile', 'landline'],
            'blocked_carriers' => [], // Carriers to block
        ],

        /*
        |--------------------------------------------------------------------------
        | Security Settings
        |--------------------------------------------------------------------------
        |
        | Security-related configuration
        |
        */
        'security' => [
            'max_daily_attempts' => env('VERISOUL_MAX_DAILY_ATTEMPTS', 5),
            'lockout_duration_minutes' => env('VERISOUL_LOCKOUT_DURATION', 60),
            'enable_fraud_detection' => env('VERISOUL_ENABLE_FRAUD_DETECTION', true),
            'auto_suspend_fraud' => env('VERISOUL_AUTO_SUSPEND_FRAUD', true),
        ],

        /*
        |--------------------------------------------------------------------------
        | Model Bindings
        |--------------------------------------------------------------------------
        |
        | Custom model class bindings
        |
        */
        'models' => [
            'user_verification' => \Ninja\Larasoul\Models\RiskProfile::class,
        ],

        /*
        |--------------------------------------------------------------------------
        | Audit Settings
        |--------------------------------------------------------------------------
        |
        | Audit and logging configuration
        |
        */
        'audit' => [
            'log_all_attempts' => env('VERISOUL_LOG_ALL_ATTEMPTS', true),
            'log_level' => env('VERISOUL_LOG_LEVEL', 'info'),
            'retain_logs_days' => env('VERISOUL_RETAIN_LOGS_DAYS', 90),
        ],

        /*
        |--------------------------------------------------------------------------
        | API Settings
        |--------------------------------------------------------------------------
        |
        | API-specific configuration
        |
        */
        'api' => [
            'rate_limit' => env('VERISOUL_API_RATE_LIMIT', 100), // requests per hour
            'webhook_secret' => env('VERISOUL_WEBHOOK_SECRET'),
            'webhook_endpoints' => [
                'verification_completed' => '/webhooks/verisoul/verification-completed',
                'high_risk_detected' => '/webhooks/verisoul/high-risk-detected',
                'fraud_detected' => '/webhooks/verisoul/fraud-detected',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Compliance Settings
        |--------------------------------------------------------------------------
        |
        | Compliance and regulatory requirements
        |
        */
        'compliance' => [
            'standard' => [
                'max_risk_score' => 0.7,
                'required_verification_types' => 2,
                'max_linked_accounts' => 3,
                'min_face_match_score' => 0.8,
            ],
            'strict' => [
                'max_risk_score' => 0.3,
                'required_verification_types' => 3,
                'max_linked_accounts' => 1,
                'min_face_match_score' => 0.9,
            ],
        ],
    ],
];
