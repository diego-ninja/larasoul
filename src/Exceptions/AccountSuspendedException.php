<?php

namespace Ninja\Larasoul\Exceptions;

/**
 * Exception thrown when account is suspended due to fraud
 */
class AccountSuspendedException extends VerificationException
{
    public function __construct(
        string $message = 'Your account has been suspended',
        ?array $context = null
    ) {
        parent::__construct($message, 'account_suspended', $context, 403);
    }

    public static function forFraud($user): self
    {
        $context = [
            'user_id' => $user?->getAuthIdentifier(),
            'suspension_reason' => 'fraud_detection',
            'contact_support' => true,
        ];

        return new self(
            'Your account has been suspended due to security concerns. Please contact support.',
            $context
        );
    }

    public static function forHighRisk($user): self
    {
        $context = [
            'user_id' => $user?->getAuthIdentifier(),
            'suspension_reason' => 'high_risk',
            'risk_level' => method_exists($user, 'getRiskLevel') ? $user->getRiskLevel() : 'unknown',
        ];

        return new self(
            'Your account has been suspended due to high risk activity. Please contact support.',
            $context
        );
    }
}
