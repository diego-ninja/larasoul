<?php

namespace Ninja\Larasoul\Api\Clients;

use Ninja\Larasoul\Api\Responses\SessionResponse;
use Ninja\Larasoul\Contracts\SessionInterface;
use Ninja\Larasoul\DTO\Account;
use Ninja\Larasoul\Enums\VerisoulApiEndpoint;
use Ninja\Larasoul\Exceptions\VerisoulApiException;
use Ninja\Larasoul\Exceptions\VerisoulConnectionException;

final class SessionClient extends Client implements SessionInterface
{
    /**
     * @throws VerisoulApiException
     * @throws VerisoulConnectionException
     */
    public function authenticate(Account $account, string $sessionId, bool $accountsLinked = false): SessionResponse
    {
        $response = $this->call(
            VerisoulApiEndpoint::SessionAuthenticate,
            [
                'accounts_linked' => $accountsLinked,
            ],
            [
                'account' => $account->toArray(),
                'session_id' => $sessionId,
            ]
        );

        return SessionResponse::from($response);
    }

    /**
     * @throws VerisoulApiException
     * @throws VerisoulConnectionException
     */
    public function unauthenticated(string $sessionId, bool $accountsLinked = false): SessionResponse
    {
        $response = $this->call(
            VerisoulApiEndpoint::SessionAuthenticate,
            [
                'accounts_linked' => $accountsLinked,
            ],
            [
                'session_id' => $sessionId,
            ]
        );

        return SessionResponse::from($response);
    }

    /**
     * @throws VerisoulApiException
     * @throws VerisoulConnectionException
     */
    public function getSession(string $sessionId): SessionResponse
    {
        $response = $this->call(
            VerisoulApiEndpoint::SessionGet,
            ['session_id' => $sessionId]
        );

        return SessionResponse::from($response);
    }
}
