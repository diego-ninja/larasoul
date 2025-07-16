<?php

namespace Ninja\Larasoul\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Ninja\Larasoul\Enums\VerificationStatus;
use Ninja\Larasoul\Enums\VerificationType;
use Ninja\Larasoul\Enums\VerisoulDecision;
use Ninja\Larasoul\Models\UserVerification;
use Ninja\Larasoul\ValueObjects\RiskScore;

trait HasUserVerifications
{
    /**
     * Get all user verifications
     */
    public function userVerifications(): HasMany
    {
        return $this->hasMany(UserVerification::class);
    }

    /**
     * Get verification by type
     */
    public function getVerification(VerificationType $type): ?UserVerification
    {
        return $this->userVerifications()
            ->where('type', $type)
            ->first();
    }

    /**
     * Get or create verification by type
     */
    public function getOrCreateVerification(VerificationType $type): UserVerification
    {
        return $this->userVerifications()
            ->firstOrCreate(
                ['type' => $type],
                ['status' => VerificationStatus::Pending]
            );
    }

    /**
     * Check if user has any verification of type
     */
    public function hasVerification(VerificationType $type): bool
    {
        return $this->userVerifications()
            ->where('type', $type)
            ->exists();
    }

    /**
     * Check if user has verified verification of type
     */
    public function hasVerifiedVerification(VerificationType $type): bool
    {
        return $this->userVerifications()
            ->where('type', $type)
            ->where('status', VerificationStatus::Verified)
            ->where('decision', VerisoulDecision::Real)
            ->exists();
    }

    /**
     * Check if user has face verification
     */
    public function hasFaceVerification(): bool
    {
        return $this->hasVerifiedVerification(VerificationType::Face);
    }

    /**
     * Check if user has phone verification
     */
    public function hasPhoneVerification(): bool
    {
        return $this->hasVerifiedVerification(VerificationType::Phone);
    }

    /**
     * Check if user has identity verification
     */
    public function hasIdentityVerification(): bool
    {
        return $this->hasVerifiedVerification(VerificationType::Identity);
    }

    /**
     * Check if user is fully verified (all verification types)
     */
    public function isFullyVerified(): bool
    {
        return $this->hasFaceVerification() &&
            $this->hasPhoneVerification() &&
            $this->hasIdentityVerification();
    }

    /**
     * Get all verified verification types
     */
    public function getVerifiedTypes(): Collection
    {
        return $this->userVerifications()
            ->where('status', VerificationStatus::Verified)
            ->where('decision', VerisoulDecision::Real)
            ->get()
            ->pluck('type');
    }

    /**
     * Get all pending verifications
     */
    public function getPendingVerifications(): Collection
    {
        return $this->userVerifications()
            ->where('status', VerificationStatus::Pending)
            ->get();
    }

    /**
     * Get all failed verifications
     */
    public function getFailedVerifications(): Collection
    {
        return $this->userVerifications()
            ->where('status', VerificationStatus::Failed)
            ->get();
    }

    /**
     * Get all expired verifications
     */
    public function getExpiredVerifications(): Collection
    {
        return $this->userVerifications()
            ->where('expires_at', '<', now())
            ->get();
    }

    /**
     * Get verifications about to expire
     */
    public function getExpiringSoonVerifications(int $warningDays = 7): Collection
    {
        return $this->userVerifications()
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays($warningDays))
            ->get();
    }

    /**
     * Check if user has any expired verifications
     */
    public function hasExpiredVerifications(): bool
    {
        return $this->userVerifications()
            ->where('expires_at', '<', now())
            ->exists();
    }

    /**
     * Check if user has any verifications expiring soon
     */
    public function hasExpiringSoonVerifications(int $warningDays = 7): bool
    {
        return $this->userVerifications()
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays($warningDays))
            ->exists();
    }

    /**
     * Get verification completion percentage
     */
    public function getVerificationCompletionPercentage(): float
    {
        $totalTypes = count(VerificationType::cases());
        $verifiedTypes = $this->getVerifiedTypes()->count();

        if ($totalTypes === 0) {
            return 0;
        }

        return round(($verifiedTypes / $totalTypes) * 100, 2);
    }

    /**
     * Get latest verification for type
     */
    public function getLatestVerification(VerificationType $type): ?UserVerification
    {
        return $this->userVerifications()
            ->where('type', $type)
            ->latest('created_at')
            ->first();
    }

    /**
     * Create new verification
     */
    public function createVerification(
        VerificationType $type,
        VerificationStatus $status = VerificationStatus::Pending
    ): UserVerification {
        return $this->userVerifications()->create([
            'type' => $type,
            'status' => $status,
        ]);
    }

    /**
     * Mark verification as verified
     */
    public function markVerificationAsVerified(VerificationType $type): UserVerification
    {
        $verification = $this->getOrCreateVerification($type);
        $verification->markAsVerified();

        return $verification;
    }

    /**
     * Mark verification as failed
     */
    public function markVerificationAsFailed(VerificationType $type): UserVerification
    {
        $verification = $this->getOrCreateVerification($type);
        $verification->markAsFailed();

        return $verification;
    }

    /**
     * Mark verification as suspicious
     */
    public function markVerificationAsSuspicious(VerificationType $type): UserVerification
    {
        $verification = $this->getOrCreateVerification($type);
        $verification->markAsSuspicious();

        return $verification;
    }

    /**
     * Update verification risk data
     */
    public function updateVerificationRiskData(
        VerificationType $type,
        RiskScore $riskScore,
        ?array $riskSignals = null,
        ?array $riskFlags = null,
        ?VerisoulDecision $decision = null
    ): UserVerification {
        $verification = $this->getOrCreateVerification($type);
        $verification->updateRiskData($riskScore, $riskSignals, $riskFlags, $decision);

        return $verification;
    }

    /**
     * Scope: Users with verified verification of type
     */
    public function scopeWithVerifiedVerification($query, VerificationType $type)
    {
        return $query->whereHas('userVerifications', function ($q) use ($type) {
            $q->where('type', $type)
                ->where('status', VerificationStatus::Verified)
                ->where('decision', VerisoulDecision::Real);
        });
    }

    /**
     * Scope: Users with face verification
     */
    public function scopeWithFaceVerification($query)
    {
        return $query->withVerifiedVerification(VerificationType::Face);
    }

    /**
     * Scope: Users with phone verification
     */
    public function scopeWithPhoneVerification($query)
    {
        return $query->withVerifiedVerification(VerificationType::Phone);
    }

    /**
     * Scope: Users with identity verification
     */
    public function scopeWithIdentityVerification($query)
    {
        return $query->withVerifiedVerification(VerificationType::Identity);
    }

    /**
     * Scope: Users that are fully verified
     */
    public function scopeFullyVerified($query)
    {
        return $query->whereHas('userVerifications', function ($q) {
            $q->where('status', VerificationStatus::Verified)
                ->where('decision', VerisoulDecision::Real)
                ->whereIn('type', [
                    VerificationType::Face,
                    VerificationType::Phone,
                    VerificationType::Identity,
                ]);
        }, '=', 3);
    }

    /**
     * Scope: Users with pending verifications
     */
    public function scopeWithPendingVerifications($query)
    {
        return $query->whereHas('userVerifications', function ($q) {
            $q->where('status', VerificationStatus::Pending);
        });
    }

    /**
     * Scope: Users with failed verifications
     */
    public function scopeWithFailedVerifications($query)
    {
        return $query->whereHas('userVerifications', function ($q) {
            $q->where('status', VerificationStatus::Failed);
        });
    }

    /**
     * Scope: Users with expired verifications
     */
    public function scopeWithExpiredVerifications($query)
    {
        return $query->whereHas('userVerifications', function ($q) {
            $q->where('expires_at', '<', now());
        });
    }

    /**
     * Scope: Users with verifications expiring soon
     */
    public function scopeWithExpiringSoonVerifications($query, int $warningDays = 7)
    {
        return $query->whereHas('userVerifications', function ($q) use ($warningDays) {
            $q->where('expires_at', '>', now())
                ->where('expires_at', '<=', now()->addDays($warningDays));
        });
    }
}
