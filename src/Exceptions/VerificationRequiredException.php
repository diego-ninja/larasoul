<?php

namespace Ninja\Larasoul\Exceptions;

/**
 * Exception thrown when verification is required but not present
 */
class VerificationRequiredException extends VerificationException
{
    public function __construct(
        string $message = 'UserAccount verification is required to access this resource',
        ?array $context = null
    ) {
        parent::__construct($message, 'verification_required', $context, 403);
    }

    public static function forUser($user): self
    {
        $context = [
            'user_id' => $user?->getAuthIdentifier(),
            'verification_status' => method_exists($user, 'getVerificationStatus')
                ? $user->getVerificationStatus()
                : 'unknown',
        ];

        return new self('Your account requires verification to access this resource', $context);
    }

    public static function expired($user): self
    {
        $context = [
            'user_id' => $user?->getAuthIdentifier(),
            'expired_at' => method_exists($user, 'getVerificationExpiryDate')
                ? $user->getVerificationExpiryDate()
                : null,
        ];

        return new self('Your verification has expired and needs renewal', $context);
    }
}
