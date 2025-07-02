<?php

namespace Ninja\Larasoul\DTO;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Bag;
use Bag\Mappers\Alias;
use Bag\Mappers\SnakeCase;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class UniqueValues extends Bag
{
    public function __construct(
        #[MapInputName(Alias::class,'1_day')]
        public int $lastDay,
        #[MapInputName(Alias::class,'7_day')]
        public int $lastWeek,
    ) {}
}