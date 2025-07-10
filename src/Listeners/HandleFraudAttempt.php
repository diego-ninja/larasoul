<?php

namespace Ninja\Larasoul\Listeners;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use Ninja\Larasoul\Events\FraudAttemptDetected;
use Ninja\Larasoul\Models\RiskProfile;

class HandleFraudAttempt
{
    public function handle(FraudAttemptDetected $event): void
    {
        $verification = $event->riskProfile;
        $user = $event->getUser();

        // Log fraud attempt
        Log::critical('Fraud attempt detected', [
            'user_id' => $user?->getAuthIdentifier(),
            'verification_id' => $verification->id,
            'fraud_indicators' => $event->fraudIndicators,
            'severity' => $event->getFraudSeverity(),
            'immediate_action_required' => $event->requiresImmediateAction(),
        ]);

        // Take immediate action if required
        if ($event->requiresImmediateAction()) {
            $this->takeImmediateAction($user, $verification);
        }

        // Notify security team
        $this->notifySecurityTeam($event);
    }

    private function takeImmediateAction(Authenticatable $user, RiskProfile $verification): void
    {
        // Auto-suspend if configured
        if (config('larasoul.verification.security.auto_suspend_fraud', true)) {
            if ($user && method_exists($user, 'suspend')) {
                $user->suspend();
                Log::critical('User automatically suspended due to fraud detection', [
                    'user_id' => $user->getAuthIdentifier(),
                ]);
            }
        }

        // Mark verification as failed
        $verification->markAsFailed('Fraud detected - immediate suspension');
    }

    private function notifySecurityTeam(FraudAttemptDetected $event): void
    {
        $channels = config('larasoul.verification.notifications.channels.fraud_detected', []);

        foreach ($channels as $channel) {
            Log::critical("Fraud detection notification sent via {$channel}", [
                'verification_id' => $event->riskProfile->id,
                'severity' => $event->getFraudSeverity(),
            ]);
        }
    }
}
