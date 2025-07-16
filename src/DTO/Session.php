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
final readonly class Session extends Bag
{
    public function __construct(
        public ?Carbon $startTime,
        public ?string $trueCountryCode,
        public Network $network,
        public Location $location,
        public Browser $browser,
        public Device $device,
        public Bot $bot,
        public RiskSignalCollection $riskSignals,
    ) {}
}
