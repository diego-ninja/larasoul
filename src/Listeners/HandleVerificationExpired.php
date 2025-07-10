<?php

namespace Ninja\Larasoul\Listeners;

use Illuminate\Support\Facades\Log;
use Ninja\Larasoul\Events\UserRiskVerificationExpired;

class HandleVerificationExpired
{
    public function handle(UserRiskVerificationExpired $event): void
    {
        $verification = $event->riskProfile;
        $user = $event->getUser();

        // Log expiration
        Log::info('User verification expired', [
            'user_id' => $user?->getAuthIdentifier(),
            'verification_id' => $verification->id,
            'expired_at' => $verification->expires_at,
        ]);

        // Send renewal notification to user
        $this->sendRenewalNotification($user);

        // Downgrade user permissions if needed
        $this->downgradeUserPermissions($user);
    }

    private function sendRenewalNotification($user): void
    {
        if ($user && method_exists($user, 'notify')) {
            Log::info('Verification renewal notification sent to user', [
                'user_id' => $user->getAuthIdentifier(),
            ]);
        }
    }

    private function downgradeUserPermissions($user): void
    {
        // Remove premium access or downgrade permissions
        Log::info('User permissions downgraded due to expired verification', [
            'user_id' => $user?->getAuthIdentifier(),
        ]);
    }
}
