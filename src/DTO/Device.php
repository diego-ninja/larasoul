<?php

namespace Ninja\Larasoul\DTO;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Bag;
use Bag\Mappers\SnakeCase;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class Device extends Bag
{
    public function __construct(
        public ?string $category,
        public ?string $type,
        public ?string $os,
        public ?int $cpuCores,
        public ?int $memory,
        public ?string $gpu,
        public ?float $screenHeight,
        public ?float $screenWidth,
    ) {}
}
