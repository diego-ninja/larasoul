<?php

namespace Ninja\Larasoul\Contracts;

use Ninja\Larasoul\Api\Responses\AccountResponse;
use Ninja\Larasoul\Api\Responses\AccountSessionsResponse;
use Ninja\Larasoul\Api\Responses\DeleteAccountResponse;
use Ninja\Larasoul\Api\Responses\LinkedAccountsResponse;

interface AccountInterface
{
    /**
     * Get account details
     */
    public function getAccount(string $accountId): AccountResponse;

    /**
     * Get account sessions
     */
    public function getAccountSessions(string $accountId): AccountSessionsResponse;

    /**
     * Get linked accounts
     */
    public function getLinkedAccounts(string $accountId): LinkedAccountsResponse;

    /**
     * Update account
     */
    public function updateAccount(string $accountId, array $data): AccountResponse;

    /**
     * Delete account
     */
    public function deleteAccount(string $accountId): DeleteAccountResponse;
}