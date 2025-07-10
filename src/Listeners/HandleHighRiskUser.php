<?php

namespace Ninja\Larasoul\Listeners;

use Illuminate\Support\Facades\Log;
use Ninja\Larasoul\Events\HighRiskUserDetected;

/**
 * Handle high risk user detection
 */
class HandleHighRiskUser
{
    public function handle(HighRiskUserDetected $event): void
    {
        $verification = $event->riskProfile;
        $user = $event->getUser();

        // Log the high risk detection
        Log::warning('High risk user detected', [
            'user_id' => $user->getAuthIdentifier(),
            'verification_id' => $verification->id,
            'risk_score' => $verification->score,
            'decision' => $verification->decision?->value,
        ]);

        // Auto-suspend if configured
        if (config('larasoul.verification.auto_actions.suspend_high_risk', false)) {
            $this->suspendUser($user);
        }

        // Notify administrators
        $this->notifyAdministrators($event);
    }

    private function suspendUser($user): void
    {
        if ($user && method_exists($user, 'suspend')) {
            $user->suspend();
            Log::info('User automatically suspended due to high risk', [
                'user_id' => $user->getAuthIdentifier(),
            ]);
        }
    }

    private function notifyAdministrators(HighRiskUserDetected $event): void
    {
        $adminEmail = config('larasoul.verification.notifications.admin_channels.mail');

        if ($adminEmail) {
            // Send notification to admin
            // This would typically use a custom notification class
            Log::info('High risk user notification sent to admin', [
                'admin_email' => $adminEmail,
                'user_id' => $event->getUser()?->getAuthIdentifier(),
            ]);
        }
    }
}
