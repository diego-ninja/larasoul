<?php

namespace Ninja\Larasoul\Exceptions;

/**
 * Exception thrown when user's risk level is too high
 */
class HighRiskUserException extends VerificationException
{
    public function __construct(
        string $message = 'Access denied due to high risk profile',
        ?array $context = null
    ) {
        parent::__construct($message, 'high_risk_user', $context, 403);
    }

    public static function forUser($user, string $maxAllowedLevel): self
    {
        $userRiskLevel = method_exists($user, 'getRiskLevel') ? $user->getRiskLevel() : 'unknown';
        $userRiskScore = method_exists($user, 'getRiskScore') ? $user->getRiskScore() : null;
        $riskFlags = method_exists($user, 'getRiskFlags') ? $user->getRiskFlags() : [];

        $context = [
            'user_id' => $user?->getAuthIdentifier(),
            'user_risk_level' => $userRiskLevel,
            'user_risk_score' => $userRiskScore,
            'max_allowed_level' => $maxAllowedLevel,
            'risk_flags' => $riskFlags,
        ];

        $message = "Access denied. Your risk level ({$userRiskLevel}) exceeds the maximum allowed ({$maxAllowedLevel})";

        return new self($message, $context);
    }

    public static function blockingFlags($user): self
    {
        $riskFlags = method_exists($user, 'getRiskFlags') ? $user->getRiskFlags() : [];

        $context = [
            'user_id' => $user?->getAuthIdentifier(),
            'blocking_flags' => $riskFlags,
        ];

        return new self('Access denied due to security flags on your account', $context);
    }
}
