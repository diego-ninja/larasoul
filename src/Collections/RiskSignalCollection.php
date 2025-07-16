<?php

namespace Ninja\Larasoul\Collections;

use Bag\Collection;
use Ninja\Larasoul\DTO\DeviceNetworkSignals;
use Ninja\Larasoul\DTO\DocumentSignals;
use Ninja\Larasoul\DTO\ReferringSessionSignals;
use Ninja\Larasoul\DTO\RiskSignal;
use Ninja\Larasoul\Enums\SignalScope;
use Ninja\Larasoul\ValueObjects\RiskScore;

/**
 * Collection of RiskSignal DTOs
 *
 * This collection replaces the individual RiskSignals and RiskSignalScore DTOs
 * providing a more flexible and structured approach to handling risk signals.
 */
final class RiskSignalCollection extends Collection
{
    /**
     * Create collection from array of signal data
     */
    public static function fromArray(array $data): self
    {
        $collection = new self;

        foreach ($data as $signalData) {
            if (is_array($signalData)) {
                if ($signalData['score'] > 0) {
                    $collection->addSignal(
                        name: $signalData['name'] ?? 'unknown',
                        score: $signalData['score'],
                        scope: isset($signalData['scope'])
                            ? SignalScope::from($signalData['scope'])
                            : SignalScope::DeviceNetwork,
                    );
                }
            } elseif ($signalData instanceof RiskSignal) {
                if ($signalData->score->value() > 0) {
                    // Only add signals with a positive score
                    $collection->add($signalData);
                }
            }
        }

        return $collection;
    }

    /**
     * Get signals by scope
     */
    public function byScope(SignalScope $scope): self
    {
        return $this->filter(fn (RiskSignal $signal) => $signal->scope === $scope);
    }

    /**
     * Get flagged signals (score > threshold)
     */
    public function flagged(float $threshold = 0.5): self
    {
        return $this->filter(fn (RiskSignal $signal) => $signal->isFlagged($threshold));
    }

    /**
     * Get high risk signals (score > threshold)
     */
    public function highRisk(float $threshold = 0.8): self
    {
        return $this->filter(fn (RiskSignal $signal) => $signal->isHighRisk($threshold));
    }

    /**
     * Get signals by name
     */
    public function byName(string $name): ?RiskSignal
    {
        return $this->first(fn (RiskSignal $signal) => $signal->name === $name);
    }

    /**
     * Get signals by names
     */
    public function byNames(array $names): self
    {
        return $this->filter(fn (RiskSignal $signal) => in_array($signal->name, $names));
    }

    /**
     * Get overall risk score (average of all signals)
     */
    public function getOverallRiskScore(): RiskScore
    {
        if ($this->isEmpty()) {
            return RiskScore::from(0.0);
        }

        return RiskScore::from($this->avg(fn (RiskSignal $signal) => $signal->score->value()));
    }

    /**
     * Get weighted risk score (different weights for different scopes)
     */
    public function getWeightedRiskScore(array $scopeWeights = []): RiskScore
    {
        if ($this->isEmpty()) {
            return RiskScore::from(0.0);
        }

        $defaultWeights = [
            SignalScope::DeviceNetwork->value => 0.3,
            SignalScope::Document->value => 0.3,
            SignalScope::ReferringSession->value => 0.2,
            SignalScope::Account->value => 0.1,
            SignalScope::Session->value => 0.1,
        ];

        $weights = array_merge($defaultWeights, $scopeWeights);
        $totalWeight = 0;
        $weightedSum = 0;

        foreach ($this as $signal) {
            $weight = $weights[$signal->scope->value] ?? 0.1;
            $weightedSum += $signal->score->value() * $weight;
            $totalWeight += $weight;
        }

        return RiskScore::from($totalWeight > 0 ? $weightedSum / $totalWeight : 0.0);
    }

    /**
     * Get signals grouped by scope
     */
    public function groupedByScope(): array
    {
        return $this->groupBy(fn (RiskSignal $signal) => $signal->scope->value)->toArray();
    }

    /**
     * Get signals grouped by risk level
     */
    public function groupedByRiskLevel(): array
    {
        return $this->groupBy(fn (RiskSignal $signal) => $signal->getRiskLevel())->toArray();
    }

    /**
     * Get summary statistics
     */
    public function getSummary(): array
    {
        if ($this->isEmpty()) {
            return [
                'total_signals' => 0,
                'flagged_signals' => 0,
                'high_risk_signals' => 0,
                'above_average_signals' => 0,
                'overall_risk_score' => 0.0,
                'weighted_risk_score' => 0.0,
                'max_score' => 0.0,
                'min_score' => 0.0,
                'avg_score' => 0.0,
            ];
        }

        return [
            'total_signals' => $this->count(),
            'flagged_signals' => $this->flagged()->count(),
            'high_risk_signals' => $this->highRisk()->count(),
            'overall_risk_score' => $this->getOverallRiskScore(),
            'weighted_risk_score' => $this->getWeightedRiskScore(),
            'max_score' => $this->max(fn (RiskSignal $signal) => $signal->score->value()),
            'min_score' => $this->min(fn (RiskSignal $signal) => $signal->score->value()),
            'avg_score' => $this->avg(fn (RiskSignal $signal) => $signal->score->value()),
            'by_scope' => $this->groupedByScope(),
            'by_risk_level' => $this->groupedByRiskLevel(),
        ];
    }

    /**
     * Get the most critical signals (top N by score)
     */
    public function getMostCritical(int $limit = 5): self
    {
        return $this->sortByDesc(fn (RiskSignal $signal) => $signal->score->value())->take($limit);
    }

    /**
     * Check if collection has any flagged signals
     */
    public function hasFlaggedSignals(float $threshold = 0.5): bool
    {
        return $this->flagged($threshold)->isNotEmpty();
    }

    /**
     * Check if collection has any high risk signals
     */
    public function hasHighRiskSignals(float $threshold = 0.8): bool
    {
        return $this->highRisk($threshold)->isNotEmpty();
    }

    /**
     * Convert to array for JSON serialization
     */
    public function toArray(): array
    {
        return $this->map(fn (RiskSignal $signal) => $signal->toArray())->toArray();
    }

    /**
     * Convert to legacy RiskSignals format (boolean flags)
     */
    public function toLegacyRiskSignals(): array
    {
        $result = [];

        foreach ($this as $signal) {
            $key = str_replace('_', '', ucwords($signal->name, '_'));
            $key = lcfirst($key);
            $result[$key] = $signal->isFlagged();
        }

        return $result;
    }

    /**
     * Convert to legacy RiskSignalScore format (float scores)
     */
    public function toLegacyRiskSignalScores(): array
    {
        $result = [];

        foreach ($this as $signal) {
            $key = str_replace('_', '', ucwords($signal->name, '_'));
            $key = lcfirst($key);
            $result[$key] = $signal->score->value();
        }

        return $result;
    }

    /**
     * Add a new signal to the collection
     */
    public function addSignal(string $name, RiskScore|float $score, ?SignalScope $scope = null): self
    {
        $score = RiskScore::from($score);

        if ($score->isPositive()) {
            $this->add(new RiskSignal(
                name: $name,
                score: $score,
                scope: $scope ?? SignalScope::DeviceNetwork,
            ));
        }

        return $this;
    }

    /**
     * Update an existing signal or add if not exists
     */
    public function updateSignal(string $name, float $score, ?float $average = null): self
    {
        $index = $this->search(fn (RiskSignal $signal) => $signal->name === $name);
        $score = RiskScore::from($score);

        if ($index !== false) {
            $existingSignal = $this->get($index);
            $this->put($index, new RiskSignal(
                name: $name,
                score: $score,
                scope: $existingSignal->scope,
            ));
        } else {
            return $this->addSignal($name, $score, $average);
        }

        return $this;
    }

    /**
     * Remove a signal by name
     */
    public function removeSignal(string $name): self
    {
        return $this->reject(fn (RiskSignal $signal) => $signal->name === $name);
    }

    /**
     * Create collection from DeviceNetworkSignals DTO
     */
    public static function fromDeviceNetworkSignals(DeviceNetworkSignals $signals): self
    {
        $collection = new self;

        $collection->addSignal('device_risk', $signals->deviceRisk, scope: SignalScope::DeviceNetwork);
        $collection->addSignal('proxy', $signals->proxy, scope: SignalScope::DeviceNetwork);
        $collection->addSignal('vpn', $signals->vpn, scope: SignalScope::DeviceNetwork);
        $collection->addSignal('datacenter', $signals->datacenter, scope: SignalScope::DeviceNetwork);
        $collection->addSignal('tor', $signals->tor, scope: SignalScope::DeviceNetwork);
        $collection->addSignal('spoofed_ip', $signals->spoofedIp, scope: SignalScope::DeviceNetwork);
        $collection->addSignal('recent_fraud_ip', $signals->recentFraudIp, scope: SignalScope::DeviceNetwork);
        $collection->addSignal('device_network_mismatch', $signals->deviceNetworkMismatch, scope: SignalScope::DeviceNetwork);
        $collection->addSignal('location_spoofing', $signals->locationSpoofing, scope: SignalScope::DeviceNetwork);

        return $collection;
    }

    /**
     * Create collection from DocumentSignals DTO
     */
    public static function fromDocumentSignals(DocumentSignals $signals): self
    {
        $collection = new self;

        $collection->addSignal('id_age', (float) $signals->idAge, scope: SignalScope::Document);
        $collection->addSignal('id_face_match_score', $signals->idFaceMatchScore, scope: SignalScope::Document);
        // Note: Other document signals are enums, not scores, so we don't include them here

        return $collection;
    }

    /**
     * Create collection from ReferringSessionSignals DTO
     */
    public static function fromReferringSessionSignals(ReferringSessionSignals $signals): self
    {
        $collection = new self;

        $collection->addSignal('impossible_travel', $signals->impossibleTravel, scope: SignalScope::ReferringSession);
        $collection->addSignal('ip_mismatch', $signals->ipMismatch, scope: SignalScope::ReferringSession);
        $collection->addSignal('user_agent_mismatch', $signals->userAgentMismatch, scope: SignalScope::ReferringSession);
        $collection->addSignal('device_timezone_mismatch', $signals->deviceTimezoneMismatch, scope: SignalScope::ReferringSession);
        $collection->addSignal('ip_timezone_mismatch', $signals->ipTimezoneMismatch, scope: SignalScope::ReferringSession);

        return $collection;
    }

    /**
     * Create comprehensive collection from all signal DTOs
     */
    public static function fromVerisoulSignals(
        ?DeviceNetworkSignals $deviceNetworkSignals = null,
        ?DocumentSignals $documentSignals = null,
        ?ReferringSessionSignals $referringSessionSignals = null
    ): self {
        $collection = new self;

        if ($deviceNetworkSignals) {
            $collection = $collection->merge(self::fromDeviceNetworkSignals($deviceNetworkSignals));
        }

        if ($documentSignals) {
            $collection = $collection->merge(self::fromDocumentSignals($documentSignals));
        }

        if ($referringSessionSignals) {
            $collection = $collection->merge(self::fromReferringSessionSignals($referringSessionSignals));
        }

        return $collection;
    }
}
