<?php

namespace Ninja\Larasoul\Auth\Guards;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Ninja\Larasoul\Exceptions\HighRiskUserException;
use Ninja\Larasoul\Exceptions\VerificationRequiredException;

class VerificationGuard implements Guard
{
    use GuardHelpers;

    protected array $verificationConfig;

    public function __construct(
        UserProvider $provider,
        protected Request $request,
        protected string $inputKey = 'api_token',
        protected string $storageKey = 'api_token',
        protected string $hash = 'sha256'
    ) {
        $this->provider = $provider;
        $this->verificationConfig = config('larasoul.verification', []);
    }

    /**
     * Get the currently authenticated user.
     */
    public function user(): ?Authenticatable
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $user = null;
        $token = $this->getTokenForRequest();

        if (! empty($token)) {
            $user = $this->provider->retrieveByCredentials([
                $this->storageKey => hash($this->hash, $token),
            ]);
        }

        // Perform verification checks if user is found
        if ($user) {
            $this->performVerificationChecks($user);
            $this->user = $user;
        }

        return $this->user;
    }

    /**
     * Validate a user's credentials.
     */
    public function validate(array $credentials = []): bool
    {
        if (empty($credentials[$this->inputKey])) {
            return false;
        }

        $credentials = [$this->storageKey => hash($this->hash, $credentials[$this->inputKey])];

        $user = $this->provider->retrieveByCredentials($credentials);

        if ($user && $this->provider->validateCredentials($user, $credentials)) {
            // Perform verification checks
            try {
                $this->performVerificationChecks($user);

                return true;
            } catch (VerificationRequiredException|HighRiskUserException) {
                return false;
            }
        }

        return false;
    }

    /**
     * Set the current user.
     */
    public function setUser(Authenticatable $user): void
    {
        $this->user = $user;
    }

    /**
     * Get the token for the current request.
     */
    public function getTokenForRequest(): ?string
    {
        $token = $this->request->query($this->inputKey);

        if (empty($token)) {
            $token = $this->request->input($this->inputKey);
        }

        if (empty($token)) {
            $token = $this->request->bearerToken();
        }

        if (empty($token)) {
            $token = $this->request->getPassword();
        }

        return $token;
    }

    /**
     * Perform verification checks on the user
     *
     * @throws VerificationRequiredException
     * @throws HighRiskUserException
     */
    protected function performVerificationChecks(Authenticatable $user): void
    {
        // Skip checks if verification is disabled
        if (! ($this->verificationConfig['enabled'] ?? true)) {
            return;
        }

        // Check if user has verification trait
        if (! method_exists($user, 'isVerified')) {
            return; // Skip if trait not present
        }

        // Check verification requirement
        if ($this->shouldRequireVerification() && ! $user->isVerified()) {
            throw VerificationRequiredException::forUser($user);
        }

        // Check if verification is expired
        if (method_exists($user, 'isVerificationExpired') && $user->isVerificationExpired()) {
            throw VerificationRequiredException::expired($user);
        }

        // Check risk level
        if ($this->shouldCheckRiskLevel()) {
            $this->checkRiskLevel($user);
        }

        // Check for blocking flags
        if (method_exists($user, 'hasBlockingRiskFlags') && $user->hasBlockingRiskFlags()) {
            throw HighRiskUserException::blockingFlags($user);
        }
    }

    /**
     * Check if verification should be required
     */
    protected function shouldRequireVerification(): bool
    {
        $guardConfig = $this->verificationConfig['guards'][static::class] ?? [];

        return $guardConfig['require_verification'] ?? false;
    }

    /**
     * Check if risk level should be validated
     */
    protected function shouldCheckRiskLevel(): bool
    {
        $guardConfig = $this->verificationConfig['guards'][static::class] ?? [];

        return $guardConfig['check_risk_level'] ?? false;
    }

    /**
     * Get maximum allowed risk level for this guard
     */
    protected function getMaxRiskLevel(): string
    {
        $guardConfig = $this->verificationConfig['guards'][static::class] ?? [];

        return $guardConfig['max_risk_level'] ?? 'medium';
    }

    /**
     * Check user's risk level
     *
     * @throws HighRiskUserException
     */
    protected function checkRiskLevel(Authenticatable $user): void
    {
        if (! method_exists($user, 'getRiskLevel')) {
            return;
        }

        $userRiskLevel = $user->getRiskLevel();
        $maxRiskLevel = $this->getMaxRiskLevel();

        $riskLevels = ['low' => 1, 'medium' => 2, 'high' => 3, 'unknown' => 4];
        $userLevel = $riskLevels[$userRiskLevel] ?? 4;
        $maxLevel = $riskLevels[$maxRiskLevel] ?? 2;

        if ($userLevel > $maxLevel) {
            throw HighRiskUserException::forUser($user, $maxRiskLevel);
        }
    }
}
