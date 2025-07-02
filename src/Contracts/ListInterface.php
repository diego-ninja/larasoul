<?php

namespace Ninja\Larasoul\Contracts;

use Ninja\Larasoul\Api\Responses\CreateListResponse;
use Ninja\Larasoul\Api\Responses\DeleteListResponse;
use Ninja\Larasoul\Collections\AccountListCollection;
use Ninja\Larasoul\DTO\AccountList;

interface ListInterface
{
    /**
     * Create new list
     */
    public function createList(string $name, string $description): CreateListResponse;

    /**
     * Get all lists
     */
    public function getAllLists(): AccountListCollection;

    /**
     * Get accounts in list
     */
    public function getList(string $listName): AccountList;

    /**
     * Add account to list
     */
    public function addAccountToList(string $listName, string $accountId, array $data = []): array;

    /**
     * Delete list
     */
    public function deleteList(string $listName): DeleteListResponse;

    /**
     * Remove account from list
     */
    public function removeAccountFromList(string $listName, string $accountId): array;
}