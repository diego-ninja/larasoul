<?php

namespace Ninja\Larasoul\Listeners;

use Illuminate\Support\Facades\Log;
use Ninja\Larasoul\Events\ManualReviewRequired;

class HandleManualReviewRequired
{
    public function handle(ManualReviewRequired $event): void
    {
        $verification = $event->riskProfile;
        $user = $event->getUser();

        // Log manual review requirement
        Log::info('Manual review required for user verification', [
            'user_id' => $user?->getAuthIdentifier(),
            'verification_id' => $verification->id,
            'reason' => $event->reason,
            'priority' => $event->getReviewPriority(),
        ]);

        // Notify review team
        $this->notifyReviewTeam($event);

        // Send notification to user
        $this->sendUserNotification($user, $event);
    }

    private function notifyReviewTeam(ManualReviewRequired $event): void
    {
        $adminEmail = config('larasoul.verification.notifications.admin_channels.mail');

        if ($adminEmail) {
            Log::info('Manual review notification sent to review team', [
                'admin_email' => $adminEmail,
                'verification_id' => $event->riskProfile->id,
                'priority' => $event->getReviewPriority(),
                'estimated_time' => $event->getEstimatedReviewTime(),
            ]);
        }
    }

    private function sendUserNotification($user, ManualReviewRequired $event): void
    {
        if ($user && method_exists($user, 'notify')) {
            Log::info('Manual review notification sent to user', [
                'user_id' => $user->getAuthIdentifier(),
                'estimated_review_time' => $event->getEstimatedReviewTime(),
            ]);
        }
    }
}
