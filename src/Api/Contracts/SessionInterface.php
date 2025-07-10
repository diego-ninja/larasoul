<?php

namespace Ninja\Larasoul\Api\Contracts;

use Ninja\Larasoul\Api\Responses\AuthenticateSessionResponse;
use Ninja\Larasoul\Api\Responses\SessionResponse;
use Ninja\Larasoul\DTO\UserAccount;

interface SessionInterface
{
    /**
     * Authenticate session with account
     */
    public function authenticate(UserAccount $account, string $sessionId, bool $accountsLinked = false): AuthenticateSessionResponse;

    /**
     * Evaluate unauthenticated session
     */
    public function unauthenticated(string $sessionId, bool $accountsLinked = false): SessionResponse;

    /**
     * Get session details
     */
    public function getSession(string $sessionId): SessionResponse;
}
