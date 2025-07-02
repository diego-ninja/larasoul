<?php

namespace Ninja\Larasoul\Api\Responses;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Mappers\SnakeCase;
use Ninja\Larasoul\DTO\Phone;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class VerifyPhoneResponse extends ApiResponse
{
    public function __construct(
        public string $projectId,
        public string $requestId,
        public Phone $phone,
    ) {
    }
}