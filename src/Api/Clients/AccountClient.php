<?php

namespace Ninja\Larasoul\Api\Clients;

use Ninja\Larasoul\Api\Responses\AccountResponse;
use Ninja\Larasoul\Api\Responses\AccountSessionsResponse;
use Ninja\Larasoul\Api\Responses\DeleteAccountResponse;
use Ninja\Larasoul\Api\Responses\LinkedAccountsResponse;
use Ninja\Larasoul\Api\Contracts\AccountInterface;
use Ninja\Larasoul\Enums\VerisoulApiEndpoint;
use Ninja\Larasoul\Exceptions\VerisoulApiException;
use Ninja\Larasoul\Exceptions\VerisoulConnectionException;

final class AccountClient extends Client implements AccountInterface
{
    /**
     * @throws VerisoulApiException
     * @throws VerisoulConnectionException
     */
    public function getAccount(string $accountId): AccountResponse
    {
        $response = $this->call(
            VerisoulApiEndpoint::AccountGet,
            ['account_id' => $accountId]
        );

        return AccountResponse::from($response);
    }

    /**
     * @throws VerisoulApiException
     * @throws VerisoulConnectionException
     */
    public function getAccountSessions(string $accountId): AccountSessionsResponse
    {
        $response = $this->call(
            VerisoulApiEndpoint::AccountSessions,
            ['account_id' => $accountId]
        );

        return AccountSessionsResponse::from($response);
    }

    /**
     * @throws VerisoulApiException
     * @throws VerisoulConnectionException
     */
    public function getLinkedAccounts(string $accountId): LinkedAccountsResponse
    {
        $response = $this->call(
            VerisoulApiEndpoint::AccountLinked,
            ['account_id' => $accountId]
        );

        return LinkedAccountsResponse::from($response);
    }

    /**
     * @throws VerisoulApiException
     * @throws VerisoulConnectionException
     */
    public function updateAccount(string $accountId, array $data): AccountResponse
    {
        $response = $this->call(
            VerisoulApiEndpoint::AccountUpdate,
            ['account_id' => $accountId],
            $data
        );

        return AccountResponse::from($response);
    }

    /**
     * @throws VerisoulApiException
     * @throws VerisoulConnectionException
     */
    public function deleteAccount(string $accountId): DeleteAccountResponse
    {
        $response = $this->call(
            VerisoulApiEndpoint::AccountDelete,
            ['account_id' => $accountId]
        );

        return DeleteAccountResponse::from($response);
    }
}
