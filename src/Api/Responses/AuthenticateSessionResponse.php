<?php

namespace Ninja\Larasoul\Api\Responses;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Mappers\SnakeCase;
use Ninja\Larasoul\Collections\LinkedAccountCollection;
use Ninja\Larasoul\DTO\Account;
use Ninja\Larasoul\DTO\Session;
use Ninja\Larasoul\Enums\VerisoulDecision;
use Ninja\Larasoul\ValueObjects\RiskScore;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class AuthenticateSessionResponse extends ApiResponse
{
    public function __construct(
        public string $projectId,
        public string $sessionId,
        public string $accountId,
        public string $requestId,
        public VerisoulDecision $decision,
        public RiskScore $accountScore,
        public float $bot,
        public float $multipleAccounts,
        public float $riskSignals,
        public int $accountsLinked,
        public array $lists,
        public Session $session,
        public Account $account,
        public ?LinkedAccountCollection $linkedAccounts,
    ) {}

    public function getRiskSignals(): array
    {
        return $this->session->riskSignals->toArray();
    }
}
