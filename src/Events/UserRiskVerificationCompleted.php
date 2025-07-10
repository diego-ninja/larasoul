<?php

namespace Ninja\Larasoul\Events;

/**
 * Event fired when a user completes verification successfully
 */
class UserRiskVerificationCompleted extends RiskVerificationEvent
{
    /**
     * Get the channels the event should broadcast on
     */
    public function broadcastOn(): array
    {
        return [
            "user.{$this->riskProfile->user_id}.verification",
        ];
    }

    /**
     * Get the data to broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'verification_completed',
            'data' => array_merge($this->getRiskData(), [
                'success' => true,
                'verification_score' => $this->riskProfile->getVerificationScore(),
                'health_status' => $this->riskProfile->getHealthStatus(),
            ]),
        ];
    }
}
