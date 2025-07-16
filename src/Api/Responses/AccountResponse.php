<?php

namespace Ninja\Larasoul\Api\Responses;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Mappers\SnakeCase;
use Carbon\Carbon;
use Ninja\Larasoul\Collections\RiskSignalCollection;
use Ninja\Larasoul\DTO\Email;
use Ninja\Larasoul\DTO\UniqueValues;
use Ninja\Larasoul\DTO\UserAccount;
use Ninja\Larasoul\Enums\VerisoulDecision;
use Ninja\Larasoul\ValueObjects\RiskScore;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class AccountResponse extends ApiResponse
{
    public function __construct(
        public string $projectId,
        public string $requestId,
        public UserAccount $account,
        public int $numSessions,
        public Carbon $firstSeen,
        public Carbon $lastSeen,
        public string $lastSession,
        public VerisoulDecision $decision,
        public RiskScore $accountScore,
        public float $bot,
        public float $multipleAccounts,
        public float $riskSignals,
        public int $accountsLinked,
        public string $country,
        public array $countries,
        public array $lists,
        public UniqueValues $uniqueDevices,
        public UniqueValues $uniqueNetworks,
        public Email $email,
        public RiskSignalCollection $riskSignalAverage,
    ) {}
}
