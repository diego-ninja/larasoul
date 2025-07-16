<?php

namespace Ninja\Larasoul\DTO;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Bag;
use Bag\Mappers\SnakeCase;
use Carbon\Carbon;
use Ninja\Larasoul\Collections\RiskSignalCollection;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class Account extends Bag
{
    public function __construct(
        public UserAccount $account,
        public int $numSessions,
        public Carbon $firstSeen,
        public Carbon $lastSeen,
        public string $lastSession,
        public string $country,
        public array $countries,
        public UniqueValues $uniqueDevices,
        public UniqueValues $uniqueNetworks,
        public Email $email,
        public RiskSignalCollection $riskSignalAverage,
    ) {}
}
