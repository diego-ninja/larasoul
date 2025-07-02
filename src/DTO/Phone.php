<?php

namespace Ninja\Larasoul\DTO;

use Bag\Bag;

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