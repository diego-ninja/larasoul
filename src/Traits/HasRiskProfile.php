<?php

namespace Ninja\Larasoul\Traits;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Ninja\Larasoul\Enums\RiskLevel;
use Ninja\Larasoul\Enums\RiskStatus;
use Ninja\Larasoul\Enums\VerisoulDecision;
use Ninja\Larasoul\Models\RiskProfile;

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

    /**
     * Check if user is verified
     */
    public function isVerified(): bool
    {
        return $this->riskProfile?->isVerified() ?? false;
    }

    public function isExpired(): bool
    {
        return $this->riskProfile?->isExpired() ?? false;
    }

    /**
     * Check if user is fully verified (all verification types)
     */
    public function isFullyVerified(): bool
    {
        return $this->riskProfile?->isFullyVerified() ?? false;
    }

    /**
     * Check if user has face verification
     */
    public function hasFaceVerification(): bool
    {
        return $this->riskProfile?->hasFaceVerification() ?? false;
    }

    /**
     * Check if user has phone verification
     */
    public function hasPhoneVerification(): bool
    {
        return $this->riskProfile?->hasPhoneVerification() ?? false;
    }

    /**
     * Check if user has identity verification
     */
    public function hasIdentityVerification(): bool
    {
        return $this->riskProfile?->hasIdentityVerification() ?? false;
    }

    /**
     * Get user's risk score
     */
    public function getRiskScore(): float
    {
        return $this->riskProfile?->score ?? 1.0;
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
     * Get verification status
     */
    public function getRiskStatus(): RiskStatus
    {
        return $this->riskProfile?->status ?? RiskStatus::Pending;
    }

    /**
     * Get last risk check date
     */
    public function getLastRiskCheckDate(): ?string
    {
        return $this->riskProfile?->last_risk_check_at;
    }

    /**
     * Check if risk check is due
     */
    public function isRiskCheckDue(int $intervalDays = 30): bool
    {
        $lastCheck = $this->getLastRiskCheckDate();
        if (! $lastCheck) {
            return true;
        }

        return now()->diffInDays($lastCheck) >= $intervalDays;
    }

    /**
     * Scope: Only verified users
     */
    public function scopeVerified($query)
    {
        return $query->whereHas('riskProfile', function ($q) {
            $q->where('status', RiskStatus::Verified);
        });
    }

    /**
     * Scope: Users requiring manual review
     */
    public function scopeRequiresManualReview($query)
    {
        return $query->whereHas('riskProfile', function ($q) {
            $q->where('status', RiskStatus::ManualReview);
        });
    }

    /**
     * Scope: Only low risk users
     */
    public function scopeLowRisk($query)
    {
        return $query->whereHas('riskProfile', function ($q) {
            $q->where('score', '<=', 0.3);
        });
    }

    /**
     * Scope: Only high risk users
     */
    public function scopeHighRisk($query)
    {
        return $query->whereHas('riskProfile', function ($q) {
            $q->where('score', '>=', 0.7);
        });
    }

    /**
     * Scope: Users with phone verification
     */
    public function scopePhoneVerified($query)
    {
        return $query->whereHas('riskProfile', function ($q) {
            $q->whereNotNull('phone_verified_at');
        });
    }

    /**
     * Scope: Users with face verification
     */
    public function scopeFaceVerified($query)
    {
        return $query->whereHas('riskProfile', function ($q) {
            $q->whereNotNull('face_verified_at');
        });
    }

    /**
     * Scope: Users with face verification
     */
    public function scopeIdentityVerified($query)
    {
        return $query->whereHas('riskProfile', function ($q) {
            $q->whereNotNull('identity_verified_at');
        });
    }
}
