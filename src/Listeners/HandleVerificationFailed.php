<?php

namespace Ninja\Larasoul\Listeners;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use Ninja\Larasoul\Events\UserRiskVerificationFailed;
use Ninja\Larasoul\Models\RiskProfile;

class HandleVerificationFailed
{
    public function handle(UserRiskVerificationFailed $event): void
    {
        $verification = $event->riskProfile;
        $user = $event->getUser();

        // Log verification failure
        Log::warning('User verification failed', [
            'user_id' => $user?->getAuthIdentifier(),
            'verification_id' => $verification->id,
            'failure_reason' => $verification->failure_reason,
            'attempts' => $verification->verification_attempts,
            'risk_score' => $verification->risk_score,
        ]);

        // Send failure notification to user
        $this->sendFailureNotification($user, $verification);

        // Check if max attempts exceeded
        if ($verification->verification_attempts >= config('larasoul.verification.max_attempts', 3)) {
            $this->handleMaxAttemptsExceeded($user, $verification);
        }
    }

    private function sendFailureNotification(Authenticatable $user, RiskProfile $verification): void
    {
        if ($user && method_exists($user, 'notify')) {
            // Send failure notification
            Log::info('Verification failure notification sent to user', [
                'user_id' => $user->getAuthIdentifier(),
                'attempts_remaining' => max(0, config('larasoul.verification.max_attempts', 3) - $verification->verification_attempts),
            ]);
        }
    }

    private function handleMaxAttemptsExceeded($user, $verification): void
    {
        Log::warning('User exceeded maximum verification attempts', [
            'user_id' => $user?->getAuthIdentifier(),
            'verification_id' => $verification->id,
            'total_attempts' => $verification->verification_attempts,
        ]);

        // Trigger manual review
        $verification->markForManualReview('Maximum verification attempts exceeded');
    }
}
