<?php

namespace Ninja\Larasoul\DTO;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Bag;
use Bag\Mappers\SnakeCase;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class UserAccount extends Bag
{
    public function __construct(
        public string $id,
        public ?string $email = null,
        public ?array $metadata = [],
        public ?string $group = null,
    ) {}
}
