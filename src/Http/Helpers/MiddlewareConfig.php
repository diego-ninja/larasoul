<?php

namespace Ninja\Larasoul\Http\Helpers;

use Ninja\Larasoul\Http\Middleware\RequireDocumentVerification;
use Ninja\Larasoul\Http\Middleware\RequireFaceVerification;
use Ninja\Larasoul\Http\Middleware\RequirePhoneVerification;
use Ninja\Larasoul\Http\Middleware\RequireRiskLevel;
use Ninja\Larasoul\Http\Middleware\RequireVerification;
use Ninja\Larasoul\Http\Middleware\RequireVerificationLevel;
use Ninja\Larasoul\Http\Middleware\RequireVerificationType;

/**
 * Middleware configuration helper
 */
class MiddlewareConfig
{
    /**
     * Get middleware configuration for routes
     */
    public static function getRouteMiddleware(): array
    {
        return [
            'require.verification' => RequireVerification::class,
            'require.risk.level' => RequireRiskLevel::class,
            'require.verification.type' => RequireVerificationType::class,
            'require.verification.level' => RequireVerificationLevel::class,
            'require.document' => RequireDocumentVerification::class,
            'require.face' => RequireFaceVerification::class,
            'require.phone' => RequirePhoneVerification::class,
        ];
    }

    /**
     * Get middleware groups configuration
     */
    public static function getMiddlewareGroups(): array
    {
        return [
            'verified' => [
                RequireVerification::class,
            ],
            'low-risk' => [
                RequireVerification::class,
                RequireRiskLevel::class.':low',
            ],
            'premium-verified' => [
                RequireVerification::class,
                RequireVerificationLevel::class.':premium',
                RequireRiskLevel::class.':medium',
            ],
            'high-security' => [
                RequireVerification::class,
                RequireVerificationLevel::class.':high_value',
                RequireRiskLevel::class.':low',
            ],
        ];
    }

    /**
     * Get guard configurations
     */
    public static function getGuardConfigurations(): array
    {
        return [
            'verification' => [
                'driver' => 'verification',
                'provider' => 'users',
                'input_key' => 'api_token',
                'storage_key' => 'api_token',
                'hash' => 'sha256',
            ],
            'high-security' => [
                'driver' => 'high-security-verification',
                'provider' => 'users',
                'input_key' => 'api_token',
                'storage_key' => 'api_token',
                'hash' => 'sha256',
            ],
            'api-verification' => [
                'driver' => 'api-verification',
                'provider' => 'users',
                'input_key' => 'api_token',
                'storage_key' => 'api_token',
                'hash' => 'sha256',
            ],
        ];
    }
}
