<?php

namespace Ninja\Larasoul\Traits;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Ninja\Larasoul\Enums\RiskLevel;
use Ninja\Larasoul\Enums\VerisoulDecision;
use Ninja\Larasoul\Models\RiskProfile;
use Ninja\Larasoul\ValueObjects\RiskScore;

trait HasRiskProfile
{
    /**
     * Get the user's verification profile
     */
    public function riskProfile(): HasOne
    {
        return $this->hasOne(RiskProfile::class);
    }

    /**
     * Check if user has any verification
     */
    public function hasRiskProfile(): bool
    {
        return $this->riskProfile()->exists();
    }

    public function getRiskProfile(): ?RiskProfile
    {
        return $this->riskProfile()->first();
    }

    public function isExpired(): bool
    {
        return $this->riskProfile?->isExpired() ?? false;
    }

    /**
     * Check if user's risk profile is assessed
     */
    public function isRiskAssessed(): bool
    {
        return $this->riskProfile?->isAssessed() ?? false;
    }

    /**
     * Check if user's risk profile is expired
     */
    public function needsRiskAssessment(): bool
    {
        return $this->riskProfile?->isExpired() ?? false;
    }

    /**
     * Get user's risk score
     */
    public function getRiskScore(): ?RiskScore
    {
        return $this->riskProfile?->risk_score;
    }

    /**
     * Get user's risk level
     */
    public function getRiskLevel(): RiskLevel
    {
        return $this->riskProfile?->getRiskLevel() ?? RiskLevel::Unknown;
    }

    /**
     * Check if user is low risk
     */
    public function isLowRisk(): bool
    {
        return $this->getRiskLevel() === RiskLevel::Low;
    }

    /**
     * Check if user is medium risk
     */
    public function isMediumRisk(): bool
    {
        return $this->getRiskLevel() === RiskLevel::Medium;
    }

    /**
     * Check if user is high risk
     */
    public function isHighRisk(): bool
    {
        return $this->getRiskLevel() === RiskLevel::High;
    }

    /**
     * Get user's verification decision
     */
    public function getDecision(): ?VerisoulDecision
    {
        return $this->riskProfile?->decision;
    }

    /**
     * Check if user's verification is real
     */
    public function isReal(): bool
    {
        return $this->getDecision() === VerisoulDecision::Real;
    }

    /**
     * Check if user's verification is fake
     */
    public function isFake(): bool
    {
        return $this->getDecision() === VerisoulDecision::Fake;
    }

    /**
     * Check if user's verification is suspicious
     */
    public function isSuspicious(): bool
    {
        return $this->getDecision() === VerisoulDecision::Suspicious;
    }

    /**
     * Check if user requires manual review
     */
    public function requiresManualReview(): bool
    {
        return $this->riskProfile?->requiresManualReview() ?? false;
    }

    /**
     * Get risk assessment date
     */
    public function getRiskAssessmentDate(): ?string
    {
        return $this->riskProfile?->assessed_at;
    }

    /**
     * Check if risk assessment is due
     */
    public function isRiskAssessmentDue(): bool
    {
        return $this->riskProfile?->needsAssessment() ?? true;
    }

    /**
     * Get user's risk signals as RiskSignalCollection
     */
    public function getRiskSignals(): ?\Ninja\Larasoul\Collections\RiskSignalCollection
    {
        return $this->riskProfile?->risk_signals;
    }

    /**
     * Scope: Only users with assessed risk profiles
     */
    public function scopeRiskAssessed($query)
    {
        return $query->whereHas('riskProfile', function ($q) {
            $q->whereNotNull('assessed_at');
        });
    }

    /**
     * Scope: Users with real risk profiles
     */
    public function scopeRealRisk($query)
    {
        return $query->whereHas('riskProfile', function ($q) {
            $q->where('decision', VerisoulDecision::Real);
        });
    }

    /**
     * Scope: Users with fake risk profiles
     */
    public function scopeFakeRisk($query)
    {
        return $query->whereHas('riskProfile', function ($q) {
            $q->where('decision', VerisoulDecision::Fake);
        });
    }

    /**
     * Scope: Users with suspicious risk profiles
     */
    public function scopeSuspiciousRisk($query)
    {
        return $query->whereHas('riskProfile', function ($q) {
            $q->where('decision', VerisoulDecision::Suspicious);
        });
    }

    /**
     * Scope: Only low risk users
     */
    public function scopeLowRisk($query)
    {
        return $query->whereHas('riskProfile', function ($q) {
            $q->where('risk_score', '<=', config('larasoul.verification.risk_thresholds.low'));
        });
    }

    /**
     * Scope: Only high risk users
     */
    public function scopeHighRisk($query)
    {
        return $query->whereHas('riskProfile', function ($q) {
            $q->where('risk_score', '>=', config('larasoul.verification.risk_thresholds.medium'));
        });
    }

    /**
     * Scope: Users with expired risk profiles
     */
    public function scopeExpiredRisk($query)
    {
        return $query->whereHas('riskProfile', function ($q) {
            $q->where('expires_at', '<', now());
        });
    }

    /**
     * Scope: Users needing risk assessment
     */
    public function scopeNeedsRiskAssessment($query)
    {
        return $query->whereHas('riskProfile', function ($q) {
            $q->whereNull('assessed_at')
                ->orWhere('expires_at', '<=', now());
        });
    }
}
