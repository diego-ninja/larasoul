<?php

namespace Ninja\Larasoul\Api\Responses;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Mappers\SnakeCase;
use Ninja\Larasoul\DTO\Session;
use Ninja\Larasoul\Enums\VerisoulDecision;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class UnauthenticatedSessionResponse extends ApiResponse
{
    public function __construct(
        public string $projectId,
        public string $sessionId,
        public string $requestId,
        public VerisoulDecision $decision,
        public float $accountScore,
        public float $bot,
        public float $multipleAccounts,
        public float $riskSignals,
        public int $accountsLinked,
        public array $lists,
        public Session $session,
    ) {}
}
