<?php

namespace Ninja\Larasoul\Api\Responses;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Mappers\SnakeCase;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class ListOperationResponse extends ApiResponse
{
    public function __construct(
        public string $requestId,
        public string $message,
        public bool $success,
    ) {}
}
