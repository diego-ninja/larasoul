<?php

namespace Ninja\Larasoul\DTO;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Bag;
use Bag\Mappers\SnakeCase;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class DeviceNetworkSignals extends Bag
{
    public function __construct(
        public float $deviceRisk,
        public float $proxy,
        public float $vpn,
        public float $datacenter,
        public float $tor,
        public float $spoofedIp,
        public float $recentFraudIp,
        public float $deviceNetworkMismatch,
        public float $locationSpoofing,
    ) {}
}
