<?php

namespace Ninja\Larasoul\Events;

/**
 * Event fired when a high-risk user is detected
 */
class HighRiskUserDetected extends RiskVerificationEvent
{
    /**
     * Get the channels the event should broadcast on
     */
    public function broadcastOn(): array
    {
        return [
            'admin.high-risk-alerts',
            "user.{$this->riskProfile->user_id}.risk",
        ];
    }
}
