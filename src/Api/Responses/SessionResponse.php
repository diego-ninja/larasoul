<?php

namespace Ninja\Larasoul\Api\Responses;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Mappers\SnakeCase;
use Carbon\Carbon;
use Ninja\Larasoul\Collections\RiskSignalCollection;
use Ninja\Larasoul\DTO\Bot;
use Ninja\Larasoul\DTO\Browser;
use Ninja\Larasoul\DTO\Device;
use Ninja\Larasoul\DTO\Location;
use Ninja\Larasoul\DTO\Network;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class SessionResponse extends ApiResponse
{
    public function __construct(
        public array $accountIds,
        public string $requestId,
        public string $projectId,
        public string $sessionId,
        public Carbon $startTime,
        public string $trueCountryCode,
        public Network $network,
        public Location $location,
        public Browser $browser,
        public Device $device,
        public RiskSignalCollection $riskSignals,
        public Bot $bot,
    ) {}
}
