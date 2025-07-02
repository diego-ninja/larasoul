<?php

namespace Ninja\Larasoul\DTO;

use Bag\Attributes\Collection;
use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Bag;
use Bag\Mappers\SnakeCase;
use Ninja\Larasoul\Collections\LinkedAccountCollection;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
#[Collection(LinkedAccountCollection::class)]
final readonly class LinkedAccount extends Bag
{
    public function __construct(
        public string $accountId,
        public float $score,
        public string $email,
        public array $matchType,
        public array $lists,
        public array $metadata,
    ) {}
}
