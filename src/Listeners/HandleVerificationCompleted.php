<?php

namespace Ninja\Larasoul\Listeners;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use Ninja\Larasoul\Events\UserRiskVerificationCompleted;

class HandleVerificationCompleted
{
    public function handle(UserRiskVerificationCompleted $event): void
    {
        $verification = $event->riskProfile;
        $user = $event->getUser();

        // Log successful verification
        Log::info('User verification completed successfully', [
            'user_id' => $user?->getAuthIdentifier(),
            'verification_id' => $verification->id,
            'risk_score' => $verification->risk_score,
            'verified_types' => $verification->getVerifiedTypes(),
        ]);

        // Send welcome notification to user
        $this->sendWelcomeNotification($user);

        // Update user permissions if needed
        $this->updateUserPermissions($user);
    }

    private function sendWelcomeNotification($user): void
    {
        if ($user && method_exists($user, 'notify')) {
            // Send welcome notification
            // This would typically use a custom notification class
            Log::info('Welcome notification sent to verified user', [
                'user_id' => $user->getAuthIdentifier(),
            ]);
        }
    }

    private function updateUserPermissions(Authenticatable $user): void
    {
        // Auto-approve low risk users if configured
        if (config('larasoul.verification.auto_actions.approve_low_risk', true)) {
            if ($user && $user->isLowRisk()) {
                // Update user role or permissions
                Log::info('User permissions updated for low risk verified user', [
                    'user_id' => $user->getAuthIdentifier(),
                ]);
            }
        }
    }
}
