<?php

namespace Ninja\Larasoul\DTO;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Bag;
use Bag\Mappers\SnakeCase;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class RiskSignals extends Bag
{
    public function __construct(
        public bool $deviceRisk,
        public bool $proxy,
        public bool $vpn,
        public bool $datacenter,
        public bool $tor,
        public bool $spoofedIp,
        public bool $recentFraudIp,
        public bool $impossibleTravel,
        public bool $deviceNetworkMismatch,
    ) {}
}
