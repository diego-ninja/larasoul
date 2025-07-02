<?php

namespace Ninja\Larasoul\Api\Responses;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Mappers\SnakeCase;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class VerifyIdentityResponse extends ApiResponse
{
    public function __construct(
        public string $requestId,
        public bool $success,
        public bool $match,
    ) {}
}
