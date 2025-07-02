<?php

namespace Ninja\Larasoul\Contracts;

use Ninja\Larasoul\Api\Responses\SessionResponse;
use Ninja\Larasoul\DTO\Account;

interface SessionInterface
{
    /**
     * Authenticate session with account
     */
    public function authenticate(Account $account, string $sessionId, bool $accountsLinked = false): SessionResponse;

    /**
     * Evaluate unauthenticated session
     */
    public function unauthenticated(string $sessionId, bool $accountsLinked = false): SessionResponse;

    /**
     * Get session details
     */
    public function getSession(string $sessionId): SessionResponse;
}
