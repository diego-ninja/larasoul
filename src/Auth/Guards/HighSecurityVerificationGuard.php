<?php

namespace Ninja\Larasoul\Auth\Guards;

use Illuminate\Contracts\Auth\Authenticatable;
use Ninja\Larasoul\Exceptions\HighRiskUserException;
use Ninja\Larasoul\Exceptions\VerificationRequiredException;

/**
 * Specific guard for high-security operations
 */
class HighSecurityVerificationGuard extends VerificationGuard
{
    protected function shouldRequireVerification(): bool
    {
        return true; // Always require verification for high security
    }

    protected function shouldCheckRiskLevel(): bool
    {
        return true; // Always check risk level for high security
    }

    protected function getMaxRiskLevel(): string
    {
        return 'low'; // Only allow low risk users
    }

    /**
     * Additional checks for high security
     *
     * @throws VerificationRequiredException
     * @throws HighRiskUserException
     */
    protected function performVerificationChecks(Authenticatable $user): void
    {
        parent::performVerificationChecks($user);

        // Require full verification for high security
        if (method_exists($user, 'isFullyVerified') && ! $user->isFullyVerified()) {
            throw VerificationRequiredException::forUser($user);
        }

        // Check for recent activity
        if ($this->hasRecentSuspiciousActivity($user)) {
            throw HighRiskUserException::forUser($user, 'low');
        }
    }

    /**
     * Check for recent suspicious activity
     */
    protected function hasRecentSuspiciousActivity(Authenticatable $user): bool
    {
        // This could check for:
        // - Recent failed verification attempts
        // - Login from new devices/locations
        // - Unusual transaction patterns
        // - Recent password changes

        if (method_exists($user, 'getVerificationAttempts')) {
            $attempts = $user->getVerificationAttempts();
            if ($attempts > 2) {
                return true;
            }
        }

        return false;
    }
}
