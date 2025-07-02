<?php

namespace Ninja\Larasoul\Api\Responses;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Mappers\SnakeCase;
use Illuminate\Support\Collection;
use Ninja\Larasoul\DTO\DeviceNetworkSignals;
use Ninja\Larasoul\DTO\Document;
use Ninja\Larasoul\DTO\DocumentSignals;
use Ninja\Larasoul\DTO\Matches;
use Ninja\Larasoul\DTO\Metadata;
use Ninja\Larasoul\DTO\PhotoUrls;
use Ninja\Larasoul\DTO\ReferringSessionSignals;
use Ninja\Larasoul\DTO\SessionData;
use Ninja\Larasoul\Enums\RiskFlag;
use Ninja\Larasoul\Enums\VerisoulDecision;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class VerifyIdResponse extends ApiResponse
{
    /**
     * @param Collection<RiskFlag> $riskFlags
     */
    public function __construct(
        public Metadata $metadata,
        public VerisoulDecision $decision,
        public float $riskScore,
        public Collection $riskFlags,
        public DocumentSignals $documentSignals,
        public Document $documentData,
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
        return VerisoulDecision::Real === $this->decision &&
            $this->riskScore <= 0.3 &&
            !$this->hasBlockingRiskFlags();
    }

    /**
     * Check if verification should be rejected
     */
    public function shouldReject(): bool
    {
        return VerisoulDecision::Fake === $this->decision ||
            $this->riskScore >= 0.8 ||
            $this->hasBlockingRiskFlags();
    }

    /**
     * Check if verification requires manual review
     */
    public function requiresManualReview(): bool
    {
        return VerisoulDecision::Suspicious === $this->decision ||
            ($this->riskScore >= 0.4 && $this->riskScore < 0.8) ||
            $this->hasModerateRiskFlags();
    }

    /**
     * Check if has blocking risk flags
     */
    public function hasBlockingRiskFlags(): bool
    {
        return $this->riskFlags->some(fn($flag) => $flag->shouldBlock());
    }

    /**
     * Check if has moderate risk flags that require review
     */
    public function hasModerateRiskFlags(): bool
    {
        return $this->riskFlags->some(fn($flag) => $flag->getRiskLevel() === 'medium');
    }

    /**
     * Get risk flags by category
     */
    public function getRiskFlagsByCategory(): array
    {
        $categories = [];
        foreach ($this->riskFlags as $flag) {
            $category = $flag->getCategory();
            if (!isset($categories[$category])) {
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
            if (!isset($levels[$level])) {
                $levels[$level] = [];
            }
            $levels[$level][] = $flag;
        });

        return $levels;
    }

    /**
     * Check if specific risk flag is present
     */
    public function hasRiskFlag(RiskFlag $flag): bool
    {
        return $this->riskFlags->contains(fn(RiskFlag $riskFlag) => $riskFlag === $flag);
    }

    /**
     * Get risk flags as string array
     */
    public function getRiskFlagsAsStrings(): array
    {
        return $this->riskFlags->map(fn(RiskFlag $flag) => $flag->value)->toArray();
    }
}
