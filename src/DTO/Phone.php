<?php

namespace Ninja\Larasoul\DTO;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Bag;
use Bag\Mappers\SnakeCase;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class Phone extends Bag
{
    public function __construct(
        public bool $valid,
        public string $phoneNumber,
        public string $callingCountryCode,
        public string $countryCode,
        public string $carrierName,
        public string $lineType,
    ) {}
}
