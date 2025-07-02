<?php

namespace Ninja\Larasoul\DTO;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Bag;
use Bag\Mappers\SnakeCase;
use Carbon\Carbon;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class UserData extends Bag
{
    public function __construct(
        public ?string $firstName,
        public ?string $lastName,
        public ?Carbon $dateOfBirth,
        public ?Carbon $dateOfExpiration,
        public ?Carbon $dateOfIssue,
        public ?string $idNumber,
        public ?string $idNumber2,
        public Address $address,
    ) {}
}
