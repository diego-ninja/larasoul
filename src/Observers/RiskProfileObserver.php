<?php

namespace Ninja\Larasoul\Observers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Ninja\Larasoul\Enums\RiskStatus;
use Ninja\Larasoul\Events\HighRiskUserDetected;
use Ninja\Larasoul\Events\UserRiskVerificationCompleted;
use Ninja\Larasoul\Events\UserRiskVerificationExpired;
use Ninja\Larasoul\Events\UserRiskVerificationFailed;
use Ninja\Larasoul\Events\UserRiskVerificationStarted;
use Ninja\Larasoul\Events\UserRiskVerificationStatusChanged;
use Ninja\Larasoul\Models\RiskProfile;

class RiskProfileObserver
{
    /**
     * Handle the UserVerification "creating" event
     */
    public function creating(RiskProfile $riskProfile): void
    {
        // Set default expiry if not set
        if (! $riskProfile->expires_at) {
            $riskProfile->expires_at = now()->addMonths(
                config('larasoul.verification.expiry_months', 12)
            );
        }
    }

    /**
     * Handle the UserVerification "created" event
     */
    public function created(RiskProfile $verification): void
    {
        // Clear user verification cache
        $this->clearUserRiskProfileCache($verification->user_id);

        // Fire verification started event
        Event::dispatch(new UserRiskVerificationStarted($verification));

        // Log verification creation
        logger()->info('User verification created', [
            'user_id' => $verification->user_id,
            'risk_profile_id' => $verification->id,
            'status' => $verification->status,
        ]);
    }

    /**
     * Handle the UserVerification "updating" event
     */
    public function updating(RiskProfile $riskProfile): void
    {
        // Track status changes
        if ($riskProfile->isDirty('status')) {
            $oldStatus = $riskProfile->getOriginal('status');
            $newStatus = $riskProfile->status;

            // Auto-set verified_at timestamp
            if ($newStatus === RiskStatus::Verified && ! $riskProfile->verified_at) {
                $riskProfile->verified_at = now();
            }

            // Auto-set expiry for verified status
            if ($newStatus === RiskStatus::Verified && ! $riskProfile->expires_at) {
                $riskProfile->expires_at = now()->addMonths(
                    config('larasoul.verification.expiry_months', 12)
                );
            }

            // Fire status change event
            Event::dispatch(new UserRiskVerificationStatusChanged(
                $riskProfile,
                $oldStatus,
                $newStatus
            ));
        }

        // Track risk score changes
        if ($riskProfile->isDirty('score')) {
            $oldScore = $riskProfile->getOriginal('score');
            $newScore = $riskProfile->score;

            // Check for high risk detection
            if ($newScore >= 0.8 && ($oldScore < 0.8 || $oldScore === null)) {
                Event::dispatch(new HighRiskUserDetected($riskProfile));
            }

            // Update last risk check
            $riskProfile->last_risk_check_at = now();
        }
    }

    /**
     * Handle the UserVerification "updated" event
     */
    public function updated(RiskProfile $riskProfile): void
    {
        // Clear user verification cache
        $this->clearUserRiskProfileCache($riskProfile->user_id);

        // Handle status-specific actions
        match ($riskProfile->status) {
            RiskStatus::Verified => $this->handleVerificationCompleted($riskProfile),
            RiskStatus::Failed => $this->handleVerificationFailed($riskProfile),
            RiskStatus::ManualReview => $this->handleManualReviewRequired($riskProfile),
            default => null,
        };

        // Check for expiration
        if ($riskProfile->isExpired()) {
            Event::dispatch(new UserRiskVerificationExpired($riskProfile));
        }

        // Log verification update
        logger()->info('User verification updated', [
            'user_id' => $riskProfile->user_id,
            'risk_profile_id' => $riskProfile->id,
            'status' => $riskProfile->status->value,
            'score' => $riskProfile->score,
            'decision' => $riskProfile->decision?->value,
        ]);
    }

    /**
     * Handle the UserVerification "deleted" event
     */
    public function deleted(RiskProfile $riskProfile): void
    {
        // Clear user verification cache
        $this->clearUserRiskProfileCache($riskProfile->user_id);

        // Log verification deletion
        logger()->info('User risk profile deleted', [
            'user_id' => $riskProfile->user_id,
            'risk_profile_id' => $riskProfile->id,
        ]);
    }

    /**
     * Handle successful verification completion
     */
    private function handleVerificationCompleted(RiskProfile $riskProfile): void
    {
        // Fire completion event
        Event::dispatch(new UserRiskVerificationCompleted($riskProfile));

        // Update user's email verification if needed
        if ($riskProfile->user && ! $riskProfile->user->email_verified_at) {
            $riskProfile->user->update([
                'email_verified_at' => now(),
            ]);
        }

        // Cache verification status for quick access
        Cache::put(
            "user:{$riskProfile->user_id}:verification:status",
            'verified',
            now()->addHour()
        );

        // Log successful verification
        logger()->info('User risk verification completed successfully', [
            'user_id' => $riskProfile->user_id,
            'verification_id' => $riskProfile->id,
            'score' => $riskProfile->score,
            'verified_types' => $riskProfile->getVerifiedTypes(),
        ]);
    }

    /**
     * Handle failed verification
     */
    private function handleVerificationFailed(RiskProfile $riskProfile): void
    {
        // Fire failure event
        Event::dispatch(new UserRiskVerificationFailed($riskProfile));

        // Cache failure status
        Cache::put(
            "user:{$riskProfile->user_id}:verification:status",
            'failed',
            now()->addHour()
        );

        // Log failed verification
        logger()->warning('User risk verification failed', [
            'user_id' => $riskProfile->user_id,
            'verification_id' => $riskProfile->id,
            'failure_reason' => $riskProfile->failure_reason,
            'risk_score' => $riskProfile->score,
        ]);
    }

    /**
     * Handle manual review requirement
     */
    private function handleManualReviewRequired(RiskProfile $riskProfile): void
    {
        // Cache manual review status
        Cache::put(
            "user:{$riskProfile->user_id}:verification:status",
            'manual_review',
            now()->addHour()
        );

        // Log manual review requirement
        logger()->info('User verification requires manual review', [
            'user_id' => $riskProfile->user_id,
            'risk_profile_id' => $riskProfile->id,
            'score' => $riskProfile->score,
            'signals' => $riskProfile->signals,
            'reason' => $riskProfile->failure_reason,
        ]);
    }

    /**
     * Clear user verification cache
     */
    private function clearUserRiskProfileCache(int $userId): void
    {
        $cacheKeys = [
            "user:{$userId}:verification",
            "user:{$userId}:verification:status",
            "user:{$userId}:verification:score",
            "user:{$userId}:verification:summary",
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }
}
