<?php

namespace Ninja\Larasoul\Api\Responses;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Mappers\SnakeCase;
use Illuminate\Support\Collection;
use Ninja\Larasoul\Collections\RiskSignalCollection;
use Ninja\Larasoul\DTO\DeviceNetworkSignals;
use Ninja\Larasoul\DTO\Matches;
use Ninja\Larasoul\DTO\Metadata;
use Ninja\Larasoul\DTO\PhotoUrls;
use Ninja\Larasoul\DTO\ReferringSessionSignals;
use Ninja\Larasoul\DTO\SessionData;
use Ninja\Larasoul\Enums\RiskFlag;
use Ninja\Larasoul\Enums\RiskLevel;
use Ninja\Larasoul\Enums\VerisoulDecision;
use Ninja\Larasoul\ValueObjects\RiskScore;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class VerifyFaceResponse extends ApiResponse
{
    /**
     * @param  Collection<RiskFlag>  $riskFlags
     */
    public function __construct(
        public Metadata $metadata,
        public VerisoulDecision $decision,
        public RiskScore $riskScore,
        public Collection $riskFlags,
        public DeviceNetworkSignals $deviceNetworkSignals,
        public ReferringSessionSignals $referringSessionSignals,
        public PhotoUrls $photoUrls,
        public SessionData $sessionData,
        public Matches $matches,
    ) {}

    /**
     * Check if verification was successful
     */
    public function isSuccessful(): bool
    {
        return $this->decision === VerisoulDecision::Real &&
            $this->riskScore->isLow() &&
            ! $this->hasBlockingRiskFlags();
    }

    /**
     * Check if verification should be rejected
     */
    public function shouldReject(): bool
    {
        return $this->decision === VerisoulDecision::Fake ||
            $this->riskScore->isHigh() ||
            $this->hasBlockingRiskFlags();
    }

    /**
     * Check if verification requires manual review
     */
    public function requiresManualReview(): bool
    {
        return $this->decision === VerisoulDecision::Suspicious ||
            $this->riskScore->isBetween(0.4, 0.8) ||
            $this->hasModerateRiskFlags();
    }

    /**
     * Check if has blocking risk flags
     */
    public function hasBlockingRiskFlags(): bool
    {
        return $this->riskFlags->some(fn (RiskFlag $flag) => $flag->shouldBlock());
    }

    /**
     * Check if has moderate risk flags that require review
     */
    public function hasModerateRiskFlags(): bool
    {
        return $this->riskFlags->some(fn (RiskFlag $flag) => $flag->getRiskLevel() === RiskLevel::Medium);
    }

    /**
     * Get risk flags by category
     */
    public function getRiskFlagsByCategory(): array
    {
        $categories = [];
        foreach ($this->riskFlags as $flag) {
            $category = $flag->getCategory();
            if (! isset($categories[$category])) {
                $categories[$category] = [];
            }
            $categories[$category][] = $flag;
        }

        return $categories;
    }

    /**
     * Get risk flags by level
     */
    public function getRiskFlagsByLevel(): array
    {
        $levels = [];
        $this->riskFlags->each(function (RiskFlag $flag) use (&$levels) {
            $level = $flag->getRiskLevel();
            if (! isset($levels[$level->value])) {
                $levels[$level->value] = [];
            }
            $levels[$level->value][] = $flag;
        });

        return $levels;
    }

    /**
     * Check if specific risk flag is present
     */
    public function hasRiskFlag(RiskFlag $flag): bool
    {
        return $this->riskFlags->contains(fn (RiskFlag $riskFlag) => $riskFlag === $flag);
    }

    /**
     * Get risk flags as string array
     */
    public function getRiskFlagsAsStrings(): array
    {
        return $this->riskFlags->map(fn (RiskFlag $flag) => $flag->value)->toArray();
    }

    /**
     * Get all risk signals as a unified collection
     */
    public function getRiskSignals(): RiskSignalCollection
    {
        return RiskSignalCollection::fromVerisoulSignals(
            deviceNetworkSignals: $this->deviceNetworkSignals,
            referringSessionSignals: $this->referringSessionSignals
        );
    }
}
