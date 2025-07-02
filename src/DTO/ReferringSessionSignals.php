<?php

namespace Ninja\Larasoul\DTO;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Bag;
use Bag\Mappers\SnakeCase;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class ReferringSessionSignals extends Bag
{
    public function __construct(
        public float $impossibleTravel,
        public float $ipMismatch,
        public float $userAgentMismatch,
        public float $deviceTimezoneMismatch,
        public float $ipTimezoneMismatch,
    ) {}
}
