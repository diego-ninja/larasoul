<?php

namespace Ninja\Larasoul\DTO;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Bag;
use Bag\Mappers\SnakeCase;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class Bot extends Bag
{
    public function __construct(
        public int $mouseNumEvents,
        public int $clickNumEvents,
        public int $keyboardNumEvents,
        public int $touchNumEvents,
        public int $clipboardNumEvents,
    ) {}
}