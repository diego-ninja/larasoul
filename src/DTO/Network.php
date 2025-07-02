<?php

namespace Ninja\Larasoul\DTO;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Bag;
use Bag\Mappers\SnakeCase;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class Network extends Bag
{
    public function __construct(
        public ?string $ipAddress,
        public ?string $serviceProvider,
        public ?string $connectionType,
    ) {}
}
