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
 * Facade for easy middleware access
 */
class VerificationMiddleware
{
    /**
     * Get verification middleware instance
     */
    public static function verification(?string $redirectRoute = null): string
    {
        return RequireVerification::class.($redirectRoute ? ":{$redirectRoute}" : '');
    }

    /**
     * Get risk level middleware instance
     */
    public static function riskLevel(string $maxLevel = 'medium', ?string $redirectRoute = null): string
    {
        $params = $redirectRoute ? "{$maxLevel},{$redirectRoute}" : $maxLevel;

        return RequireRiskLevel::class.":{$params}";
    }

    /**
     * Get verification type middleware instance
     */
    public static function verificationType(string $type, ?string $redirectRoute = null): string
    {
        $params = $redirectRoute ? "{$type},{$redirectRoute}" : $type;

        return RequireVerificationType::class.":{$params}";
    }

    /**
     * Get verification level middleware instance
     */
    public static function verificationLevel(string $level = 'basic', ?string $redirectRoute = null): string
    {
        $params = $redirectRoute ? "{$level},{$redirectRoute}" : $level;

        return RequireVerificationLevel::class.":{$params}";
    }

    /**
     * Get document verification middleware
     */
    public static function document(?string $redirectRoute = null): string
    {
        return RequireDocumentVerification::class.($redirectRoute ? ":{$redirectRoute}" : '');
    }

    /**
     * Get face verification middleware
     */
    public static function face(?string $redirectRoute = null): string
    {
        return RequireFaceVerification::class.($redirectRoute ? ":{$redirectRoute}" : '');
    }

    /**
     * Get phone verification middleware
     */
    public static function phone(?string $redirectRoute = null): string
    {
        return RequirePhoneVerification::class.($redirectRoute ? ":{$redirectRoute}" : '');
    }
}
