<?php

namespace Ninja\Larasoul\DTO;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Bag;
use Bag\Mappers\SnakeCase;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class Browser extends Bag
{
    public function __construct(
        public ?string $type,
        public ?string $version,
        public ?string $language,
        public ?string $userAgent,
        public ?string $timezone,
    ) {}
}
