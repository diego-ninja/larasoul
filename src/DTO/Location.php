<?php

namespace Ninja\Larasoul\DTO;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Bag;
use Bag\Mappers\SnakeCase;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class Location extends Bag
{
    public function __construct(
        public ?string $continent,
        public ?string $countryCode,
        public ?string $state,
        public ?string $city,
        public ?string $zipCode,
        public ?string $timezone,
        public ?float $latitude,
        public ?float $longitude,
    ) {}
}
