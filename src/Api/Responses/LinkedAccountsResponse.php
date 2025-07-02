<?php

namespace Ninja\Larasoul\Api\Responses;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Mappers\SnakeCase;
use Ninja\Larasoul\Collections\LinkedAccountCollection;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class LinkedAccountsResponse extends ApiResponse
{
    public function __construct(
        public string $requestId,
        public LinkedAccountCollection $accountsLinked,
    ) {}
}