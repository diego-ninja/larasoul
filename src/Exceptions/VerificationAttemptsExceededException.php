<?php

namespace Ninja\Larasoul\Exceptions;

/**
 * Exception thrown when verification attempts are exceeded
 */
class VerificationAttemptsExceededException extends VerificationException
{
    public function __construct(
        string $message = 'Maximum verification attempts exceeded',
        ?array $context = null
    ) {
        parent::__construct($message, 'verification_attempts_exceeded', $context, 429);
    }

    public static function forUser($user, int $maxAttempts): self
    {
        $currentAttempts = method_exists($user, 'getVerificationAttempts')
            ? $user->getVerificationAttempts()
            : 0;

        $context = [
            'user_id' => $user?->getAuthIdentifier(),
            'current_attempts' => $currentAttempts,
            'max_attempts' => $maxAttempts,
            'lockout_until' => now()->addHours(24)->toISOString(),
        ];

        return new self(
            "Maximum verification attempts ({$maxAttempts}) exceeded. Please try again later.",
            $context
        );
    }
}
