<?php

namespace Ninja\Larasoul\DTO;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Bag;
use Bag\Mappers\SnakeCase;
use Carbon\Carbon;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class Metadata extends Bag
{
    public function __construct(
        public string $projectId,
        public string $sessionId,
        public ?string $accountId,
        public ?string $referringSessionId,
        public string $requestId,
        public Carbon $timestamp,
    ) {}
}
