<?php

namespace Ninja\Larasoul\DTO;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Bag;
use Bag\Mappers\SnakeCase;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class AccountMatch extends Bag
{
    public function __construct(
        public string $accountId,
        public array $matchTypes,
    ) {}

    /**
     * Check if has face match
     */
    public function hasFaceMatch(): bool
    {
        return in_array('face', $this->matchTypes);
    }

    /**
     * Check if has ID match
     */
    public function hasIdMatch(): bool
    {
        return in_array('id', $this->matchTypes) || in_array('document', $this->matchTypes);
    }

    /**
     * Check if has device match
     */
    public function hasDeviceMatch(): bool
    {
        return in_array('device', $this->matchTypes);
    }

    /**
     * Check if has email match
     */
    public function hasEmailMatch(): bool
    {
        return in_array('email', $this->matchTypes);
    }

    /**
     * Check if has phone match
     */
    public function hasPhoneMatch(): bool
    {
        return in_array('phone', $this->matchTypes);
    }

    /**
     * Check if is exact match (multiple match types)
     */
    public function isExactMatch(): bool
    {
        return count($this->matchTypes) >= 2;
    }

    /**
     * Check if is high-confidence match
     */
    public function isHighConfidenceMatch(): bool
    {
        $highConfidenceTypes = ['face', 'id', 'document'];

        return array_any($this->matchTypes, fn ($type) => in_array($type, $highConfidenceTypes));
    }

    /**
     * Get match risk level
     */
    public function getMatchRiskLevel(): string
    {
        if ($this->hasFaceMatch() && $this->hasIdMatch()) {
            return 'very_high';
        }

        if ($this->hasFaceMatch() || $this->hasIdMatch()) {
            return 'high';
        }

        if ($this->hasDeviceMatch()) {
            return 'medium';
        }

        if ($this->hasEmailMatch() || $this->hasPhoneMatch()) {
            return 'low';
        }

        return 'very_low';
    }

    /**
     * Get match type priorities (for sorting)
     */
    public function getMatchPriority(): int
    {
        $priorities = [
            'face' => 100,
            'id' => 90,
            'document' => 90,
            'device' => 70,
            'email' => 50,
            'phone' => 40,
        ];

        $maxPriority = 0;
        foreach ($this->matchTypes as $type) {
            $maxPriority = max($maxPriority, $priorities[$type] ?? 0);
        }

        return $maxPriority;
    }

    /**
     * Get match summary
     */
    public function getMatchSummary(): array
    {
        return [
            'account_id' => $this->accountId,
            'match_types' => $this->matchTypes,
            'match_count' => count($this->matchTypes),
            'risk_level' => $this->getMatchRiskLevel(),
            'is_exact_match' => $this->isExactMatch(),
            'is_high_confidence' => $this->isHighConfidenceMatch(),
            'has_biometric_match' => $this->hasFaceMatch(),
            'has_document_match' => $this->hasIdMatch(),
            'priority' => $this->getMatchPriority(),
        ];
    }
}
