<?php

namespace Ninja\Larasoul\Api\Clients;

use Ninja\Larasoul\Api\Responses\ListOperationResponse;
use Ninja\Larasoul\Collections\AccountListCollection;
use Ninja\Larasoul\Contracts\ListInterface;
use Ninja\Larasoul\DTO\AccountList;
use Ninja\Larasoul\Enums\VerisoulApiEndpoint;
use Ninja\Larasoul\Exceptions\VerisoulApiException;
use Ninja\Larasoul\Exceptions\VerisoulConnectionException;

final class ListClient extends Client implements ListInterface
{
    /**
     * @throws VerisoulApiException
     * @throws VerisoulConnectionException
     */
    public function createList(string $name, string $description): ListOperationResponse
    {
        $response = $this->call(
            VerisoulApiEndpoint::ListCreate,
            ['list_name' => $name],
            ['list_description' => $description]
        );

        return ListOperationResponse::from($response);
    }

    /**
     * @throws VerisoulApiException
     * @throws VerisoulConnectionException
     */
    public function getAllLists(): AccountListCollection
    {
        $response = $this->call(VerisoulApiEndpoint::ListGetAll);

        /** @var AccountListCollection $collection */
        $collection = AccountList::collect($response);

        return $collection;
    }

    /**
     * @throws VerisoulApiException
     * @throws VerisoulConnectionException
     */
    public function getList(string $listName): AccountList
    {
        $response = $this->call(
            VerisoulApiEndpoint::ListGet,
            ['list_name' => $listName]
        );

        return AccountList::from($response);
    }

    /**
     * @throws VerisoulApiException
     * @throws VerisoulConnectionException
     */
    public function addAccountToList(string $listName, string $accountId): ListOperationResponse
    {
        $response = $this->call(
            VerisoulApiEndpoint::ListAddAccount,
            ['list_name' => $listName, 'account_id' => $accountId],
        );

        return ListOperationResponse::from($response);
    }

    /**
     * @throws VerisoulApiException
     * @throws VerisoulConnectionException
     */
    public function deleteList(string $listName): ListOperationResponse
    {
        $response = $this->call(
            VerisoulApiEndpoint::ListDelete,
            ['list_name' => $listName]
        );

        return ListOperationResponse::from($response);
    }

    /**
     * @throws VerisoulApiException
     * @throws VerisoulConnectionException
     */
    public function removeAccountFromList(string $listName, string $accountId): ListOperationResponse
    {
        $response = $this->call(
            VerisoulApiEndpoint::ListRemoveAccount,
            ['list_name' => $listName, 'account_id' => $accountId]
        );

        return ListOperationResponse::from($response);
    }
}
