<?php

namespace Ninja\Larasoul\DTO;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Bag;
use Bag\Mappers\SnakeCase;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class RiskSignalScore extends Bag
{
    public function __construct(
        public ?float $deviceRisk = 0.0,
        public ?float $proxy = 0.0,
        public ?float $vpn = 0.0,
        public ?float $datacenter = 0.0,
        public ?float $tor = 0.0,
        public ?float $spoofedIp = 0.0,
        public ?float $recentFraudIp = 0.0,
        public ?float $impossibleTravel = 0.0,
        public ?float $deviceNetworkMismatch = 0.0,
    ) {}
}