<?php

namespace Ninja\Larasoul\DTO;

use Bag\Attributes\Collection;
use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Bag;
use Bag\Mappers\SnakeCase;
use Ninja\Larasoul\Collections\AccountListCollection;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
#[Collection(AccountListCollection::class)]
final readonly class AccountList extends Bag
{
    public function __construct(
        public string $name,
        public ?string $description,
    ) {}
}
