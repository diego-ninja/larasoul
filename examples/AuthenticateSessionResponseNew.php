<?php

namespace Ninja\Larasoul\Api\Responses;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Mappers\SnakeCase;
use Ninja\Larasoul\Collections\LinkedAccountCollection;
use Ninja\Larasoul\Collections\RiskSignalCollection;
use Ninja\Larasoul\DTO\Account;
use Ninja\Larasoul\DTO\Session;
use Ninja\Larasoul\Enums\VerisoulDecision;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class AuthenticateSessionResponseNew extends ApiResponse
{
    public function __construct(
        public string $projectId,
        public string $sessionId,
        public string $accountId,
        public string $requestId,
        public VerisoulDecision $decision,
        public float $accountScore,
        public float $bot,
        public float $multipleAccounts,
        public float $riskSignals,
        public int $accountsLinked,
        public array $lists,
        public Session $session,
        public Account $account,
        public ?LinkedAccountCollection $linkedAccounts,
    ) {}

    /**
     * Get risk signals as the new RiskSignalCollection
     */
    public function getRiskSignals(): RiskSignalCollection
    {
        return RiskSignalCollection::fromLegacyDTOs(
            signals: $this->session->riskSignals,
            scores: $this->session->riskSignalScores,
            averages: $this->account->riskSignalAverage,
        );
    }

    /**
     * Get risk signals as array (for backward compatibility)
     */
    public function getRiskSignalsArray(): array
    {
        return $this->getRiskSignals()->toArray();
    }

    /**
     * Get risk signals summary
     */
    public function getRiskSignalsSummary(): array
    {
        return $this->getRiskSignals()->getSummary();
    }

    /**
     * Get flagged signals only
     */
    public function getFlaggedSignals(): RiskSignalCollection
    {
        return $this->getRiskSignals()->flagged();
    }

    /**
     * Get high risk signals only
     */
    public function getHighRiskSignals(): RiskSignalCollection
    {
        return $this->getRiskSignals()->highRisk();
    }

    /**
     * Get signals grouped by scope
     */
    public function getSignalsByScope(): array
    {
        return $this->getRiskSignals()->groupedByScope();
    }

    /**
     * Get overall weighted risk score
     */
    public function getWeightedRiskScore(): float
    {
        return $this->getRiskSignals()->getWeightedRiskScore();
    }

    /**
     * Check if response has any concerning signals
     */
    public function hasConcerningSignals(): bool
    {
        return $this->getRiskSignals()->hasFlaggedSignals() ||
               $this->getRiskSignals()->hasHighRiskSignals();
    }

    /**
     * Get the most critical signals
     */
    public function getMostCriticalSignals(int $limit = 3): RiskSignalCollection
    {
        return $this->getRiskSignals()->getMostCritical($limit);
    }

    /**
     * Get signals that significantly deviate from average
     */
    public function getSignificantDeviations(): RiskSignalCollection
    {
        return $this->getRiskSignals()->getSignificantDeviations();
    }
}
